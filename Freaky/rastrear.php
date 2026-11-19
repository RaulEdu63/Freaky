<?php

session_start();

include "conexao.php";


/* =========================================================
   VERIFICAR LOGIN
========================================================= */

if (!isset($_SESSION["id_cliente"])) {

    header("Location: login.php");

    exit;
}


$id_cliente = intval(
    $_SESSION["id_cliente"]
);


/* =========================================================
   CANCELAR PEDIDO
========================================================= */

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["cancelar_pedido"])
) {

    $id_pedido = intval(
        $_POST["id_pedido"]
    );


    $cancelar = $conexao->prepare("
        UPDATE pedidos
        SET status_pedido = 'Cancelado'
        WHERE id_pedido = ?
        AND id_cliente = ?
        AND (
            status_pedido IS NULL
            OR status_pedido NOT IN (
                'Enviado',
                'Saiu para entrega',
                'Entregue',
                'Cancelado'
            )
        )
    ");


    $cancelar->bind_param(
        "ii",
        $id_pedido,
        $id_cliente
    );


    $cancelar->execute();


    header("Location: rastrear.php");

    exit;
}


/* =========================================================
   BUSCAR ÚLTIMO PEDIDO
========================================================= */

$sql = $conexao->prepare("
    SELECT
        id_pedido,
        status_pedido,
        valor_total
    FROM pedidos
    WHERE id_cliente = ?
    ORDER BY id_pedido DESC
    LIMIT 1
");


$sql->bind_param(
    "i",
    $id_cliente
);


$sql->execute();


$resultado = $sql->get_result();


$pedido = $resultado->fetch_assoc();


/* =========================================================
   BUSCAR ITENS DO PEDIDO
========================================================= */

$itens = [];


if ($pedido) {

    $id_pedido = intval(
        $pedido["id_pedido"]
    );


    $sql_itens = $conexao->prepare("
        SELECT
            ip.quantidade,
            ip.preco_unitario,
            p.nome,
            p.imagem
        FROM itens_pedido ip
        INNER JOIN produtos p
            ON p.id_produto = ip.id_produto
        WHERE ip.id_pedido = ?
    ");


    $sql_itens->bind_param(
        "i",
        $id_pedido
    );


    $sql_itens->execute();


    $resultado_itens =
        $sql_itens->get_result();


    while (
        $item =
        $resultado_itens->fetch_assoc()
    ) {

        $itens[] = $item;

    }

}


/* =========================================================
   STATUS
========================================================= */

$statusBanco = "";

$cancelado = false;


if ($pedido) {

    $statusBanco =
        strtolower(
            trim(
                $pedido["status_pedido"] ?? ""
            )
        );


    $cancelado =
        $statusBanco === "cancelado";

}


?>


<!DOCTYPE html>

<html lang="pt-br">


<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>


<title>
    Rastrear Pedido | Freaky Suplements
</title>


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

    font-family:
        Arial,
        Helvetica,
        sans-serif;

    min-height: 100vh;

}


/* =========================================================
   TOPO
========================================================= */

.topo {

    height: 34px;

    background: #ff2525;

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 11px;

    font-weight: bold;

    letter-spacing: .5px;

}


/* =========================================================
   HEADER
========================================================= */

header {

    background: #0d0d0d;

    border-bottom: 1px solid #242424;

}


.header-principal {

    width: 90%;

    max-width: 1400px;

    height: 82px;

    margin: auto;

    display: flex;

    align-items: center;

    justify-content: space-between;

}


.logo img {

    width: 150px;

    max-height: 58px;

    object-fit: contain;

}


.header-acoes {

    display: flex;

    align-items: center;

    gap: 25px;

}


.header-acoes a {

    color: #999999;

    text-decoration: none;

    font-size: 12px;

    transition: .3s;

}


.header-acoes a:hover {

    color: #ffffff;

}


.header-acoes .vermelho:hover {

    color: #ff2525;

}


/* =========================================================
   MENU
========================================================= */

nav {

    background: #101010;

    border-top: 1px solid #181818;

}


.menu {

    width: 90%;

    max-width: 1400px;

    height: 46px;

    margin: auto;

    display: flex;

    align-items: center;

    gap: 35px;

}


.menu a {

    height: 46px;

    display: flex;

    align-items: center;

    color: #777777;

    text-decoration: none;

    font-size: 11px;

    font-weight: bold;

    position: relative;

    transition: .3s;

}


.menu a:hover {

    color: #ffffff;

}


.menu a.ativo {

    color: #ffffff;

}


.menu a.ativo::after {

    content: "";

    position: absolute;

    bottom: 0;

    left: 0;

    right: 0;

    height: 2px;

    background: #ff2525;

}


/* =========================================================
   CONTAINER
========================================================= */

.container {

    width: 90%;

    max-width: 1200px;

    margin: 60px auto 90px;

}


/* =========================================================
   TÍTULO
========================================================= */

.titulo {

    margin-bottom: 35px;

}


.titulo-mini {

    color: #ff2525;

    font-size: 10px;

    font-weight: bold;

    letter-spacing: 2px;

    text-transform: uppercase;

    margin-bottom: 10px;

}


.titulo h1 {

    font-size: 42px;

    line-height: 1.1;

}


.titulo h1 span {

    color: #ff2525;

}


.titulo p {

    color: #777777;

    font-size: 13px;

    margin-top: 10px;

}


/* =========================================================
   SEM PEDIDOS
========================================================= */

.sem-pedidos {

    background: #101010;

    border: 1px solid #292929;

    border-radius: 18px;

    padding: 70px 30px;

    text-align: center;

}


.sem-icone {

    width: 75px;

    height: 75px;

    margin: 0 auto 22px;

    border-radius: 50%;

    background: #171717;

    border: 1px solid #333333;

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 28px;

}


.sem-pedidos h2 {

    font-size: 22px;

    margin-bottom: 10px;

}


.sem-pedidos p {

    color: #777777;

    font-size: 12px;

    margin-bottom: 25px;

}


.botao {

    display: inline-flex;

    align-items: center;

    justify-content: center;

    min-height: 47px;

    padding: 0 25px;

    border-radius: 8px;

    background: #ff2525;

    color: #ffffff;

    text-decoration: none;

    font-size: 11px;

    font-weight: bold;

    transition: .3s;

}


.botao:hover {

    background: #d90000;

    transform: translateY(-2px);

}


/* =========================================================
   PEDIDO
========================================================= */

.pedido {

    background: #101010;

    border: 1px solid #292929;

    border-radius: 18px;

    padding: 30px;

    box-shadow:
        0 20px 60px
        rgba(0,0,0,.25);

}


/* =========================================================
   CABEÇALHO DO PEDIDO
========================================================= */

.pedido-topo {

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 20px;

    padding-bottom: 22px;

    border-bottom: 1px solid #292929;

}


.pedido-topo h2 {

    font-size: 20px;

}


.status {

    padding: 8px 13px;

    border-radius: 20px;

    background: #1a0a0a;

    border: 1px solid #542020;

    color: #ff2525;

    font-size: 10px;

    font-weight: bold;

    text-transform: uppercase;

}


.status.cancelado {

    color: #999999;

    background: #171717;

    border-color: #333333;

}


/* =========================================================
   RASTREAMENTO
========================================================= */

.rastreamento {

    position: relative;

    display: flex;

    justify-content: space-between;

    margin: 60px 0 35px;

}


.linha-fundo {

    position: absolute;

    top: 21px;

    left: 10%;

    right: 10%;

    height: 4px;

    background: #292929;

    border-radius: 10px;

    z-index: 0;

}


#linhaProgresso {

    position: absolute;

    top: 21px;

    left: 10%;

    height: 4px;

    width: 0%;

    background: #ff2525;

    border-radius: 10px;

    z-index: 1;

    transition: width .8s ease;

}


.etapa {

    width: 20%;

    text-align: center;

    position: relative;

    z-index: 2;

}


.bolinha {

    width: 43px;

    height: 43px;

    margin: 0 auto 13px;

    border-radius: 50%;

    background: #292929;

    border: 2px solid #333333;

    color: #777777;

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 12px;

    font-weight: bold;

    transition: .5s;

}


.etapa.ativa .bolinha {

    background: #ff2525;

    border-color: #ff2525;

    color: #ffffff;

    box-shadow:
        0 0 20px
        rgba(255,37,37,.25);

}


.etapa.atual .bolinha {

    transform: scale(1.12);

}


.etapa p {

    color: #555555;

    font-size: 10px;

    line-height: 1.4;

}


.etapa.ativa p {

    color: #cccccc;

}


.etapa.atual p {

    color: #ff2525;

    font-weight: bold;

}


/* =========================================================
   MENSAGEM
========================================================= */

.mensagem {

    background: #0b0b0b;

    border: 1px solid #292929;

    border-left: 3px solid #ff2525;

    border-radius: 9px;

    padding: 17px;

    color: #aaaaaa;

    text-align: center;

    font-size: 12px;

}


.tempo {

    color: #555555;

    text-align: center;

    font-size: 10px;

    margin-top: 10px;

}


/* =========================================================
   DETALHES
========================================================= */

.detalhes {

    margin-top: 28px;

    border-top: 1px solid #292929;

    padding-top: 22px;

}


.botao-detalhes {

    width: 100%;

    min-height: 50px;

    background: #151515;

    border: 1px solid #292929;

    border-radius: 9px;

    color: #dddddd;

    text-align: left;

    padding: 0 17px;

    font-size: 11px;

    font-weight: bold;

    cursor: pointer;

    transition: .3s;

}


.botao-detalhes:hover {

    border-color: #ff2525;

    background: #181818;

}


.botao-detalhes span {

    color: #ff2525;

    font-size: 18px;

    margin-right: 8px;

}


.conteudo-detalhes {

    display: none;

    margin-top: 12px;

    background: #0b0b0b;

    border: 1px solid #292929;

    border-radius: 10px;

    padding: 10px 18px;

}


.conteudo-detalhes.aberto {

    display: block;

}


.item {

    display: flex;

    align-items: center;

    gap: 15px;

    padding: 15px 0;

    border-bottom: 1px solid #222222;

}


.item:last-child {

    border-bottom: none;

}


.item img {

    width: 62px;

    height: 62px;

    object-fit: contain;

    background: #151515;

    border-radius: 8px;

}


.item-info {

    flex: 1;

}


.item-info h3 {

    font-size: 13px;

    margin-bottom: 5px;

}


.item-info p {

    color: #666666;

    font-size: 10px;

}


.item-preco {

    color: #ff2525;

    font-size: 12px;

    font-weight: bold;

    white-space: nowrap;

}


/* =========================================================
   VALOR
========================================================= */

.valor {

    margin-top: 22px;

    padding-top: 22px;

    border-top: 1px solid #292929;

    display: flex;

    justify-content: space-between;

    align-items: center;

}


.valor span {

    color: #777777;

    font-size: 11px;

}


.valor strong {

    color: #ff2525;

    font-size: 25px;

}


/* =========================================================
   AÇÕES
========================================================= */

.acoes-pedido {

    display: flex;

    justify-content: flex-end;

    margin-top: 25px;

}


.botao-cancelar {

    min-height: 42px;

    padding: 0 18px;

    background: transparent;

    border: 1px solid #642323;

    border-radius: 7px;

    color: #ff5252;

    font-size: 10px;

    font-weight: bold;

    cursor: pointer;

    transition: .3s;

}


.botao-cancelar:hover {

    background: #ff2525;

    border-color: #ff2525;

    color: #ffffff;

}


/* =========================================================
   PEDIDO CANCELADO
========================================================= */

.cancelado-msg {

    margin-top: 30px;

    padding: 25px;

    background: #0b0b0b;

    border: 1px solid #292929;

    border-radius: 10px;

    color: #888888;

    text-align: center;

    font-size: 12px;

}


/* =========================================================
   BOTÃO VOLTAR
========================================================= */

.voltar-loja {

    display: flex;

    justify-content: center;

    margin-top: 25px;

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

    width: 90%;

    max-width: 1200px;

    margin: auto;

    padding: 45px 0;

    display: grid;

    grid-template-columns:
        1.5fr 1fr 1fr 1fr;

    gap: 40px;

}


.footer-logo img {

    width: 135px;

    margin-bottom: 13px;

}


.footer-logo p {

    max-width: 270px;

    color: #666666;

    font-size: 10px;

    line-height: 1.7;

}


.footer-col h3 {

    font-size: 10px;

    text-transform: uppercase;

    letter-spacing: 1px;

    margin-bottom: 15px;

}


.footer-col a {

    display: block;

    color: #666666;

    text-decoration: none;

    font-size: 10px;

    margin-bottom: 10px;

    transition: .3s;

}


.footer-col a:hover {

    color: #ff2525;

}


.footer-bottom {

    border-top: 1px solid #1c1c1c;

    text-align: center;

    padding: 18px;

    color: #555555;

    font-size: 9px;

}


/* =========================================================
   RESPONSIVIDADE
========================================================= */

@media (max-width: 800px) {

    .header-principal {

        height: 75px;

    }


    .header-acoes {

        gap: 12px;

    }


    .header-acoes a {

        font-size: 10px;

    }


    .menu {

        overflow-x: auto;

        gap: 25px;

    }


    .titulo h1 {

        font-size: 34px;

    }


    .rastreamento {

        flex-direction: column;

        gap: 22px;

    }


    .linha-fundo,
    #linhaProgresso {

        display: none;

    }


    .etapa {

        width: 100%;

        display: flex;

        align-items: center;

        gap: 15px;

        text-align: left;

    }


    .bolinha {

        margin: 0;

        flex-shrink: 0;

    }


    .footer-conteudo {

        grid-template-columns:
            1fr 1fr;

    }

}


