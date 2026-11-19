<?php

session_start();

/* =========================================================
   CONEXÃO COM O BANCO
========================================================= */

$servidor = "localhost";
$usuario = "root";
$senha = "";
$banco = "freaky";

$conexao = new mysqli(
    $servidor,
    $usuario,
    $senha,
    $banco
);

if ($conexao->connect_error) {
    die("Erro na conexão com o banco de dados: " . $conexao->connect_error);
}

$conexao->set_charset("utf8");


/* =========================================================
   CRIA O CARRINHO
========================================================= */

if (!isset($_SESSION["carrinho"])) {
    $_SESSION["carrinho"] = [];
}


/* =========================================================
   ADICIONAR PRODUTO
========================================================= */

if (isset($_GET["adicionar"])) {

    $id_produto = intval($_GET["adicionar"]);

    if ($id_produto > 0) {

        if (isset($_SESSION["carrinho"][$id_produto])) {

            $_SESSION["carrinho"][$id_produto]++;

        } else {

            $_SESSION["carrinho"][$id_produto] = 1;

        }

    }

    header("Location: carrinho.php");
    exit;
}


/* =========================================================
   AUMENTAR QUANTIDADE
========================================================= */

if (isset($_GET["aumentar"])) {

    $id_produto = intval($_GET["aumentar"]);

    if (isset($_SESSION["carrinho"][$id_produto])) {

        $_SESSION["carrinho"][$id_produto]++;

    }

    header("Location: carrinho.php");
    exit;
}


/* =========================================================
   DIMINUIR QUANTIDADE
========================================================= */

if (isset($_GET["diminuir"])) {

    $id_produto = intval($_GET["diminuir"]);

    if (isset($_SESSION["carrinho"][$id_produto])) {

        $_SESSION["carrinho"][$id_produto]--;

        if ($_SESSION["carrinho"][$id_produto] <= 0) {

            unset($_SESSION["carrinho"][$id_produto]);

        }

    }

    header("Location: carrinho.php");
    exit;
}


/* =========================================================
   REMOVER PRODUTO
========================================================= */

if (isset($_GET["remover"])) {

    $id_produto = intval($_GET["remover"]);

    if (isset($_SESSION["carrinho"][$id_produto])) {

        unset($_SESSION["carrinho"][$id_produto]);

    }

    header("Location: carrinho.php");
    exit;
}


/* =========================================================
   LIMPAR CARRINHO
========================================================= */

if (isset($_GET["limpar"])) {

    $_SESSION["carrinho"] = [];

    header("Location: carrinho.php");
    exit;
}


/* =========================================================
   BUSCAR PRODUTOS
========================================================= */

$produtos_carrinho = [];

$total = 0;

$quantidade_total = 0;


foreach ($_SESSION["carrinho"] as $id => $quantidade) {

    $id = intval($id);

    $sql = "SELECT * FROM Produtos WHERE id_produto = $id";

    $resultado = $conexao->query($sql);

    if ($resultado && $resultado->num_rows > 0) {

        $produto = $resultado->fetch_assoc();

        $produto["quantidade"] = $quantidade;

        $subtotal = $produto["preco"] * $quantidade;

        $produto["subtotal"] = $subtotal;

        $produtos_carrinho[] = $produto;

        $total += $subtotal;

        $quantidade_total += $quantidade;
    }
}


/* =========================================================
   QUANTIDADE PARA O HEADER
========================================================= */

$quantidade_carrinho = $quantidade_total;


/* =========================================================
   USUÁRIO
========================================================= */

$usuario_logado = false;
$nome_usuario = "";

if (isset($_SESSION["id_cliente"])) {

    $usuario_logado = true;

    if (isset($_SESSION["nome_usuario"])) {

        $nome_usuario = $_SESSION["nome_usuario"];

    }

}

?>

<!DOCTYPE html>

<html lang="pt-br">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Carrinho | Freaky Suplements</title>


<style>

