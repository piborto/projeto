<?php
require "conexao.php";

// ===================== FUNÇÃO PARA REDIMENSIONAR IMAGEM (CORTE) =====================
function redimensionarImagem($caminho_original, $caminho_destino, $largura, $altura) {
    // Obtém informações da imagem
    $info = getimagesize($caminho_original);
    if (!$info) return false;
    
    $mime = $info['mime'];
    
    // Cria imagem baseada no tipo
    switch ($mime) {
        case 'image/jpeg':
            $imagem_original = imagecreatefromjpeg($caminho_original);
            break;
        case 'image/png':
            $imagem_original = imagecreatefrompng($caminho_original);
            break;
        case 'image/gif':
            $imagem_original = imagecreatefromgif($caminho_original);
            break;
        case 'image/webp':
            $imagem_original = imagecreatefromwebp($caminho_original);
            break;
        default:
            return false;
    }
    
    if (!$imagem_original) return false;
    
    // Obtém dimensões originais
    $largura_original = imagesx($imagem_original);
    $altura_original = imagesy($imagem_original);
    
    // Calcula proporções
    $proporcao_original = $largura_original / $altura_original;
    $proporcao_desejada = $largura / $altura;
    
    // Cria nova imagem
    $imagem_redimensionada = imagecreatetruecolor($largura, $altura);
    
    // Calcula dimensões para cortar e preencher o espaço
    if ($proporcao_original > $proporcao_desejada) {
        // Imagem mais larga - corta as laterais
        $src_h = $altura_original;
        $src_w = $altura_original * $proporcao_desejada;
        $src_x = ($largura_original - $src_w) / 2;
        $src_y = 0;
    } else {
        // Imagem mais alta - corta topo e base
        $src_w = $largura_original;
        $src_h = $largura_original / $proporcao_desejada;
        $src_x = 0;
        $src_y = ($altura_original - $src_h) / 2;
    }
    
    // Redimensiona cortando a imagem
    imagecopyresampled(
        $imagem_redimensionada, $imagem_original,
        0, 0, $src_x, $src_y,
        $largura, $altura, $src_w, $src_h
    );
    
    // Salva a imagem
    $resultado = false;
    switch ($mime) {
        case 'image/jpeg':
            $resultado = imagejpeg($imagem_redimensionada, $caminho_destino, 90);
            break;
        case 'image/png':
            $resultado = imagepng($imagem_redimensionada, $caminho_destino, 8);
            break;
        case 'image/gif':
            $resultado = imagegif($imagem_redimensionada, $caminho_destino);
            break;
        case 'image/webp':
            $resultado = imagewebp($imagem_redimensionada, $caminho_destino, 90);
            break;
    }
    
    // Libera memória
    imagedestroy($imagem_original);
    imagedestroy($imagem_redimensionada);
    
    return $resultado;
}

// ===================== INSERIR =====================
if (isset($_POST['acao']) && $_POST['acao'] == "inserir") {
    $nome = $_POST['nome'];
    $idioma = $_POST['idioma'];

    // Tratamento do upload
    if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] == 0) {
        $pasta_upload = "uploads/";
        if (!is_dir($pasta_upload)) mkdir($pasta_upload, 0777, true);

        $arquivo_tmp = $_FILES['imagem']['tmp_name'];
        $nome_arquivo = basename($_FILES['imagem']['name']);
        $extensao = strtolower(pathinfo($nome_arquivo, PATHINFO_EXTENSION));
        
        // Verifica se é uma imagem válida
        $tipos_permitidos = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (!in_array($extensao, $tipos_permitidos)) {
            echo "<div class='alert error'>Erro: Apenas imagens JPG, PNG, GIF e WebP são permitidas!</div>";
            exit;
        }
        
        // Verifica tamanho mínimo da imagem
        $info_imagem = getimagesize($arquivo_tmp);
        $largura_minima = 800;
        $altura_minima = 1200;
        
        if ($info_imagem[0] < $largura_minima || $info_imagem[1] < $altura_minima) {
            echo "<div class='alert error'>Erro: A imagem deve ter no mínimo {$largura_minima}x{$altura_minima} pixels. Sua imagem tem {$info_imagem[0]}x{$info_imagem[1]} pixels.</div>";
            exit;
        }
        
        $novo_nome = uniqid() . "." . $extensao;
        $caminho_final = $pasta_upload . $novo_nome;
        
        // Redimensiona a imagem para 1064x1596 (CORTANDO)
        if (redimensionarImagem($arquivo_tmp, $caminho_final, 1064, 1596)) {
            // Inserção no banco com o novo nome do arquivo
            $sql = "INSERT INTO carrossel (nome, imagem, idioma) VALUES (:nome, :imagem, :idioma)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':nome' => $nome,
                ':imagem' => $novo_nome,
                ':idioma' => $idioma
            ]);

            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
        } else {
            echo "<div class='alert error'>Erro ao processar a imagem!</div>";
        }
    } else {
        echo "<div class='alert error'>Erro no upload da imagem!</div>";
    }
}

