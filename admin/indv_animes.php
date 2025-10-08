<?php
require "conexao.php"; // Garante que $pdo existe

// ======================================================
// 1. FUNÇÕES E CONFIGURAÇÃO - CORRIGIDO
// ======================================================

// CONFIGURAÇÃO DE PASTA CORRIGIDA:
$PASTA_FUNDOS = "indvanimes/"; // MUDEI PARA O NOME CORRETO

// Garante que a pasta existe com permissões
if (!is_dir($PASTA_FUNDOS)) {
    if (!mkdir($PASTA_FUNDOS, 0755, true)) {
        die("Erro: Não foi possível criar a pasta $PASTA_FUNDOS");
    }
}

// Verifica permissões de escrita
if (!is_writable($PASTA_FUNDOS)) {
    die("Erro: A pasta $PASTA_FUNDOS não tem permissão de escrita");
}

// --- FUNÇÃO PARA REDIMENSIONAR IMAGEM ---
function redimensionar_imagem($caminho_origem, $caminho_destino, $largura_desejada, $altura_desejada) {
    // Verifica se o arquivo existe
    if (!file_exists($caminho_origem)) {
        return false;
    }
    
    // Obtém informações da imagem
    $info = getimagesize($caminho_origem);
    if (!$info) {
        return false;
    }
    
    $largura_original = $info[0];
    $altura_original = $info[1];
    $tipo = $info['mime'];
    
    // Cria imagem a partir do arquivo original
    switch ($tipo) {
        case 'image/jpeg':
            $imagem_original = imagecreatefromjpeg($caminho_origem);
            break;
        case 'image/png':
            $imagem_original = imagecreatefrompng($caminho_origem);
            break;
        case 'image/gif':
            $imagem_original = imagecreatefromgif($caminho_origem);
            break;
        case 'image/webp':
            $imagem_original = imagecreatefromwebp($caminho_origem);
            break;
        default:
            return false;
    }
    
    if (!$imagem_original) {
        return false;
    }
    
    // Cria nova imagem com dimensões desejadas
    $nova_imagem = imagecreatetruecolor($largura_desejada, $altura_desejada);
    
    // Preserva transparência para PNG e GIF
    if ($tipo == 'image/png' || $tipo == 'image/gif') {
        imagecolortransparent($nova_imagem, imagecolorallocatealpha($nova_imagem, 0, 0, 0, 127));
        imagealphablending($nova_imagem, false);
        imagesavealpha($nova_imagem, true);
    }
    
    // Redimensiona a imagem
    imagecopyresampled($nova_imagem, $imagem_original, 0, 0, 0, 0, 
                      $largura_desejada, $altura_desejada, 
                      $largura_original, $altura_original);
    
    // Salva a imagem redimensionada
    $resultado = false;
    switch ($tipo) {
        case 'image/jpeg':
            $resultado = imagejpeg($nova_imagem, $caminho_destino, 85);
            break;
        case 'image/png':
            $resultado = imagepng($nova_imagem, $caminho_destino, 8);
            break;
        case 'image/gif':
            $resultado = imagegif($nova_imagem, $caminho_destino);
            break;
        case 'image/webp':
            $resultado = imagewebp($nova_imagem, $caminho_destino, 85);
            break;
    }
    
    // Libera memória
    imagedestroy($imagem_original);
    imagedestroy($nova_imagem);
    
    return $resultado;
}

// --- FUNÇÃO AUXILIAR PARA UPLOAD MELHORADA ---
function handle_upload($file_key, $prefix, $pasta_upload, $current_file = NULL, $redimensionar = false, $largura = null, $altura = null) {
    // Verifica se o arquivo foi enviado
    if (!isset($_FILES[$file_key]) || $_FILES[$file_key]['error'] !== UPLOAD_ERR_OK) {
        return $current_file;
    }
    
    $file_array = $_FILES[$file_key];
    
    // Verifica se é uma imagem
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file_array['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mime_type, $allowed_types)) {
        return $current_file;
    }
    
    // Gera novo nome
    $extensao = strtolower(pathinfo($file_array['name'], PATHINFO_EXTENSION));
    $novo_nome = uniqid() . $prefix . "." . $extensao;
    $caminho_final = $pasta_upload . $novo_nome;
    
    // Move o arquivo
    if (move_uploaded_file($file_array['tmp_name'], $caminho_final)) {
        // Se precisa redimensionar, chama a função de redimensionamento
        if ($redimensionar && $largura && $altura) {
            $caminho_redimensionado = $pasta_upload . "redimensionado_" . $novo_nome;
            if (redimensionar_imagem($caminho_final, $caminho_redimensionado, $largura, $altura)) {
                // Remove o arquivo original e usa o redimensionado
                unlink($caminho_final);
                rename($caminho_redimensionado, $caminho_final);
            }
        }
        return $novo_nome;
    }
    
    return $current_file;
}