/* =========================================================
   RESET
========================================================= */

* {

    margin: 0;
    padding: 0;
    box-sizing: border-box;

}


/* =========================================================
   BODY
========================================================= */

body {

    background: #080808;

    color: #ffffff;

    font-family: Arial, Helvetica, sans-serif;

    min-height: 100vh;

}


/* =========================================================
   SELEÇÃO
========================================================= */

::selection {

    background: #ff2525;

    color: #ffffff;

}


/* =========================================================
   TOPO
========================================================= */

.topo {

    width: 100%;

    height: 34px;

    background: #ff2525;

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 12px;

    font-weight: bold;

    letter-spacing: .3px;

}


/* =========================================================
   HEADER
========================================================= */

header {

    width: 100%;

    background: rgba(13,13,13,.96);

    border-bottom: 1px solid #242424;

    position: sticky;

    top: 0;

    z-index: 1000;

    backdrop-filter: blur(15px);

}


.header-principal {

    max-width: 1400px;

    margin: auto;

    padding: 18px 5%;

    display: flex;

    align-items: center;

    gap: 35px;

}


/* =========================================================
   LOGO
========================================================= */

.logo {

    display: flex;

    align-items: center;

    flex-shrink: 0;

}

.logo img {

    width: 150px;

    max-height: 55px;

    object-fit: contain;

}


/* =========================================================
   BUSCA
========================================================= */

.busca {

    flex: 1;

    max-width: 650px;

    position: relative;

}

.busca input {

    width: 100%;

    height: 45px;

    background: #111111;

    border: 1px solid #292929;

    border-radius: 8px;

    color: #ffffff;

    padding: 0 50px 0 18px;

    outline: none;

    font-size: 13px;

    transition: .3s;

}

.busca input:focus {

    border-color: #ff2525;

    box-shadow: 0 0 0 3px rgba(255,37,37,.08);

}

.busca input::placeholder {

    color: #666666;

}

.icone-busca {

    position: absolute;

    right: 17px;

    top: 50%;

    transform: translateY(-50%);

    color: #777777;

    font-size: 18px;

}


/* =========================================================
   AÇÕES DO HEADER
========================================================= */

.acoes-header {

    display: flex;

    align-items: center;

    gap: 25px;

    margin-left: auto;

}

.acao-header {

    color: #cccccc;

    text-decoration: none;

    font-size: 12px;

    display: flex;

    align-items: center;

    gap: 8px;

    transition: .3s;

    white-space: nowrap;

}

.acao-header:hover {

    color: #ff2525;

}

.icone-acao {

    font-size: 20px;

}


/* =========================================================
   CARRINHO HEADER
========================================================= */

.acao-carrinho {

    position: relative;

}

.contador-carrinho {

    position: absolute;

    top: -9px;

    right: -12px;

    min-width: 18px;

    height: 18px;

    padding: 0 4px;

    border-radius: 20px;

    background: #ff2525;

    color: #ffffff;

    font-size: 10px;

    font-weight: bold;

    display: flex;

    align-items: center;

    justify-content: center;

}


/* =========================================================
   NAVEGAÇÃO
========================================================= */

nav {

    border-top: 1px solid #181818;

    background: #101010;

}

.menu {

    max-width: 1400px;

    margin: auto;

    padding: 0 5%;

    height: 48px;

    display: flex;

    align-items: center;

    gap: 35px;

}

.menu a {

    height: 48px;

    display: flex;

    align-items: center;

    color: #999999;

    text-decoration: none;

    font-size: 12px;

    font-weight: bold;

    position: relative;

    transition: .3s;

}

.menu a:hover,

.menu a.ativo {

    color: #ffffff;

}

.menu a.ativo::after {

    content: "";

    position: absolute;

    left: 0;

    right: 0;

    bottom: 0;

    height: 2px;

    background: #ff2525;

}


/* =========================================================
   CONTAINER
========================================================= */

.container {

    width: 90%;

    max-width: 1350px;

    margin: 55px auto 90px;

}