// ===================== ATUALIZAR =====================
if (isset($_POST['acao']) && $_POST['acao'] == "editar") {
    // Primeiro busca a imagem atual do banco
    $id = $_POST['id'];
    $sql_select = "SELECT imagem FROM carrossel WHERE id = :id";
    $stmt_select = $pdo->prepare($sql_select);
    $stmt_select->execute([':id'=>$id]);
    $imagem_atual = $stmt_select->fetchColumn();

    $imagem = $imagem_atual; // Mantém a imagem atual por padrão

    // Verifica se foi enviada uma nova imagem
    if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] == 0) {
        $pasta_upload = "uploads/";
        if (!is_dir($pasta_upload)) mkdir($pasta_upload, 0777, true);

        $arquivo_tmp = $_FILES['imagem']['tmp_name'];
        $nome_arquivo = basename($_FILES['imagem']['name']);
        $extensao = strtolower(pathinfo($nome_arquivo, PATHINFO_EXTENSION));
        
        // Verifica se é uma imagem válida
        $tipos_permitidos = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (!in_array($extensao, $tipos_permitidos)) {
            echo "<div class='alert error'>Erro: Apenas imagens JPG, PNG, GIF e WebP são permitidas!</div>";
            exit;
        }
        
        // Verifica tamanho mínimo da imagem
        $info_imagem = getimagesize($arquivo_tmp);
        $largura_minima = 800;
        $altura_minima = 1200;
        
        if ($info_imagem[0] < $largura_minima || $info_imagem[1] < $altura_minima) {
            echo "<div class='alert error'>Erro: A imagem deve ter no mínimo {$largura_minima}x{$altura_minima} pixels. Sua imagem tem {$info_imagem[0]}x{$info_imagem[1]} pixels.</div>";
            exit;
        }
        
        $novo_nome = uniqid() . "." . $extensao;
        $caminho_final = $pasta_upload . $novo_nome;
        
        // Redimensiona a imagem para 1064x1596 (CORTANDO)
        if (redimensionarImagem($arquivo_tmp, $caminho_final, 1064, 1596)) {
            $imagem = $novo_nome;
            
            // Remove a imagem antiga se existir
            if ($imagem_atual && file_exists($pasta_upload . $imagem_atual)) {
                unlink($pasta_upload . $imagem_atual);
            }
        } else {
            echo "<div class='alert error'>Erro ao processar a imagem!</div>";
            exit;
        }
    }

    $nome = $_POST['nome'];
    $idioma = $_POST['idioma'];

    $sql = "UPDATE carrossel SET nome = :nome, imagem = :imagem, idioma = :idioma WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':nome'=>$nome, ':imagem'=>$imagem, ':idioma'=>$idioma, ':id'=>$id]);

    header("Location: ".$_SERVER['PHP_SELF']);
}

// ===================== DELETAR =====================
if (isset($_GET['acao']) && $_GET['acao'] == "deletar") {
    $id = $_GET['id'];
    
    // Busca a imagem para deletar do servidor
    $sql_select = "SELECT imagem FROM carrossel WHERE id = :id";
    $stmt_select = $pdo->prepare($sql_select);
    $stmt_select->execute([':id'=>$id]);
    $imagem = $stmt_select->fetchColumn();
    
    // Deleta do banco
    $sql = "DELETE FROM carrossel WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id'=>$id]);
    
    // Deleta a imagem do servidor
    if ($imagem && file_exists("uploads/" . $imagem)) {
        unlink("uploads/" . $imagem);
    }

    header("Location: ".$_SERVER['PHP_SELF']);
}