@media (max-width: 500px) {

    .topo {

        font-size: 8px;

    }


    .logo img {

        width: 120px;

    }


    .header-acoes a {

        display: none;

    }


    .container {

        width: 94%;

        margin-top: 40px;

    }


    .pedido {

        padding: 20px;

    }


    .pedido-topo {

        align-items: flex-start;

        flex-direction: column;

    }


    .titulo h1 {

        font-size: 30px;

    }


    .item {

        align-items: flex-start;

    }


    .item-preco {

        font-size: 11px;

    }


    .valor strong {

        font-size: 21px;

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


        <a
            href="index.php"
            class="logo"
        >

            <img
                src="img/logo.png"
                alt="Freaky Suplements"
            >

        </a>


        <div class="header-acoes">


            <a
                href="rastrear.php"
                class="vermelho"
            >
                🚚 Rastrear Pedido
            </a>


            <a href="carrinho.php">
                🛒 Carrinho
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


            <a href="carrinho.php">
                Suas compras
            </a>


            <a
                href="rastrear.php"
                class="ativo"
            >
                Rastrear pedido
            </a>


        </div>


    </nav>


</header>


<!-- =========================================================
     CONTEÚDO
========================================================= -->

<main class="container">


    <section class="titulo">


        <div class="titulo-mini">
            ACOMPANHAMENTO
        </div>


        <h1>

            Rastrear <span>pedido.</span>

        </h1>


        <p>

            Acompanhe o andamento da sua compra.

        </p>


    </section>


<?php if (!$pedido): ?>


    <!-- =====================================================
         SEM PEDIDOS
    ====================================================== -->

    <section class="sem-pedidos">


        <div class="sem-icone">
            📦
        </div>


        <h2>
            Nenhum pedido encontrado
        </h2>


        <p>

            Você ainda não realizou nenhuma compra.

        </p>


        <a
            href="index.php"
            class="botao"
        >

            IR PARA A LOJA

        </a>


    </section>


<?php else: ?>


    <!-- =====================================================
         PEDIDO
    ====================================================== -->

    <section class="pedido">


        <div class="pedido-topo">


            <h2>

                Pedido #

                <?php

                echo htmlspecialchars(
                    $pedido["id_pedido"]
                );

                ?>

            </h2>


            <div
                id="statusAtual"
                class="status
                <?php

                echo $cancelado
                    ? "cancelado"
                    : "";

                ?>"
            >

                <?php

                echo $cancelado
                    ? "Pedido cancelado"
                    : "Pedido confirmado";

                ?>

            </div>


        </div>


<?php if (!$cancelado): ?>


        <!-- =================================================
             LINHA DE RASTREAMENTO
        ================================================== -->

        <div class="rastreamento">


            <div class="linha-fundo"></div>


            <div id="linhaProgresso"></div>


            <div class="etapa ativa atual">

                <div class="bolinha">
                    ✓
                </div>

                <p>
                    Pedido<br>
                    confirmado
                </p>

            </div>


            <div class="etapa">

                <div class="bolinha">
                    2
                </div>

                <p>
                    Separando<br>
                    seu pedido
                </p>

            </div>


            <div class="etapa">

                <div class="bolinha">
                    3
                </div>

                <p>
                    Saiu da<br>
                    transportadora
                </p>

            </div>


            <div class="etapa">

                <div class="bolinha">
                    4
                </div>

                <p>
                    Saiu para<br>
                    entrega
                </p>

            </div>


            <div class="etapa">

                <div class="bolinha">
                    5
                </div>

                <p>
                    Pedido<br>
                    entregue
                </p>

            </div>


        </div>


        <div
            class="mensagem"
            id="mensagem"
        >

            Seu pedido foi confirmado
            e está sendo preparado.

        </div>


        <div
            class="tempo"
            id="tempo"
        >

            Próxima atualização em 10 segundos

        </div>


<?php else: ?>


        <div class="cancelado-msg">

            Este pedido foi cancelado
            e não seguirá para entrega.

        </div>


<?php endif; ?>


        <!-- =================================================
             DETALHES
        ================================================== -->

        <div class="detalhes">


            <button
                type="button"
                class="botao-detalhes"
                onclick="abrirDetalhes(this)"
            >

                <span>＋</span>

                Ver detalhes do pedido

            </button>


            <div class="conteudo-detalhes">


<?php if (count($itens) > 0): ?>


<?php foreach ($itens as $item): ?>


                <div class="item">


                    <img
                        src="img/<?php

                        echo htmlspecialchars(
                            $item["imagem"]
                        );

                        ?>"
                        alt="<?php

                        echo htmlspecialchars(
                            $item["nome"]
                        );

                        ?>"
                    >


                    <div class="item-info">


                        <h3>

                            <?php

                            echo htmlspecialchars(
                                $item["nome"]
                            );

                            ?>

                        </h3>


                        <p>

                            Quantidade:

                            <?php

                            echo intval(
                                $item["quantidade"]
                            );

                            ?>

                        </p>


                    </div>


                    <div class="item-preco">

                        R$

                        <?php

                        echo number_format(
                            $item["preco_unitario"]
                            *
                            $item["quantidade"],
                            2,
                            ",",
                            "."
                        );

                        ?>

                    </div>


                </div>


<?php endforeach; ?>


<?php else: ?>


                <div
                    class="sem-itens"
                    style="
                        color:#666;
                        padding:20px;
                        text-align:center;
                    "
                >

                    Não foi possível encontrar
                    os itens deste pedido.

                </div>


<?php endif; ?>


            </div>


        </div>


        <!-- =================================================
             CANCELAR
        ================================================== -->

<?php if (!$cancelado): ?>


        <div class="acoes-pedido">


            <form
                method="POST"
                onsubmit="return confirmarCancelamento();"
            >


                <input
                    type="hidden"
                    name="id_pedido"
                    value="<?php

                    echo intval(
                        $pedido["id_pedido"]
                    );

                    ?>"
                >


                <button
                    type="submit"
                    name="cancelar_pedido"
                    class="botao-cancelar"
                >

                    CANCELAR PEDIDO

                </button>


            </form>


        </div>


<?php endif; ?>


        <!-- =================================================
             VALOR
        ================================================== -->

        <div class="valor">


            <span>
                Valor da compra
            </span>


            <strong>

                R$

                <?php

                echo number_format(
                    $pedido["valor_total"],
                    2,
                    ",",
                    "."
                );

                ?>

            </strong>


        </div>


    </section>


    <div class="voltar-loja">


        <a
            href="index.php"
            class="botao"
        >

            VOLTAR PARA A LOJA

        </a>


    </div>


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

                Produtos para quem leva
                seus objetivos a sério.

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


            <a href="login.php">
                Entrar
            </a>


            <a href="cadastro.php">
                Criar conta
            </a>


        </div>


    </div>


    <div class="footer-bottom">

        © 2026 Freaky Suplements.
        Todos os direitos reservados.

    </div>


</footer>


<script>


/* =========================================================
   DETALHES
========================================================= */

function abrirDetalhes(botao) {


    const conteudo =
        botao.nextElementSibling;


    if (
        conteudo.classList.contains(
            "aberto"
        )
    ) {


        conteudo.classList.remove(
            "aberto"
        );


        botao.innerHTML =
            "<span>＋</span> Ver detalhes do pedido";


    } else {


        conteudo.classList.add(
            "aberto"
        );


        botao.innerHTML =
            "<span>−</span> Ocultar detalhes do pedido";

    }

}


/* =========================================================
   CANCELAMENTO
========================================================= */

function confirmarCancelamento() {


    return confirm(
        "Tem certeza que deseja cancelar este pedido?"
    );

}


/* =========================================================
   SIMULAÇÃO DE STATUS
========================================================= */

document.addEventListener(
    "DOMContentLoaded",
    function () {


        const etapas =
            document.querySelectorAll(
                ".etapa"
            );


        const statusAtual =
            document.getElementById(
                "statusAtual"
            );


        const mensagem =
            document.getElementById(
                "mensagem"
            );


        const linha =
            document.getElementById(
                "linhaProgresso"
            );


        const tempo =
            document.getElementById(
                "tempo"
            );


        if (
            !etapas.length ||
            !statusAtual ||
            !mensagem
        ) {

            return;

        }


        const status = [

            "Pedido confirmado",

            "Separando seu pedido",

            "Saiu da transportadora",

            "Saiu para entrega",

            "Pedido entregue"

        ];


        const mensagens = [

            "Seu pedido foi confirmado e está sendo preparado.",

            "Seu pedido está sendo separado para envio.",

            "Seu pedido saiu da transportadora.",

            "Seu pedido saiu para entrega.",

            "Seu pedido foi entregue com sucesso!"

        ];


        let etapaAtual = 0;

        let segundos = 10;


        function atualizar() {


            etapas.forEach(
                function (
                    etapa,
                    index
                ) {


                    etapa.classList.remove(
                        "ativa"
                    );


                    etapa.classList.remove(
                        "atual"
                    );


                    if (
                        index <= etapaAtual
                    ) {

                        etapa.classList.add(
                            "ativa"
                        );

                    }


                    if (
                        index === etapaAtual
                    ) {

                        etapa.classList.add(
                            "atual"
                        );

                    }

                }
            );


            statusAtual.textContent =
                status[etapaAtual];


            mensagem.textContent =
                mensagens[etapaAtual];


            if (linha) {

                const progresso =
                    (etapaAtual / 4) * 80;


                linha.style.width =
                    progresso + "%";

            }


            if (
                etapaAtual < 4
            ) {

                tempo.textContent =
                    "Próxima atualização em "
                    +
                    segundos
                    +
                    " segundos";

            } else {

                tempo.textContent =
                    "Pedido entregue!";

            }

        }


        function avancar() {


            segundos--;


            if (
                segundos <= 0
            ) {

                if (
                    etapaAtual < 4
                ) {

                    etapaAtual++;

                    segundos = 10;

                }

            }


            atualizar();


            if (
                etapaAtual < 4
            ) {

                setTimeout(
                    avancar,
                    1000
                );

            }

        }


        atualizar();


        setTimeout(
            avancar,
            1000
        );

    }

);

</script>


</body>

</html>