/* =========================================================
   CABEÇALHO DA PÁGINA
========================================================= */

.pagina-topo {

    margin-bottom: 35px;

}

.pagina-topo .mini {

    color: #ff2525;

    font-size: 11px;

    font-weight: bold;

    text-transform: uppercase;

    letter-spacing: 2px;

    margin-bottom: 10px;

}

.pagina-topo h1 {

    font-size: 42px;

    line-height: 1.1;

    margin-bottom: 12px;

}

.pagina-topo h1 span {

    color: #ff2525;

}

.pagina-topo p {

    color: #777777;

    font-size: 14px;

}


/* =========================================================
   CARRINHO VAZIO
========================================================= */

.vazio {

    min-height: 450px;

    background:

        radial-gradient(
            circle at 50% 0%,
            rgba(255,37,37,.08),
            transparent 45%
        ),

        #101010;

    border: 1px solid #292929;

    border-radius: 18px;

    display: flex;

    align-items: center;

    justify-content: center;

    text-align: center;

    padding: 60px 25px;

}

.vazio-conteudo {

    max-width: 500px;

}

.vazio-icone {

    width: 95px;

    height: 95px;

    margin: 0 auto 25px;

    border-radius: 50%;

    background: #171717;

    border: 1px solid #303030;

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 42px;

    box-shadow: 0 15px 45px rgba(0,0,0,.35);

}

.vazio h2 {

    font-size: 28px;

    margin-bottom: 12px;

}

.vazio p {

    color: #777777;

    font-size: 14px;

    line-height: 1.7;

    margin-bottom: 28px;

}


/* =========================================================
   BOTÃO PRINCIPAL
========================================================= */

.botao-principal {

    display: inline-flex;

    align-items: center;

    justify-content: center;

    gap: 10px;

    min-height: 48px;

    padding: 0 27px;

    background: #ff2525;

    color: #ffffff;

    text-decoration: none;

    border-radius: 7px;

    font-size: 12px;

    font-weight: bold;

    letter-spacing: .5px;

    transition: .3s;

    box-shadow: 0 10px 30px rgba(255,37,37,.15);

}

.botao-principal:hover {

    background: #d90000;

    transform: translateY(-2px);

    box-shadow: 0 15px 35px rgba(255,37,37,.22);

}


/* =========================================================
   LAYOUT DO CARRINHO
========================================================= */

.carrinho-layout {

    display: grid;

    grid-template-columns: minmax(0, 1fr) 380px;

    gap: 25px;

    align-items: start;

}


/* =========================================================
   CABEÇALHO DA LISTA
========================================================= */

.lista-topo {

    background: #101010;

    border: 1px solid #242424;

    border-radius: 15px 15px 0 0;

    padding: 20px 24px;

    display: flex;

    align-items: center;

    justify-content: space-between;

}

.lista-topo h2 {

    font-size: 17px;

}

.lista-topo span {

    color: #777777;

    font-size: 12px;

}


/* =========================================================
   LISTA
========================================================= */

.lista {

    display: flex;

    flex-direction: column;

    gap: 12px;

    margin-top: 12px;

}


/* =========================================================
   PRODUTO
========================================================= */

.produto {

    background: #101010;

    border: 1px solid #242424;

    border-radius: 15px;

    padding: 18px;

    display: grid;

    grid-template-columns: 105px minmax(150px,1fr) auto auto auto;

    align-items: center;

    gap: 20px;

    transition: .3s;

}

.produto:hover {

    border-color: #3a3a3a;

    transform: translateY(-2px);

    box-shadow: 0 15px 40px rgba(0,0,0,.25);

}


/* =========================================================
   IMAGEM
========================================================= */

.produto-imagem {

    width: 105px;

    height: 105px;

    background:

        radial-gradient(
            circle,
            rgba(255,37,37,.08),
            transparent 65%
        ),

        #080808;

    border: 1px solid #222222;

    border-radius: 12px;

    display: flex;

    align-items: center;

    justify-content: center;

    overflow: hidden;

}