// ===================== PEGAR REGISTROS =====================
$sql = "SELECT * FROM carrossel";
$stmt = $pdo->query($sql);
$carrosseis = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>CRUD Carrossel</title>
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
            max-width: 1200px;
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
            min-height: 100px;
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
        
        .file-requirements {
            font-size: 12px;
            color: var(--warning);
            margin-top: 5px;
            font-style: italic;
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
            height: 120px; /* Ajustado para a proporção 1064x1596 */
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
        
        .alert.error {
            background: #f8d7da;
            color: #721c24;
            border-color: #f5c6cb;
        }
        
        .link-detalhes { 
            background: var(--success); 
            color: white; 
            padding: 8px 15px; 
            border-radius: 6px; 
            font-size: 14px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: all 0.3s;
        }
        
        .link-detalhes:hover {
            background: #3aa8d0;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
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
        
        .nav-buttons {
            display: flex;
            gap: 15px;
            margin-bottom: 30px;
            flex-wrap: wrap;
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
            
            table {
                font-size: 14px;
            }
            
            th, td {
                padding: 10px 8px;
            }
            
            .table-img {
                width: 60px;
                height: 90px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎬 CRUD Carrossel</h1>
            <p>Gerenciamento de conteúdo do carrossel</p>
        </div>
        
        <div class="content">
            <!-- NAVEGAÇÃO -->
            <div class="nav-buttons">
                <a href="indv_animes.php" class="btn btn-secondary">
                    📝 Gerenciar Detalhes dos Animes
                </a>
            </div>

            <!-- FORMULÁRIO INSERIR / EDITAR -->
            <?php
            if (isset($_GET['acao']) && $_GET['acao'] == "editar") {
                $id = $_GET['id'];
                $sql = "SELECT * FROM carrossel WHERE id = :id";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([':id'=>$id]);
                $registro = $stmt->fetch(PDO::FETCH_ASSOC);
                ?>
                <div class="card">
                    <h2>✏️ Editar Registro</h2>
                    <form method="post" enctype="multipart/form-data">
                        <input type="hidden" name="acao" value="editar">
                        <input type="hidden" name="id" value="<?= $registro['id'] ?>">
                        
                        <div class="form-group">
                            <label for="nome">Nome:</label>
                            <input type="text" name="nome" class="form-control" value="<?= htmlspecialchars($registro['nome']) ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="imagem">Imagem:</label>
                            <input type="file" name="imagem" class="form-control file-input" accept="image/*">
                            <div class="file-info">Imagem atual: <?= htmlspecialchars($registro['imagem']) ?></div>
                            <div class="file-requirements">📏 Dimensões recomendadas: 1064x1596 pixels (mínimo: 800x1200)</div>
                        </div>
                        
                        <div class="form-group">
                            <label for="idioma">Idioma:</label>
                            <textarea name="idioma" class="form-control" required><?= htmlspecialchars($registro['idioma']) ?></textarea>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            🔄 Atualizar
                        </button>
                    </form>
                    <a href="<?= $_SERVER['PHP_SELF'] ?>" class="cancel-link">
                        ❌ Cancelar edição
                    </a>
                </div>
            <?php } else { ?>
                <div class="card">
                    <h2>➕ Inserir Novo Registro</h2>
                    <form method="post" enctype="multipart/form-data">
                        <input type="hidden" name="acao" value="inserir">
                        
                        <div class="form-group">
                            <label for="nome">Nome:</label>
                            <input type="text" name="nome" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="imagem">Imagem:</label>
                            <input type="file" name="imagem" class="form-control file-input" accept="image/*" required>
                            <div class="file-requirements">📏 Dimensões recomendadas: 1064x1596 pixels (mínimo: 800x1200)</div>
                        </div>
                        
                        <div class="form-group">
                            <label for="idioma">Idioma:</label>
                            <textarea name="idioma" class="form-control" required></textarea>
                        </div>
                        
                        <button type="submit" class="btn btn-success">
                            ✅ Inserir
                        </button>
                    </form>
                </div>
            <?php } ?>

            <!-- TABELA DE REGISTROS -->
            <div class="card">
                <h2>📋 Registros Existentes</h2>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nome</th>
                            <th>Imagem</th>
                            <th>Idioma</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($carrosseis as $c) { ?>
                            <tr>
                                <td><?= htmlspecialchars($c['id']) ?></td>
                                <td><strong><?= htmlspecialchars($c['nome']) ?></strong></td>
                                <td>
                                    <img src="uploads/<?= htmlspecialchars($c['imagem']) ?>" class="table-img" alt="<?= htmlspecialchars($c['nome']) ?>">
                                </td>
                                <td><?= htmlspecialchars($c['idioma']) ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="?acao=editar&id=<?= $c['id'] ?>" class="btn btn-primary btn-sm">
                                            ✏️ Editar
                                        </a>
                                        <a href="?acao=deletar&id=<?= $c['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Tem certeza que deseja deletar este registro?')">
                                            🗑️ Deletar
                                        </a>
                                        <a href="indv_animes.php" class="link-detalhes">
                                            📖 Detalhes
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