// ======================================================
// 2. LÓGICA DE INSERÇÃO/EDIÇÃO/EXCLUSÃO
// ======================================================

// =============================== INSERÇÃO ===============================
if (isset($_POST['acao']) && $_POST['acao'] == "inserir_detalhes") {
    
    $id_carrossel = $_POST['id_carrossel'];
    
    // 1. Buscar Título e Idioma do Carrossel (para copiar)
    $sql_c = "SELECT nome, idioma FROM carrossel WHERE id = :id";
    $stmt_c = $pdo->prepare($sql_c);
    $stmt_c->execute([':id' => $id_carrossel]);
    $dados_carrossel = $stmt_c->fetch(PDO::FETCH_ASSOC);

    if (!$dados_carrossel) {
        header("Location: " . $_SERVER['PHP_SELF'] . "?erro=carrossel_nao_encontrado"); 
        exit;
    }
    
    $titulo_copiado = $dados_carrossel['nome'];
    $idioma_copiado = $dados_carrossel['idioma'];

    // 2. Tratamento dos uploads (Usando a NOVA PASTA)
    // Imagem de fundo: redimensiona para 1920x762
    $novo_nome_fundo = handle_upload('imagem_fundo', '_fundo', $PASTA_FUNDOS, null, true, 1920, 762);
    // Classificação: redimensiona para 100x100 (quadrada)
    $novo_nome_classificacao = handle_upload('classificacao', '_class', $PASTA_FUNDOS, null, true, 100, 100);
    
    // 3. Inserção final no indv_anime ✅ TABELA ATUALIZADA
    $sql_insert = "
        INSERT INTO `indv_anime` (
            `id_carrossel`, `titulo`, `idioma`, `imagem_fundo`, `classificacao`, 
            `genero`, `classificacao_media`, `descricao`, `audio`, 
            `legendas`, `premios`, `classificacao_conteudo`
        ) VALUES (
            :id_c, :titulo, :idioma, :img_fundo, :classificacao, 
            :genero, :classificacao_media, :descricao, :audio, 
            :legendas, :premios, :classificacao_conteudo
        )
    ";

    $stmt = $pdo->prepare($sql_insert);
    $stmt->execute([
        ':id_c'             => $id_carrossel,
        ':titulo'           => $titulo_copiado,
        ':idioma'           => $idioma_copiado,
        ':img_fundo'        => $novo_nome_fundo,
        ':classificacao'    => $novo_nome_classificacao,
        ':descricao'        => $_POST['descricao'] ?? NULL,
        ':genero'           => $_POST['genero'] ?? NULL,
        ':classificacao_media' => $_POST['classificacao_media'] ?? NULL,
        ':audio'            => $_POST['audio'] ?? NULL,
        ':legendas'         => $_POST['legendas'] ?? NULL,
        ':premios'          => $_POST['premios'] ?? NULL,
        ':classificacao_conteudo' => $_POST['classificacao_conteudo'] ?? NULL,
    ]);

    header("Location: " . $_SERVER['PHP_SELF'] . "?sucesso=1"); 
    exit;
}