.produto-imagem img {

    width: 90px;

    height: 90px;

    object-fit: contain;

    transition: .3s;

}

.produto:hover .produto-imagem img {

    transform: scale(1.06);

}


/* =========================================================
   INFORMAÇÕES
========================================================= */

.informacoes {

    min-width: 0;

}

.categoria {

    display: inline-block;

    color: #ff2525;

    font-size: 10px;

    font-weight: bold;

    text-transform: uppercase;

    letter-spacing: 1px;

    margin-bottom: 7px;

}

.informacoes h2 {

    color: #ffffff;

    font-size: 17px;

    line-height: 1.3;

    margin-bottom: 7px;

}

.descricao {

    color: #666666;

    font-size: 12px;

    line-height: 1.5;

    max-width: 400px;

}

.preco-unitario {

    margin-top: 10px;

    color: #999999;

    font-size: 11px;

}

.preco-unitario strong {

    color: #dddddd;

}


/* =========================================================
   QUANTIDADE
========================================================= */

.quantidade-area {

    text-align: center;

}

.quantidade-label {

    display: block;

    color: #666666;

    font-size: 10px;

    text-transform: uppercase;

    margin-bottom: 8px;

}

.quantidade {

    display: flex;

    align-items: center;

    justify-content: center;

    background: #080808;

    border: 1px solid #292929;

    border-radius: 8px;

    overflow: hidden;

}

.quantidade a {

    width: 34px;

    height: 36px;

    display: flex;

    align-items: center;

    justify-content: center;

    color: #ffffff;

    background: transparent;

    text-decoration: none;

    font-size: 17px;

    transition: .2s;

}

.quantidade a:hover {

    background: #ff2525;

}

.numero {

    width: 34px;

    text-align: center;

    color: #ffffff;

    font-size: 13px;

    font-weight: bold;

}


/* =========================================================
   SUBTOTAL
========================================================= */

.subtotal {

    min-width: 115px;

    text-align: right;

}

.subtotal small {

    display: block;

    color: #666666;

    font-size: 10px;

    text-transform: uppercase;

    margin-bottom: 6px;

}

.subtotal strong {

    color: #ffffff;

    font-size: 16px;

}


/* =========================================================
   REMOVER
========================================================= */

.remover {

    width: 36px;

    height: 36px;

    display: flex;

    align-items: center;

    justify-content: center;

    color: #777777;

    background: #181818;

    border: 1px solid #292929;

    border-radius: 8px;

    text-decoration: none;

    font-size: 15px;

    transition: .3s;

}

.remover:hover {

    color: #ffffff;

    background: #ff2525;

    border-color: #ff2525;

}


/* =========================================================
   RESUMO
========================================================= */

.resumo {

    background:

        linear-gradient(
            145deg,
            #151515,
            #0e0e0e
        );

    border: 1px solid #292929;

    border-radius: 17px;

    padding: 28px;

    position: sticky;

    top: 140px;

    box-shadow: 0 20px 60px rgba(0,0,0,.3);

}

.resumo-titulo {

    font-size: 18px;

    margin-bottom: 25px;

    padding-bottom: 18px;

    border-bottom: 1px solid #242424;

}

.resumo-linha {

    display: flex;

    justify-content: space-between;

    align-items: center;

    color: #777777;

    font-size: 13px;

    margin-bottom: 15px;

}

.resumo-linha strong {

    color: #dddddd;

}

.resumo-linha.frete strong {

    color: #54d68a;

}

.linha-total {

    border-top: 1px solid #292929;

    margin-top: 20px;

    padding-top: 20px;

    display: flex;

    align-items: flex-end;

    justify-content: space-between;

}

.linha-total span {

    color: #999999;

    font-size: 12px;

}

.valor-total {

    color: #ff2525;

    font-size: 30px;

    font-weight: bold;

}

