
<?php

$servidor = "localhost";
$usuario = "root";
$senha = "";
$banco = "freaky";

// Criar conexão
$conexao = new mysqli($servidor, $usuario, $senha, $banco);

// Verificar conexão
if ($conexao->connect_error) {
    die("Erro ao conectar com o banco de dados: " . $conexao->connect_error);
}

// Definir caracteres
$conexao->set_charset("utf8mb4");

?>

