<?php

session_start();

// Guarda o carrinho antes de encerrar a sessão
$carrinho = $_SESSION["carrinho"] ?? [];

// Limpa os dados da sessão
$_SESSION = [];

// Mantém o carrinho disponível depois do logout
$_SESSION["carrinho"] = $carrinho;

// Encerra a sessão
session_destroy();

// Inicia uma nova sessão para manter o carrinho
session_start();
$_SESSION["carrinho"] = $carrinho;

// Volta para a página inicial
header("Location: index.php");
exit;
?>
