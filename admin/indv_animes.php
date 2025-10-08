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

// --- FUNÇÃO AUXILIAR PARA UPLOAD MELHORADA ---
function handle_upload($file_key, $prefix, $pasta_upload, $current_file = NULL) {
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
    
    // Move o arquivo
    if (move_uploaded_file($file_array['tmp_name'], $pasta_upload . $novo_nome)) {
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
    $novo_nome_fundo = handle_upload('imagem_fundo', '_fundo', $PASTA_FUNDOS);
    $novo_nome_classificacao = handle_upload('classificacao', '_class', $PASTA_FUNDOS);
    
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
    $img_fundo = handle_upload('imagem_fundo', '_fundo_edit', $PASTA_FUNDOS, $detalhe_atual['imagem_fundo']);
    $classificacao_img = handle_upload('classificacao', '_class_edit', $PASTA_FUNDOS, $detalhe_atual['classificacao']);

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
        body { font-family: Arial, sans-serif; margin: 20px; }
        table { border-collapse: collapse; width: 100%; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        form { margin-top: 20px; padding: 15px; border: 1px solid #ccc; border-radius: 5px; }
        input[type=text], textarea, select { width: 100%; padding: 8px; margin-bottom: 10px; box-sizing: border-box; }
        input[type=submit] { padding: 10px 15px; background-color: #28a745; color: white; border: none; cursor: pointer; }
        .grid-campos { display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; }
        .mensagem { padding: 10px; margin-bottom: 15px; border-radius: 4px; }
        .sucesso { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .edicao { background-color: #fff3cd; color: #856404; border: 1px solid #ffeeba; }
        .erro { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6fb; }
        .required::after { content: " *"; color: red; }
    </style>
</head>
<body>
    <h1>Gerenciamento de Detalhes de Animes (indv_anime)</h1>
    <p><a href="index.php">Voltar para CRUD do Carrossel</a></p>

    <?php 
    if (isset($_GET['sucesso'])) {
        if ($_GET['sucesso'] == 1) {
            echo '<div class="mensagem sucesso">Detalhes do Anime inseridos com sucesso!</div>';
        } elseif ($_GET['sucesso'] == 2) {
            echo '<div class="mensagem sucesso">Detalhes do Anime atualizados com sucesso!</div>';
        } elseif ($_GET['sucesso'] == 3) {
            echo '<div class="mensagem erro">Detalhes do Anime deletados com sucesso!</div>';
        }
    }
    if (isset($_GET['erro']) && $_GET['erro'] == 'carrossel_nao_encontrado') {
        echo '<div class="mensagem erro">Erro: O ID do Anime Principal não foi encontrado. A inserção falhou.</div>';
    }
    ?>

    <hr>
    <?php if ($registro_detalhe_editar) : ?>
        <h2 class="edicao">1. Editar Detalhes do Anime #<?= $registro_detalhe_editar['id'] ?></h2>
        <form method="post" enctype="multipart/form-data">
            <input type="hidden" name="acao" value="editar_detalhes">
            <input type="hidden" name="id_detalhe" value="<?= $registro_detalhe_editar['id'] ?>">
            
            <label for="id_carrossel" class="required">**Anime Principal (Carrossel):**</label>
            <select name="id_carrossel" id="id_carrossel" required>
                <?php foreach ($carrosseis as $c) { ?>
                    <option value="<?= $c['id'] ?>" <?= ($c['id'] == $registro_detalhe_editar['id_carrossel']) ? 'selected' : '' ?>>
                        <?= $c['id'] ?> - <?= $c['nome'] ?> (<?= $c['idioma'] ?>)
                    </option>
                <?php } ?>
            </select>

            <div class="grid-campos">
                <div>
                    <label for="imagem_fundo" class="required">**Imagem de Fundo (Upload):**</label>
                    <input type="file" name="imagem_fundo" accept="image/*" required>
                    <small>Atual: **<?= $registro_detalhe_editar['imagem_fundo'] ?>**</small>
                </div>
                <div>
                    <label for="classificacao" class="required">**Classificação (Selo - Upload):**</label>
                    <input type="file" name="classificacao" accept="image/*" required>
                    <small>Atual: **<?= $registro_detalhe_editar['classificacao'] ?>**</small>
                </div>
            </div>
            
            <label for="descricao" class="required">**Descrição (Obrigatório):**</label>
            <textarea name="descricao" rows="5" required><?= $registro_detalhe_editar['descricao'] ?></textarea>

            <div class="grid-campos">
                <div><label for="genero" class="required">Gênero:</label><input type="text" name="genero" value="<?= $registro_detalhe_editar['genero'] ?>" required></div>
                <div><label for="classificacao_media" class="required">Classificação Média:</label><input type="text" name="classificacao_media" value="<?= $registro_detalhe_editar['classificacao_media'] ?>" required></div>
                <div><label for="audio" class="required">Áudio:</label><input type="text" name="audio" value="<?= $registro_detalhe_editar['audio'] ?>" required></div>
                <div><label for="legendas" class="required">Legendas:</label><input type="text" name="legendas" value="<?= $registro_detalhe_editar['legendas'] ?>" required></div>
                <div><label for="premios">Prêmios:</label><input type="text" name="premios" value="<?= $registro_detalhe_editar['premios'] ?>"></div>
                <div><label for="classificacao_conteudo" class="required">Classificação Conteúdo:</label><input type="text" name="classificacao_conteudo" value="<?= $registro_detalhe_editar['classificacao_conteudo'] ?>" required></div>
            </div>
            <br>
            <input type="submit" value="Atualizar Detalhes" style="background-color: #ffc107; color: black;">
        </form>
        <p><a href="<?= $_SERVER['PHP_SELF'] ?>">Cancelar Edição</a></p>

    <?php else : ?>
        <h2>1. Inserir Novos Detalhes</h2>
        <form method="post" enctype="multipart/form-data">
            <input type="hidden" name="acao" value="inserir_detalhes">
            
            <label for="id_carrossel" class="required">**Anime Principal (Carrossel):**</label>
            <select name="id_carrossel" id="id_carrossel" required>
                <option value="">-- Selecione o Anime --</option>
                <?php foreach ($carrosseis as $c) { ?>
                    <option value="<?= $c['id'] ?>">
                        <?= $c['id'] ?> - <?= $c['nome'] ?> (<?= $c['idioma'] ?>)
                    </option>
                <?php } ?>
            </select>
            
            <div class="grid-campos">
                <div>
                    <label for="imagem_fundo" class="required">**Imagem de Fundo (Upload):**</label>
                    <input type="file" name="imagem_fundo" accept="image/*" required>
                    <small>Fundo da página de detalhes.</small>
                </div>
                <div>
                    <label for="classificacao" class="required">**Classificação (Selo - Upload):**</label>
                    <input type="file" name="classificacao" accept="image/*" required>
                    <small>Selo de faixa etária (imagem).</small>
                </div>
            </div>
            
            <label for="descricao" class="required">**Descrição (Obrigatório):**</label>
            <textarea name="descricao" rows="5" required></textarea>

            <div class="grid-campos">
                <div><label for="genero" class="required">Gênero:</label><input type="text" name="genero" required></div>
                <div><label for="classificacao_media" class="required">Classificação Média:</label><input type="text" name="classificacao_media" required></div>
                <div><label for="audio" class="required">Áudio:</label><input type="text" name="audio" required></div>
                <div><label for="legendas" class="required">Legendas:</label><input type="text" name="legendas" required></div>
                <div><label for="premios">Prêmios:</label><input type="text" name="premios"></div>
                <div><label for="classificacao_conteudo" class="required">Classificação Conteúdo:</label><input type="text" name="classificacao_conteudo" required></div>
            </div>
            <br>

            <input type="submit" value="Inserir Detalhes do Anime">
        </form>
    <?php endif; ?>

    <hr>

    <h2>2. Detalhes Existentes (Tabela indv_anime)</h2>
    <table>
        <tr>
            <th>ID Detalhe</th>
            <th>Título (Copiado)</th>
            <th>Classificação (Selo)</th>
            <th>Descrição</th>
            <th>Imagem Fundo</th> 
            <th>Ações</th>
        </tr>
        <?php foreach ($detalhes_animes as $d) { ?>
            <tr>
                <td><?= $d['id'] ?></td>
                <td><?= $d['titulo_principal'] ?> (Copiado: **<?= $d['titulo'] ?>**)</td>
                <td>
                    <?php if ($d['classificacao']) : ?>
                        <img src="<?= $PASTA_FUNDOS . $d['classificacao'] ?>" width="40"/>
                    <?php else: ?>
                        N/A
                    <?php endif; ?>
                </td>
                <td><?= substr($d['descricao'], 0, 50) . (strlen($d['descricao']) > 50 ? '...' : '') ?></td>
                <td>
                    <?php if ($d['imagem_fundo']) : ?>
                        <img src="<?= $PASTA_FUNDOS . $d['imagem_fundo'] ?>" width="100"/>
                    <?php else: ?>
                        N/A
                    <?php endif; ?>
                </td>
                <td>
                    <a href="?acao=editar_detalhes&id=<?= $d['id'] ?>">Editar</a> |
                    <a href="?acao=deletar_detalhes&id=<?= $d['id'] ?>" onclick="return confirm('Deseja realmente deletar estes detalhes?')">Deletar</a>
                </td>
            </tr>
        <?php } ?>
    </table>
</body>
</html>