.finalizar {

    width: 100%;

    height: 52px;

    margin-top: 22px;

    display: flex;

    align-items: center;

    justify-content: center;

    background: #ff2525;

    color: #ffffff;

    text-decoration: none;

    border-radius: 8px;

    font-size: 12px;

    font-weight: bold;

    letter-spacing: .7px;

    transition: .3s;

    box-shadow: 0 12px 30px rgba(255,37,37,.16);

}

.finalizar:hover {

    background: #d90000;

    transform: translateY(-2px);

}

.continuar {

    width: 100%;

    height: 45px;

    margin-top: 10px;

    display: flex;

    align-items: center;

    justify-content: center;

    color: #999999;

    border: 1px solid #292929;

    border-radius: 8px;

    text-decoration: none;

    font-size: 11px;

    transition: .3s;

}

.continuar:hover {

    color: #ffffff;

    border-color: #444444;

}

.limpar {

    display: block;

    width: fit-content;

    margin: 20px auto 0;

    color: #666666;

    text-decoration: none;

    font-size: 10px;

    transition: .3s;

}

.limpar:hover {

    color: #ff2525;

}


/* =========================================================
   BENEFÍCIOS
========================================================= */

.beneficios {

    margin-top: 25px;

    display: grid;

    grid-template-columns: repeat(3,1fr);

    gap: 12px;

}

.beneficio {

    background: #101010;

    border: 1px solid #242424;

    border-radius: 12px;

    padding: 17px;

}

.beneficio-icone {

    font-size: 19px;

    margin-bottom: 10px;

}

.beneficio h3 {

    font-size: 12px;

    margin-bottom: 5px;

}

.beneficio p {

    color: #666666;

    font-size: 10px;

    line-height: 1.5;

}


/* =========================================================
   RODAPÉ
========================================================= */

footer {

    background: #0d0d0d;

    border-top: 1px solid #242424;

    margin-top: 80px;

}

.footer-conteudo {

    max-width: 1350px;

    width: 90%;

    margin: auto;

    padding: 55px 0 40px;

    display: grid;

    grid-template-columns: 1.5fr 1fr 1fr 1fr;

    gap: 45px;

}

.footer-logo img {

    width: 145px;

    margin-bottom: 18px;

}

.footer-logo p {

    color: #666666;

    font-size: 12px;

    line-height: 1.7;

    max-width: 300px;

}

.footer-col h3 {

    font-size: 12px;

    text-transform: uppercase;

    letter-spacing: 1px;

    margin-bottom: 18px;

}

.footer-col a {

    display: block;

    color: #666666;

    text-decoration: none;

    font-size: 11px;

    margin-bottom: 11px;

    transition: .3s;

}

.footer-col a:hover {

    color: #ff2525;

}

.footer-bottom {

    border-top: 1px solid #1d1d1d;

    text-align: center;

    padding: 20px;

    color: #555555;

    font-size: 10px;

}


/* =========================================================
   RESPONSIVIDADE
========================================================= */

@media (max-width: 1100px) {

    .header-principal {

        flex-wrap: wrap;

    }

    .busca {

        order: 3;

        max-width: none;

        width: 100%;

    }

    .carrinho-layout {

        grid-template-columns: 1fr;

    }

    .resumo {

        position: static;

    }

    .produto {

        grid-template-columns: 90px 1fr auto;

    }

    .produto-imagem {

        width: 90px;

        height: 90px;

    }

    .quantidade-area {

        grid-column: 3;

    }

    .subtotal {

        grid-column: 2;

        text-align: left;

    }

    .remover {

        grid-column: 3;

        grid-row: 2;

    }

}


