<?php
$host = "localhost";
$usuario = "root";
$senha = "";
$banco = "animes";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$banco;charset=utf8", $usuario, $senha);
    // Configura para lançar exceções em caso de erro
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Conexão realizada com sucesso!";
} catch(PDOException $e) {
    echo "Falha na conexão: " . $e->getMessage();
}
?>