// =============================== EDIÇÃO (UPDATE) ===============================
if (isset($_POST['acao']) && $_POST['acao'] == "editar_detalhes") {

    $id_detalhe = $_POST['id_detalhe'];
    $id_carrossel = $_POST['id_carrossel'];
    
    // 1. Puxar dados atuais para manter as imagens que não forem substituídas ✅ TABELA ATUALIZADA
    $sql_select = "SELECT imagem_fundo, classificacao FROM `indv_anime` WHERE id = :id";
    $stmt_select = $pdo->prepare($sql_select);
    $stmt_select->execute([':id'=>$id_detalhe]);
    $detalhe_atual = $stmt_select->fetch(PDO::FETCH_ASSOC);

    // 2. Tratamento dos uploads, mantendo os nomes de arquivos atuais se nenhum novo for enviado
    // Imagem de fundo: redimensiona para 1920x762
    $img_fundo = handle_upload('imagem_fundo', '_fundo_edit', $PASTA_FUNDOS, $detalhe_atual['imagem_fundo'], true, 1920, 762);
    // Classificação: redimensiona para 100x100 (quadrada)
    $classificacao_img = handle_upload('classificacao', '_class_edit', $PASTA_FUNDOS, $detalhe_atual['classificacao'], true, 100, 100);

    // 3. Buscar Título e Idioma do Carrossel (para garantir que estão atualizados)
    $sql_c = "SELECT nome, idioma FROM carrossel WHERE id = :id";
    $stmt_c = $pdo->prepare($sql_c);
    $stmt_c->execute([':id' => $id_carrossel]);
    $dados_carrossel = $stmt_c->fetch(PDO::FETCH_ASSOC);

    $titulo_copiado = $dados_carrossel['nome'];
    $idioma_copiado = $dados_carrossel['idioma'];

    // 4. Atualização final no indv_anime ✅ TABELA ATUALIZADA
    $sql_update = "
        UPDATE `indv_anime` SET 
            `id_carrossel` = :id_c,
            `titulo` = :titulo,
            `idioma` = :idioma,
            `imagem_fundo` = :img_fundo,
            `classificacao` = :classificacao_img,
            `genero` = :genero,
            `classificacao_media` = :classificacao_media,
            `descricao` = :descricao,
            `audio` = :audio,
            `legendas` = :legendas,
            `premios` = :premios,
            `classificacao_conteudo` = :classificacao_conteudo
        WHERE `id` = :id_detalhe
    ";

    $stmt = $pdo->prepare($sql_update);
    $stmt->execute([
        ':id_c'             => $id_carrossel,
        ':titulo'           => $titulo_copiado,
        ':idioma'           => $idioma_copiado,
        ':img_fundo'        => $img_fundo,
        ':classificacao_img' => $classificacao_img,
        ':descricao'        => $_POST['descricao'] ?? NULL,
        ':genero'           => $_POST['genero'] ?? NULL,
        ':classificacao_media' => $_POST['classificacao_media'] ?? NULL,
        ':audio'            => $_POST['audio'] ?? NULL,
        ':legendas'         => $_POST['legendas'] ?? NULL,
        ':premios'          => $_POST['premios'] ?? NULL,
        ':classificacao_conteudo' => $_POST['classificacao_conteudo'] ?? NULL,
        ':id_detalhe'       => $id_detalhe
    ]);

    header("Location: " . $_SERVER['PHP_SELF'] . "?sucesso=2");
    exit;
}

// Lógica de DELETAR Detalhes ✅ TABELA ATUALIZADA
if (isset($_GET['acao']) && $_GET['acao'] == "deletar_detalhes" && isset($_GET['id'])) {
    $id_detalhe = $_GET['id'];
    $sql = "DELETE FROM `indv_anime` WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id'=>$id_detalhe]);
    header("Location: " . $_SERVER['PHP_SELF'] . "?sucesso=3");
    exit;
}


// ======================================================
// 3. PEGAR REGISTROS PARA POPULAR O HTML
// ======================================================

// Puxa todos os carrosseis para popular o <select>
$sql_c_todos = "SELECT id, nome, idioma FROM carrossel ORDER BY id ASC";
$stmt_c_todos = $pdo->query($sql_c_todos);
$carrosseis = $stmt_c_todos->fetchAll(PDO::FETCH_ASSOC);

// Puxa todos os detalhes para a tabela de listagem do indv_anime ✅ TABELA ATUALIZADA
$sql_i_todos = "SELECT i.*, c.nome AS titulo_principal FROM `indv_anime` i JOIN `carrossel` c ON i.id_carrossel = c.id ORDER BY i.id DESC";
$stmt_i_todos = $pdo->query($sql_i_todos);
$detalhes_animes = $stmt_i_todos->fetchAll(PDO::FETCH_ASSOC);