@media (max-width: 750px) {

    .header-principal {

        padding: 15px 5%;

    }

    .acoes-header {

        gap: 12px;

    }

    .acao-header span.texto-acao {

        display: none;

    }

    .menu {

        overflow-x: auto;

        gap: 25px;

    }

    .menu::-webkit-scrollbar {

        display: none;

    }

    .pagina-topo h1 {

        font-size: 32px;

    }

    .produto {

        display: grid;

        grid-template-columns: 80px 1fr auto;

        gap: 14px;

        padding: 14px;

    }

    .produto-imagem {

        width: 80px;

        height: 80px;

    }

    .produto-imagem img {

        width: 68px;

        height: 68px;

    }

    .informacoes h2 {

        font-size: 14px;

    }

    .descricao {

        display: none;

    }

    .quantidade-area {

        grid-column: 2;

        grid-row: 2;

        justify-self: start;

    }

    .subtotal {

        grid-column: 3;

        grid-row: 2;

        align-self: center;

    }

    .remover {

        grid-column: 3;

        grid-row: 1;

    }

    .beneficios {

        grid-template-columns: 1fr;

    }

    .footer-conteudo {

        grid-template-columns: 1fr 1fr;

        gap: 30px;

    }

}


@media (max-width: 480px) {

    .topo {

        font-size: 10px;

    }

    .logo img {

        width: 125px;

    }

    .acoes-header {

        margin-left: auto;

    }

    .acao-header {

        font-size: 17px;

    }

    .container {

        width: 92%;

        margin-top: 35px;

    }

    .pagina-topo h1 {

        font-size: 28px;

    }

    .lista-topo {

        padding: 17px;

    }

    .produto {

        grid-template-columns: 70px 1fr auto;

    }

    .produto-imagem {

        width: 70px;

        height: 70px;

    }

    .produto-imagem img {

        width: 58px;

        height: 58px;

    }

    .subtotal strong {

        font-size: 14px;

    }

    .resumo {

        padding: 22px;

    }

    .valor-total {

        font-size: 26px;

    }

    .footer-conteudo {

        grid-template-columns: 1fr;

    }

}

</style>

</head>


<body>


<!-- =========================================================
     TOPO
========================================================= -->

<div class="topo">

    FRETE GRÁTIS PARA COMPRAS ACIMA DE R$100

</div>


<!-- =========================================================
     HEADER
========================================================= -->

<header>

    <div class="header-principal">


        <a href="index.php" class="logo">

            <img
                src="img/logo.png"
                alt="Freaky Suplements"
            >

        </a>


        <div class="busca">

            <input
                type="text"
                id="campoBusca"
                placeholder="O que você está procurando?"
                autocomplete="off"
            >

            <span class="icone-busca">

                🔍

            </span>

        </div>


        <div class="acoes-header">


            <a
                href="rastrear.php"
                class="acao-header"
            >

                <span class="icone-acao">🚚</span>

                <span class="texto-acao">
                    Rastrear Pedido
                </span>

            </a>


            <a
                href="carrinho.php"
                class="acao-header acao-carrinho"
            >

                <span class="icone-acao">🛒</span>

                <span class="texto-acao">
                    Carrinho
                </span>

                <span class="contador-carrinho">

                    <?php echo $quantidade_carrinho; ?>

                </span>

            </a>


        </div>

    </div>


    <!-- =====================================================
         MENU
    ====================================================== -->

    <nav>

        <div class="menu">

            <a href="index.php">
                Início
            </a>

            <a href="index.php#catalogo">
                Produtos
            </a>

            <a href="index.php#sobre">
                Sobre nós
            </a>

            <a
                href="carrinho.php"
                class="ativo"
            >
                Suas compras
            </a>

            <a href="index.php#suporte">
                Suporte
            </a>

        </div>

    </nav>

</header>


<!-- =========================================================
     CONTEÚDO
========================================================= -->

<main class="container">


    <section class="pagina-topo">

        <div class="mini">
            Sua compra
        </div>

        <h1>
            Seu <span>carrinho.</span>
        </h1>

        <p>
            Confira seus produtos antes de finalizar o pedido.
        </p>

    </section>


