<?php
require "conexao.php";

// ===================== INSERIR =====================
if (isset($_POST['acao']) && $_POST['acao'] == "inserir") {
    $nome = $_POST['nome'];
    $idioma = $_POST['idioma'];

    // Tratamento do upload
    if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] == 0) {
        $pasta_upload = "uploads/"; // certifique-se de criar essa pasta com permissão de escrita
        if (!is_dir($pasta_upload)) mkdir($pasta_upload, 0777, true);

        $arquivo_tmp = $_FILES['imagem']['tmp_name'];
        $nome_arquivo = basename($_FILES['imagem']['name']);
        $extensao = strtolower(pathinfo($nome_arquivo, PATHINFO_EXTENSION));
        $novo_nome = uniqid() . "." . $extensao; // renomeia para evitar conflitos

        move_uploaded_file($arquivo_tmp, $pasta_upload . $novo_nome);
        
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
        echo "<p style='color:red'>Erro no upload da imagem!</p>";
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
        $pasta_upload = "uploads/"; // certifique-se de criar essa pasta com permissão de escrita
        if (!is_dir($pasta_upload)) mkdir($pasta_upload, 0777, true);

        $arquivo_tmp = $_FILES['imagem']['tmp_name'];
        $nome_arquivo = basename($_FILES['imagem']['name']);
        $extensao = strtolower(pathinfo($nome_arquivo, PATHINFO_EXTENSION));
        $novo_nome = uniqid() . "." . $extensao; // renomeia para evitar conflitos

        move_uploaded_file($arquivo_tmp, $pasta_upload . $novo_nome);
        
        $imagem = $novo_nome; // Usa a nova imagem apenas se foi enviada
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
    $sql = "DELETE FROM carrossel WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id'=>$id]);

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
    <title>CRUD carrossel</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        table { border-collapse: collapse; width: 100%; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        form { margin-top: 20px; padding: 15px; border: 1px solid #ccc; border-radius: 5px; }
        input[type=text], textarea, select { width: 100%; padding: 8px; margin-bottom: 10px; box-sizing: border-box; }
        input[type=submit] { padding: 10px 15px; background-color: #28a745; color: white; border: none; cursor: pointer; }
        a { text-decoration: none; color: blue; }
        .link-detalhes { 
            background-color: #007bff; 
            color: white; 
            padding: 5px 10px; 
            border-radius: 4px; 
            margin-left: 10px;
            font-size: 12px;
        }
        .link-detalhes:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <h1>CRUD carrossel</h1>
    
    <!-- LINK PARA O FORMULÁRIO DE DETALHES -->
    <p><a href="indv_animes.php" style="background-color: #6c757d; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px; display: inline-block; margin-bottom: 20px;">📝 Gerenciar Detalhes dos Animes</a></p>

    <!-- FORMULÁRIO INSERIR / EDITAR -->
<?php
// CORREÇÃO: mudar $_FILES para $_GET
if (isset($_GET['acao']) && $_GET['acao'] == "editar") {
    $id = $_GET['id'];
    $sql = "SELECT * FROM carrossel WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id'=>$id]);
    $registro = $stmt->fetch(PDO::FETCH_ASSOC);
    ?>
    <h2>Editar Registro</h2>
    <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="acao" value="editar">
        <input type="hidden" name="id" value="<?= $registro['id'] ?>">
        Nome: <input type="text" name="nome" value="<?= $registro['nome'] ?>" required>
        Imagem: <input type="file" name="imagem" accept="image/*">
        <small>Deixe em branco para manter a imagem atual: <?= $registro['imagem'] ?></small><br>
        Idioma: <input type="text" name="idioma" value="<?= $registro['idioma'] ?>" required>
        <input type="submit" value="Atualizar">
    </form>
    <hr>
    <a href="<?= $_SERVER['PHP_SELF'] ?>">Cancelar edição</a>
<?php } else { ?>
    <h2>Inserir Novo Registro</h2>
    <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="acao" value="inserir">
        Nome: <input type="text" name="nome" required>
        Imagem: <input type="file" name="imagem" accept="image/*" required>
        Idioma: <input type="text" name="idioma" required>
        <input type="submit" value="Inserir">
    </form>
<?php } ?>

    <!-- TABELA DE REGISTROS -->
    <h2>Registros Existentes</h2>
    <table>
        <tr>
            <th>ID</th>
            <th>Nome</th>
            <th>Imagem</th>
            <th>Idioma</th>
            <th>Ações</th>
        </tr>
        <?php foreach ($carrosseis as $c) { ?>
            <tr>
                <td><?= $c['id'] ?></td>
                <td><?= $c['nome'] ?></td>
                <td>
                    <img src="uploads/<?= $c['imagem'] ?>" width="100"/>
                </td>
                <td><?= $c['idioma'] ?></td>
                <td>
                    <a href="?acao=editar&id=<?= $c['id'] ?>">Editar</a> |
                    <a href="?acao=deletar&id=<?= $c['id'] ?>" onclick="return confirm('Deseja realmente deletar?')">Deletar</a> |
                    <a href="indv_animes.php" class="link-detalhes">Detalhes</a>
                </td>
            </tr>
        <?php } ?>
    </table>
</body>
</html>