// Lógica para busca do registro de detalhes para EDIÇÃO ✅ TABELA ATUALIZADA
$registro_detalhe_editar = null;
if (isset($_GET['acao']) && $_GET['acao'] == "editar_detalhes" && isset($_GET['id'])) {
    $id_detalhe = $_GET['id'];
    $sql = "SELECT * FROM `indv_anime` WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id'=>$id_detalhe]);
    $registro_detalhe_editar = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Detalhes de Animes (indv_anime)</title>
    <style>
        :root {
            --primary: #4361ee;
            --secondary: #3a0ca3;
            --success: #4cc9f0;
            --danger: #f72585;
            --warning: #f8961e;
            --dark: #212529;
            --light: #f8f9fa;
            --gray: #6c757d;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
            color: var(--dark);
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .header h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
        }
        
        .content {
            padding: 30px;
        }
        
        .card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            padding: 25px;
            margin-bottom: 30px;
            border-left: 4px solid var(--primary);
        }
        
        h2 {
            color: var(--primary);
            margin-bottom: 20px;
            font-size: 1.8rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        h2::before {
            content: "";
            width: 4px;
            height: 25px;
            background: var(--primary);
            border-radius: 2px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--dark);
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 16px;
            transition: all 0.3s;
        }
        
        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.2);
            outline: none;
        }
        
        textarea.form-control {
            min-height: 120px;
            resize: vertical;
        }
        
        .file-input {
            padding: 10px;
            border: 2px dashed #dee2e6;
            border-radius: 8px;
            background: #f8f9fa;
            transition: all 0.3s;
        }
        
        .file-input:hover {
            border-color: var(--primary);
            background: #e9ecef;
        }
        
        .file-info {
            font-size: 14px;
            color: var(--gray);
            margin-top: 5px;
            font-style: italic;
        }
        
        .dimension-info {
            font-size: 12px;
            color: var(--primary);
            margin-top: 3px;
            font-weight: 500;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
        }
        
        .btn-primary {
            background: var(--primary);
            color: white;
        }
        
        .btn-primary:hover {
            background: var(--secondary);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(67, 97, 238, 0.3);
        }
        
        .btn-success {
            background: #28a745;
            color: white;
        }
        
        .btn-success:hover {
            background: #218838;
            transform: translateY(-2px);
        }
        
        .btn-warning {
            background: var(--warning);
            color: black;
        }
        
        .btn-warning:hover {
            background: #e68900;
            transform: translateY(-2px);
        }
        
        .btn-danger {
            background: var(--danger);
            color: white;
        }
        
        .btn-danger:hover {
            background: #e01e5a;
            transform: translateY(-2px);
        }
        
        .btn-secondary {
            background: var(--gray);
            color: white;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }
        
        .btn-sm {
            padding: 8px 15px;
            font-size: 14px;
        }
        
        .action-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .grid-campos {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .grid-item {
            display: flex;
            flex-direction: column;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        th, td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
        }
        
        th {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 14px;
            letter-spacing: 0.5px;
        }
        
        tr:hover {
            background-color: rgba(67, 97, 238, 0.05);
        }
        
        .table-img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 6px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .table-img-large {
            width: 120px;
            height: 70px;
            object-fit: cover;
            border-radius: 6px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border-color: #c3e6cb;
        }
        
        .alert-warning {
            background: #fff3cd;
            color: #856404;
            border-color: #ffeeba;
        }
        
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border-color: #f5c6cb;
        }
        
        .alert-info {
            background: #d1ecf1;
            color: #0c5460;
            border-color: #bee5eb;
        }
        
        .required::after {
            content: " *";
            color: var(--danger);
            font-weight: bold;
        }
        
        .nav-buttons {
            display: flex;
            gap: 15px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }
        
        .cancel-link {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            margin-top: 15px;
            color: var(--gray);
            text-decoration: none;
            padding: 8px 15px;
            border: 1px solid #dee2e6;
            border-radius: 6px;
            transition: all 0.3s;
        }
        
        .cancel-link:hover {
            background: #f8f9fa;
            color: var(--dark);
        }
        
        @media (max-width: 768px) {
            .container {
                margin: 10px;
            }
            
            .content {
                padding: 20px;
            }
            
            .nav-buttons {
                flex-direction: column;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .grid-campos {
                grid-template-columns: 1fr;
            }
            
            table {
                font-size: 14px;
            }
            
            th, td {
                padding: 10px 8px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎬 Detalhes de Animes</h1>
            <p>Gerenciamento completo dos detalhes dos animes</p>
        </div>
        
        <div class="content">
            <!-- NAVEGAÇÃO -->
            <div class="nav-buttons">
                <a href="index.php" class="btn btn-secondary">
                    ⬅️ Voltar para CRUD do Carrossel
                </a>
            </div>

            <?php 
            if (isset($_GET['sucesso'])) {
                if ($_GET['sucesso'] == 1) {
                    echo '<div class="alert alert-success">✅ Detalhes do Anime inseridos com sucesso!</div>';
                } elseif ($_GET['sucesso'] == 2) {
                    echo '<div class="alert alert-success">✅ Detalhes do Anime atualizados com sucesso!</div>';
                } elseif ($_GET['sucesso'] == 3) {
                    echo '<div class="alert alert-info">🗑️ Detalhes do Anime deletados com sucesso!</div>';
                }
            }
            if (isset($_GET['erro']) && $_GET['erro'] == 'carrossel_nao_encontrado') {
                echo '<div class="alert alert-danger">❌ Erro: O ID do Anime Principal não foi encontrado. A inserção falhou.</div>';
            }
            ?>

            <!-- FORMULÁRIO INSERIR / EDITAR -->
            <?php if ($registro_detalhe_editar) : ?>
                <div class="card">
                    <h2>✏️ Editar Detalhes do Anime #<?= $registro_detalhe_editar['id'] ?></h2>
                    <form method="post" enctype="multipart/form-data">
                        <input type="hidden" name="acao" value="editar_detalhes">
                        <input type="hidden" name="id_detalhe" value="<?= $registro_detalhe_editar['id'] ?>">
                        
                        <div class="form-group">
                            <label for="id_carrossel" class="required">Anime Principal (Carrossel):</label>
                            <select name="id_carrossel" id="id_carrossel" class="form-control" required>
                                <?php foreach ($carrosseis as $c) { ?>
                                    <option value="<?= $c['id'] ?>" <?= ($c['id'] == $registro_detalhe_editar['id_carrossel']) ? 'selected' : '' ?>>
                                        <?= $c['id'] ?> - <?= htmlspecialchars($c['nome']) ?> (<?= htmlspecialchars($c['idioma']) ?>)
                                    </option>
                                <?php } ?>
                            </select>
                        </div>

                        <div class="grid-campos">
                            <div class="grid-item">
                                <label for="imagem_fundo" class="required">Imagem de Fundo:</label>
                                <input type="file" name="imagem_fundo" class="form-control file-input" accept="image/*">
                                <div class="file-info">Atual: <?= htmlspecialchars($registro_detalhe_editar['imagem_fundo']) ?></div>
                                <div class="dimension-info">📐 Será redimensionada para 1920x762 pixels</div>
                            </div>
                            <div class="grid-item">
                                <label for="classificacao" class="required">Classificação (Selo):</label>
                                <input type="file" name="classificacao" class="form-control file-input" accept="image/*">
                                <div class="file-info">Atual: <?= htmlspecialchars($registro_detalhe_editar['classificacao']) ?></div>
                                <div class="dimension-info">🎯 Será redimensionada para 100x100 pixels (quadrada)</div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="descricao" class="required">Descrição:</label>
                            <textarea name="descricao" class="form-control" rows="5" required><?= htmlspecialchars($registro_detalhe_editar['descricao']) ?></textarea>
                        </div>

                        <div class="grid-campos">
                            <div class="grid-item">
                                <label for="genero" class="required">Gênero:</label>
                                <input type="text" name="genero" class="form-control" value="<?= htmlspecialchars($registro_detalhe_editar['genero']) ?>" required>
                            </div>
                            <div class="grid-item">
                                <label for="classificacao_media" class="required">Classificação Média:</label>
                                <input type="text" name="classificacao_media" class="form-control" value="<?= htmlspecialchars($registro_detalhe_editar['classificacao_media']) ?>" required>
                            </div>
                            <div class="grid-item">
                                <label for="audio" class="required">Áudio:</label>
                                <input type="text" name="audio" class="form-control" value="<?= htmlspecialchars($registro_detalhe_editar['audio']) ?>" required>
                            </div>
                            <div class="grid-item">
                                <label for="legendas" class="required">Legendas:</label>
                                <input type="text" name="legendas" class="form-control" value="<?= htmlspecialchars($registro_detalhe_editar['legendas']) ?>" required>
                            </div>
                            <div class="grid-item">
                                <label for="premios">Prêmios:</label>
                                <input type="text" name="premios" class="form-control" value="<?= htmlspecialchars($registro_detalhe_editar['premios']) ?>">
                            </div>
                            <div class="grid-item">
                                <label for="classificacao_conteudo" class="required">Classificação Conteúdo:</label>
                                <input type="text" name="classificacao_conteudo" class="form-control" value="<?= htmlspecialchars($registro_detalhe_editar['classificacao_conteudo']) ?>" required>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-warning">
                            🔄 Atualizar Detalhes
                        </button>
                    </form>
                    <a href="<?= $_SERVER['PHP_SELF'] ?>" class="cancel-link">
                        ❌ Cancelar Edição
                    </a>
                </div>

            <?php else : ?>
                <div class="card">
                    <h2>➕ Inserir Novos Detalhes</h2>
                    <form method="post" enctype="multipart/form-data">
                        <input type="hidden" name="acao" value="inserir_detalhes">
                        
                        <div class="form-group">
                            <label for="id_carrossel" class="required">Anime Principal (Carrossel):</label>
                            <select name="id_carrossel" id="id_carrossel" class="form-control" required>
                                <option value="">-- Selecione o Anime --</option>
                                <?php foreach ($carrosseis as $c) { ?>
                                    <option value="<?= $c['id'] ?>">
                                        <?= $c['id'] ?> - <?= htmlspecialchars($c['nome']) ?> (<?= htmlspecialchars($c['idioma']) ?>)
                                    </option>
                                <?php } ?>
                            </select>
                        </div>
                        
                        <div class="grid-campos">
                            <div class="grid-item">
                                <label for="imagem_fundo" class="required">Imagem de Fundo:</label>
                                <input type="file" name="imagem_fundo" class="form-control file-input" accept="image/*" required>
                                <div class="file-info">Fundo da página de detalhes</div>
                                <div class="dimension-info">📐 Será redimensionada para 1920x762 pixels</div>
                            </div>
                            <div class="grid-item">
                                <label for="classificacao" class="required">Classificação (Selo):</label>
                                <input type="file" name="classificacao" class="form-control file-input" accept="image/*" required>
                                <div class="file-info">Selo de faixa etária (imagem)</div>
                                <div class="dimension-info">🎯 Será redimensionada para 100x100 pixels (quadrada)</div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="descricao" class="required">Descrição:</label>
                            <textarea name="descricao" class="form-control" rows="5" required></textarea>
                        </div>

                        <div class="grid-campos">
                            <div class="grid-item">
                                <label for="genero" class="required">Gênero:</label>
                                <input type="text" name="genero" class="form-control" required>
                            </div>
                            <div class="grid-item">
                                <label for="classificacao_media" class="required">Classificação Média:</label>
                                <input type="text" name="classificacao_media" class="form-control" required>
                            </div>
                            <div class="grid-item">
                                <label for="audio" class="required">Áudio:</label>
                                <input type="text" name="audio" class="form-control" required>
                            </div>
                            <div class="grid-item">
                                <label for="legendas" class="required">Legendas:</label>
                                <input type="text" name="legendas" class="form-control" required>
                            </div>
                            <div class="grid-item">
                                <label for="premios">Prêmios:</label>
                                <input type="text" name="premios" class="form-control">
                            </div>
                            <div class="grid-item">
                                <label for="classificacao_conteudo" class="required">Classificação Conteúdo:</label>
                                <input type="text" name="classificacao_conteudo" class="form-control" required>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-success">
                            ✅ Inserir Detalhes do Anime
                        </button>
                    </form>
                </div>
            <?php endif; ?>

            <!-- TABELA DE DETALHES EXISTENTES -->
            <div class="card">
                <h2>📋 Detalhes Existentes</h2>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Título Principal</th>
                            <th>Classificação</th>
                            <th>Descrição</th>
                            <th>Imagem Fundo</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($detalhes_animes as $d) { ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($d['id']) ?></strong></td>
                                <td>
                                    <strong><?= htmlspecialchars($d['titulo_principal']) ?></strong>
                                    <br><small>Copiado: <?= htmlspecialchars($d['titulo']) ?></small>
                                </td>
                                <td>
                                    <?php if ($d['classificacao']) : ?>
                                        <img src="<?= $PASTA_FUNDOS . htmlspecialchars($d['classificacao']) ?>" class="table-img" alt="Classificação">
                                    <?php else: ?>
                                        <span style="color: var(--gray);">N/A</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars(substr($d['descricao'], 0, 50)) . (strlen($d['descricao']) > 50 ? '...' : '') ?></td>
                                <td>
                                    <?php if ($d['imagem_fundo']) : ?>
                                        <img src="<?= $PASTA_FUNDOS . htmlspecialchars($d['imagem_fundo']) ?>" class="table-img-large" alt="Fundo">
                                    <?php else: ?>
                                        <span style="color: var(--gray);">N/A</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="?acao=editar_detalhes&id=<?= $d['id'] ?>" class="btn btn-primary btn-sm">
                                            ✏️ Editar
                                        </a>
                                        <a href="?acao=deletar_detalhes&id=<?= $d['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Tem certeza que deseja deletar estes detalhes?')">
                                            🗑️ Deletar
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>