<?php if (empty($produtos_carrinho)): ?>


    <!-- =====================================================
         CARRINHO VAZIO
    ====================================================== -->

    <section class="vazio">

        <div class="vazio-conteudo">

            <div class="vazio-icone">
                🛒
            </div>

            <h2>
                Seu carrinho está vazio
            </h2>

            <p>
                Você ainda não adicionou nenhum produto.
                Explore nossa loja e encontre os produtos
                ideais para sua rotina.
            </p>

            <a
                href="index.php#catalogo"
                class="botao-principal"
            >

                EXPLORAR PRODUTOS

                <span>→</span>

            </a>

        </div>

    </section>


<?php else: ?>


    <!-- =====================================================
         CARRINHO COM PRODUTOS
    ====================================================== -->

    <div class="carrinho-layout">


        <!-- =================================================
             LISTA
        ================================================== -->

        <section>


            <div class="lista-topo">

                <h2>
                    Produtos selecionados
                </h2>

                <span>
                    <?php echo $quantidade_total; ?>
                    item(ns)
                </span>

            </div>


            <div class="lista">


<?php foreach ($produtos_carrinho as $produto): ?>


                <article class="produto">


                    <!-- IMAGEM -->

                    <div class="produto-imagem">

                        <img
                            src="img/<?php
                                echo htmlspecialchars(
                                    $produto["imagem"]
                                );
                            ?>"
                            alt="<?php
                                echo htmlspecialchars(
                                    $produto["nome"]
                                );
                            ?>"
                        >

                    </div>


                    <!-- INFORMAÇÕES -->

                    <div class="informacoes">


                        <div class="categoria">

                            <?php
                                echo htmlspecialchars(
                                    $produto["categoria"]
                                );
                            ?>

                        </div>


                        <h2>

                            <?php
                                echo htmlspecialchars(
                                    $produto["nome"]
                                );
                            ?>

                        </h2>


                        <div class="descricao">

                            <?php

                            if (
                                !empty(
                                    $produto["descricao"]
                                )
                            ) {

                                echo htmlspecialchars(
                                    $produto["descricao"]
                                );

                            } else {

                                echo "Produto Freaky Suplements.";

                            }

                            ?>

                        </div>


                        <div class="preco-unitario">

                            Preço unitário:

                            <strong>

                                R$

                                <?php

                                echo number_format(
                                    $produto["preco"],
                                    2,
                                    ",",
                                    "."
                                );

                                ?>

                            </strong>

                        </div>


                    </div>


                    <!-- QUANTIDADE -->

                    <div class="quantidade-area">

                        <span class="quantidade-label">
                            Quantidade
                        </span>


                        <div class="quantidade">


                            <a
                                href="carrinho.php?diminuir=<?php
                                    echo intval(
                                        $produto["id_produto"]
                                    );
                                ?>"
                                title="Diminuir"
                            >
                                −
                            </a>


                            <span class="numero">

                                <?php
                                    echo intval(
                                        $produto["quantidade"]
                                    );
                                ?>

                            </span>


                            <a
                                href="carrinho.php?aumentar=<?php
                                    echo intval(
                                        $produto["id_produto"]
                                    );
                                ?>"
                                title="Aumentar"
                            >
                                +
                            </a>


                        </div>

                    </div>


                    <!-- SUBTOTAL -->

                    <div class="subtotal">

                        <small>
                            Subtotal
                        </small>

                        <strong>

                            R$

                            <?php

                            echo number_format(
                                $produto["subtotal"],
                                2,
                                ",",
                                "."
                            );

                            ?>

                        </strong>

                    </div>


                    <!-- REMOVER -->

                    <a
                        href="carrinho.php?remover=<?php
                            echo intval(
                                $produto["id_produto"]
                            );
                        ?>"
                        class="remover"
                        title="Remover produto"
                    >

                        ✕

                    </a>


                </article>


<?php endforeach; ?>


            </div>


        </section>


        <!-- =================================================
             RESUMO
        ================================================== -->

        <aside class="resumo">


            <h2 class="resumo-titulo">
                Resumo da compra
            </h2>


            <div class="resumo-linha">

                <span>
                    Produtos
                </span>

                <strong>

                    <?php echo $quantidade_total; ?>

                    <?php
                        echo $quantidade_total == 1
                            ? " item"
                            : " itens";
                    ?>

                </strong>

            </div>


            <div class="resumo-linha">

                <span>
                    Subtotal
                </span>

                <strong>

                    R$

                    <?php

                    echo number_format(
                        $total,
                        2,
                        ",",
                        "."
                    );

                    ?>

                </strong>

            </div>


            <div class="resumo-linha frete">

                <span>
                    Frete
                </span>

                <strong>
                    Grátis
                </strong>

            </div>


            <div class="linha-total">

                <span>
                    Total
                </span>

                <div class="valor-total">

                    R$

                    <?php

                    echo number_format(
                        $total,
                        2,
                        ",",
                        "."
                    );

                    ?>

                </div>

            </div>


            <a
                href="finalizar.php"
                class="finalizar"
            >

                FINALIZAR PEDIDO

                <span>
                    →
                </span>

            </a>


            <a
                href="index.php#catalogo"
                class="continuar"
            >

                ← Continuar comprando

            </a>


            <a
                href="carrinho.php?limpar=1"
                class="limpar"
                onclick="
                    return confirm(
                        'Tem certeza que deseja limpar o carrinho?'
                    );
                "
            >

                Limpar carrinho

            </a>


        </aside>


    </div>


    <!-- =====================================================
         BENEFÍCIOS
    ====================================================== -->

    <section class="beneficios">


        <div class="beneficio">

            <div class="beneficio-icone">
                🔒
            </div>

            <h3>
                Compra segura
            </h3>

            <p>
                Seus dados são tratados com segurança
                durante o processo de compra.
            </p>

        </div>


        <div class="beneficio">

            <div class="beneficio-icone">
                🚚
            </div>

            <h3>
                Entrega
            </h3>

            <p>
                Acompanhe seu pedido depois da
                finalização da compra.
            </p>

        </div>


        <div class="beneficio">

            <div class="beneficio-icone">
                ⚡
            </div>

            <h3>
                Processo rápido
            </h3>

            <p>
                Finalize seu pedido de forma simples
                e rápida.
            </p>

        </div>


    </section>


<?php endif; ?>


</main>


<!-- =========================================================
     RODAPÉ
========================================================= -->

<footer>


    <div class="footer-conteudo">


        <div class="footer-logo">

            <img
                src="img/logo.png"
                alt="Freaky Suplements"
            >

            <p>
                Freaky Suplements. Produtos para quem
                leva sua rotina e seus objetivos a sério.
            </p>

        </div>


        <div class="footer-col">

            <h3>
                Loja
            </h3>

            <a href="index.php">
                Início
            </a>

            <a href="index.php#catalogo">
                Produtos
            </a>

            <a href="carrinho.php">
                Carrinho
            </a>

        </div>


        <div class="footer-col">

            <h3>
                Atendimento
            </h3>

            <a href="rastrear.php">
                Rastrear pedido
            </a>

            <a href="index.php#suporte">
                Suporte
            </a>

            <a href="index.php#sobre">
                Sobre nós
            </a>

        </div>


        <div class="footer-col">

            <h3>
                Conta
            </h3>


<?php if ($usuario_logado): ?>

            <a href="#">
                Olá,
                <?php
                    echo htmlspecialchars(
                        $nome_usuario
                    );
                ?>
            </a>

            <a href="logout.php">
                Sair da conta
            </a>

<?php else: ?>

            <a href="login.php">
                Entrar
            </a>

            <a href="cadastro.php">
                Criar conta
            </a>

<?php endif; ?>


        </div>


    </div>


    <div class="footer-bottom">

        © 2026 Freaky Suplements.
        Todos os direitos reservados.

    </div>


</footer>


</body>

</html>
