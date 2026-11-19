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

    die(
        "Erro na conexão com o banco de dados: " .
        $conexao->connect_error
    );

}

$conexao->set_charset("utf8");


/* =========================================================
   VERIFICAR SE O PEDIDO FOI CONCLUÍDO
========================================================= */

$sucesso = false;

if (
    isset($_GET["sucesso"]) &&
    $_GET["sucesso"] == "1" &&
    isset($_SESSION["pedido_realizado"])
) {

    $sucesso = true;

    unset($_SESSION["pedido_realizado"]);

}


/* =========================================================
   VERIFICAR LOGIN
========================================================= */

if (
    !$sucesso &&
    !isset($_SESSION["id_cliente"])
) {

    header("Location: login.php");

    exit;

}


/* =========================================================
   VERIFICAR CARRINHO
========================================================= */

if (
    !$sucesso &&
    (
        !isset($_SESSION["carrinho"]) ||
        empty($_SESSION["carrinho"])
    )
) {

    header("Location: carrinho.php");

    exit;

}


/* =========================================================
   VARIÁVEIS
========================================================= */

$produtos_carrinho = [];

$total = 0;

$quantidade_total = 0;

$erro = "";

$cep = "";
$rua = "";
$numero = "";
$complemento = "";
$bairro = "";
$cidade = "";
$estado = "";


/* =========================================================
   BUSCAR PRODUTOS DO CARRINHO
========================================================= */

if (!$sucesso) {

    foreach (
        $_SESSION["carrinho"]
        as $id => $quantidade
    ) {

        $id = intval($id);

        $quantidade = intval($quantidade);


        if (
            $id <= 0 ||
            $quantidade <= 0
        ) {

            continue;

        }


        $sql = $conexao->prepare(
            "SELECT
                id_produto,
                nome,
                descricao,
                preco,
                estoque,
                categoria,
                imagem
             FROM produtos
             WHERE id_produto = ?"
        );


        $sql->bind_param(
            "i",
            $id
        );


        $sql->execute();


        $resultado =
            $sql->get_result();


        if (
            $resultado &&
            $resultado->num_rows > 0
        ) {

            $produto =
                $resultado->fetch_assoc();


            /* =========================
               VERIFICAR ESTOQUE
            ========================= */

            if (
                $quantidade >
                $produto["estoque"]
            ) {

                $erro =
                    "O produto " .
                    $produto["nome"] .
                    " não possui estoque suficiente.";

                break;

            }


            $produto["quantidade"] =
                $quantidade;


            $subtotal =
                $produto["preco"] *
                $quantidade;


            $produto["subtotal"] =
                $subtotal;


            $produtos_carrinho[] =
                $produto;


            $total +=
                $subtotal;


            $quantidade_total +=
                $quantidade;

        }

    }

}


/* =========================================================
   FINALIZAR PEDIDO
========================================================= */

if (
    !$sucesso &&
    $_SERVER["REQUEST_METHOD"] == "POST" &&
    empty($erro)
) {


    $cep = trim($_POST["cep"] ?? "");
    $rua = trim($_POST["rua"] ?? "");
    $numero = trim($_POST["numero"] ?? "");
    $complemento = trim($_POST["complemento"] ?? "");
    $bairro = trim($_POST["bairro"] ?? "");
    $cidade = trim($_POST["cidade"] ?? "");
    $estado = trim($_POST["estado"] ?? "");

    $pagamento =
        isset($_POST["pagamento"])
        ? $_POST["pagamento"]
        : "";


    $tipo_cartao =
        isset($_POST["tipo_cartao"])
        ? $_POST["tipo_cartao"]
        : "";


    /* =========================
       VALIDAR ENDEREÇO
    ========================= */

    if (
        empty($cep) ||
        empty($rua) ||
        empty($numero) ||
        empty($bairro) ||
        empty($cidade) ||
        empty($estado)
    ) {

        $erro =
            "Preencha todos os campos obrigatórios do endereço.";

    }


    /* =========================
       VALIDAR PAGAMENTO
    ========================= */

    if (
        $pagamento != "pix" &&
        $pagamento != "cartao" &&
        $pagamento != "boleto"
    ) {

        $erro =
            "Selecione uma forma de pagamento.";

    }


    /* =========================
       VALIDAR CARTÃO
    ========================= */

    if (
        empty($erro) &&
        $pagamento == "cartao" &&
        $tipo_cartao != "credito" &&
        $tipo_cartao != "debito"
    ) {

        $erro =
            "Escolha entre cartão de crédito ou débito.";

    }


    /* =========================
       CRIAR PEDIDO
    ========================= */

    if (empty($erro)) {


        $id_cliente =
            intval(
                $_SESSION["id_cliente"]
            );


        $status =
            "Pendente";


        /* =========================
           INICIAR TRANSAÇÃO
        ========================= */

        $conexao->begin_transaction();


        try {


            /* =========================
               INSERIR PEDIDO
            ========================= */

            $sql_pedido =
                $conexao->prepare(
                    "INSERT INTO pedidos
                    (
                        id_cliente,
                        cep,
                        rua,
                        numero,
                        complemento,
                        bairro,
                        cidade,
                        estado,
                        status_pedido,
                        valor_total
                    )
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
                );


            $sql_pedido->bind_param(
                "issssssssd",
                $id_cliente,
                $cep,
                $rua,
                $numero,
                $complemento,
                $bairro,
                $cidade,
                $estado,
                $status,
                $total
            );


            if (
                !$sql_pedido->execute()
            ) {

                throw new Exception(
                    "Erro ao criar o pedido."
                );

            }


            $id_pedido =
                $conexao->insert_id;


            /* =========================
               INSERIR PRODUTOS
            ========================= */

            foreach (
                $produtos_carrinho
                as $produto
            ) {


                $id_produto =
                    intval(
                        $produto["id_produto"]
                    );


                $quantidade =
                    intval(
                        $produto["quantidade"]
                    );


                $preco =
                    floatval(
                        $produto["preco"]
                    );


                /* =========================
                   ITEM DO PEDIDO
                ========================= */

                $sql_item =
                    $conexao->prepare(
                        "INSERT INTO itens_pedido
                        (
                            id_pedido,
                            id_produto,
                            quantidade,
                            preco_unitario
                        )
                        VALUES (?, ?, ?, ?)"
                    );


                $sql_item->bind_param(
                    "iiid",
                    $id_pedido,
                    $id_produto,
                    $quantidade,
                    $preco
                );


                if (
                    !$sql_item->execute()
                ) {

                    throw new Exception(
                        "Erro ao adicionar produto ao pedido."
                    );

                }


                /* =========================
                   DIMINUIR ESTOQUE
                ========================= */

                $sql_estoque =
                    $conexao->prepare(
                        "UPDATE produtos
                         SET estoque = estoque - ?
                         WHERE id_produto = ?
                         AND estoque >= ?"
                    );


                $sql_estoque->bind_param(
                    "iii",
                    $quantidade,
                    $id_produto,
                    $quantidade
                );


                if (
                    !$sql_estoque->execute()
                ) {

                    throw new Exception(
                        "Erro ao atualizar estoque."
                    );

                }


                if (
                    $sql_estoque->affected_rows == 0
                ) {

                    throw new Exception(
                        "Estoque insuficiente para o produto " .
                        $produto["nome"]
                    );

                }

            }


            /* =========================
               CONFIRMAR TRANSAÇÃO
            ========================= */

            $conexao->commit();


            /* =========================
               LIMPAR CARRINHO
            ========================= */

            $_SESSION["carrinho"] = [];


            /* =========================
               GUARDAR PEDIDO
            ========================= */

            $_SESSION["pedido_realizado"] =
                $id_pedido;


            $_SESSION["ultimo_pedido"] =
                $id_pedido;


            /* =========================
               IR PARA SUCESSO
            ========================= */

            header(
                "Location: finalizar.php?sucesso=1"
            );

            exit;


        } catch (Exception $e) {


            $conexao->rollback();


            $erro =
                "Não foi possível finalizar o pedido: " .
                $e->getMessage();

        }

    }

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
    Finalizar Compra | Freaky Suplements
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

    color: #ffffff;

    font-size: 11px;

    font-weight: bold;

    letter-spacing: .4px;

}


/* =========================================================
   HEADER
========================================================= */

header {

    background: #0d0d0d;

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

    justify-content: space-between;

}


/* =========================================================
   LOGO
========================================================= */

.logo img {

    width: 150px;

    max-height: 55px;

    object-fit: contain;

}


/* =========================================================
   HEADER DIREITA
========================================================= */

.header-direita {

    display: flex;

    align-items: center;

    gap: 25px;

}


.header-link {

    color: #999999;

    text-decoration: none;

    font-size: 12px;

    transition: .3s;

}


.header-link:hover {

    color: #ffffff;

}


.header-link.destaque:hover {

    color: #ff2525;

}


/* =========================================================
   NAVEGAÇÃO
========================================================= */

nav {

    background: #101010;

    border-top: 1px solid #181818;

}


.menu {

    max-width: 1400px;

    margin: auto;

    padding: 0 5%;

    height: 46px;

    display: flex;

    align-items: center;

    gap: 35px;

}


.menu a {

    height: 46px;

    display: flex;

    align-items: center;

    color: #888888;

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

    max-width: 1250px;

    margin: 55px auto 90px;

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

    text-transform: uppercase;

    letter-spacing: 2px;

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
   CHECKOUT
========================================================= */

.checkout {

    display: grid;

    grid-template-columns:
        minmax(0, 1fr)
        360px;

    gap: 25px;

    align-items: start;

}


/* =========================================================
   CAIXAS
========================================================= */

.caixa {

    background: #101010;

    border: 1px solid #252525;

    border-radius: 16px;

    padding: 28px;

    margin-bottom: 18px;

}


.caixa-titulo {

    display: flex;

    align-items: center;

    gap: 12px;

    padding-bottom: 20px;

    border-bottom: 1px solid #252525;

    margin-bottom: 5px;

}


.numero-secao {

    width: 30px;

    height: 30px;

    border-radius: 50%;

    background: #ff2525;

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 12px;

    font-weight: bold;

}


.caixa-titulo h2 {

    font-size: 17px;

}


.caixa-subtitulo {

    color: #666666;

    font-size: 11px;

    margin: 0 0 20px 42px;

}


/* =========================================================
   ENDEREÇO DE ENTREGA
========================================================= */

.endereco-grid {

    display: grid;

    grid-template-columns: 1fr 1fr;

    gap: 14px;

    margin-top: 20px;

}

.campo-endereco {

    display: flex;

    flex-direction: column;

    gap: 7px;

}

.campo-endereco.completo {

    grid-column: 1 / -1;

}

.campo-endereco label {

    color: #cccccc;

    font-size: 10px;

    font-weight: bold;

    letter-spacing: .5px;

}

.campo-endereco input,
.campo-endereco select {

    width: 100%;

    height: 46px;

    background: #0b0b0b;

    border: 1px solid #292929;

    border-radius: 8px;

    color: #ffffff;

    padding: 0 13px;

    outline: none;

    font-family: Arial, Helvetica, sans-serif;

    font-size: 12px;

    transition: .3s;

}

.campo-endereco input::placeholder {

    color: #555555;

}

.campo-endereco input:focus,
.campo-endereco select:focus {

    border-color: #ff2525;

    background: #101010;

    box-shadow: 0 0 0 3px rgba(255,37,37,.07);

}

.campo-endereco small {

    color: #555555;

    font-size: 9px;

}


/* =========================================================
   PRODUTOS
========================================================= */

.produto {

    min-height: 80px;

    padding: 15px 0;

    border-bottom: 1px solid #242424;

    display: flex;

    align-items: center;

    gap: 15px;

}


.produto:last-child {

    border-bottom: none;

}


.produto-imagem {

    width: 70px;

    height: 70px;

    flex-shrink: 0;

    background: #080808;

    border: 1px solid #292929;

    border-radius: 10px;

    display: flex;

    align-items: center;

    justify-content: center;

    overflow: hidden;

}


.produto-imagem img {

    width: 60px;

    height: 60px;

    object-fit: contain;

}


.produto-info {

    flex: 1;

    min-width: 0;

}


.produto-nome {

    font-size: 14px;

    font-weight: bold;

    margin-bottom: 7px;

}


.produto-detalhe {

    color: #666666;

    font-size: 11px;

}


.produto-preco {

    color: #ffffff;

    font-size: 14px;

    font-weight: bold;

    white-space: nowrap;

}


/* =========================================================
   TOTAL
========================================================= */

.total-area {

    margin-top: 15px;

    padding-top: 22px;

    border-top: 1px solid #333333;

    display: flex;

    justify-content: space-between;

    align-items: center;

}


.total-texto {

    color: #999999;

    font-size: 13px;

}


.total {

    color: #ff2525;

    font-size: 29px;

    font-weight: bold;

}


/* =========================================================
   PAGAMENTO
========================================================= */

.pagamento-descricao {

    color: #777777;

    font-size: 12px;

    margin: 0 0 18px;

}


.opcoes-pagamento {

    display: grid;

    grid-template-columns:
        repeat(3, 1fr);

    gap: 12px;

}


.opcao-pagamento {

    display: block;

    cursor: pointer;

}


.opcao-pagamento input {

    display: none;

}


.cartao-pagamento {

    min-height: 125px;

    background: #0b0b0b;

    border: 1px solid #292929;

    border-radius: 12px;

    padding: 17px;

    position: relative;

    transition: .3s;

}


.cartao-pagamento:hover {

    border-color: #444444;

    transform: translateY(-2px);

}


.opcao-pagamento
input:checked
+
.cartao-pagamento {

    border-color: #ff2525;

    background: #170909;

    box-shadow:
        0 0 0 1px #ff2525,
        0 12px 30px rgba(
            255,
            37,
            37,
            .08
        );

}


.icone-pagamento {

    width: 43px;

    height: 43px;

    border-radius: 10px;

    background: #171717;

    border: 1px solid #292929;

    display: flex;

    align-items: center;

    justify-content: center;

    color: #ff2525;

    font-size: 12px;

    font-weight: bold;

    margin-bottom: 15px;

}


.texto-pagamento strong {

    display: block;

    font-size: 13px;

    margin-bottom: 5px;

}


.texto-pagamento span {

    color: #666666;

    font-size: 10px;

}


.check {

    position: absolute;

    right: 12px;

    top: 12px;

    width: 20px;

    height: 20px;

    border-radius: 50%;

    border: 1px solid #444444;

    color: transparent;

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 11px;

}


.opcao-pagamento
input:checked
+
.cartao-pagamento
.check {

    background: #ff2525;

    border-color: #ff2525;

    color: #ffffff;

}


/* =========================================================
   OPÇÕES DO CARTÃO
========================================================= */

.opcoes-cartao {

    display: none;

    margin-top: 15px;

    padding: 20px;

    background: #0b0b0b;

    border: 1px solid #292929;

    border-radius: 12px;

}


.opcoes-cartao.ativo {

    display: block;

}


.opcoes-cartao h4 {

    font-size: 13px;

    margin-bottom: 13px;

    color: #dddddd;

}


.tipos-cartao {

    display: grid;

    grid-template-columns:
        1fr 1fr;

    gap: 12px;

}


.tipos-cartao label {

    cursor: pointer;

}


.tipos-cartao input {

    display: none;

}


.tipo-cartao {

    border: 1px solid #292929;

    background: #101010;

    border-radius: 10px;

    padding: 15px;

    display: flex;

    align-items: center;

    gap: 12px;

    transition: .3s;

}


.tipo-cartao:hover {

    border-color: #555555;

}


.tipos-cartao
input:checked
+
.tipo-cartao {

    border-color: #ff2525;

    background: #170909;

}


.radio-visual {

    width: 18px;

    height: 18px;

    border: 2px solid #555555;

    border-radius: 50%;

    position: relative;

    flex-shrink: 0;

}


.tipos-cartao
input:checked
+
.tipo-cartao
.radio-visual {

    border-color: #ff2525;

}


.tipos-cartao
input:checked
+
.tipo-cartao
.radio-visual::after {

    content: "";

    position: absolute;

    width: 8px;

    height: 8px;

    background: #ff2525;

    border-radius: 50%;

    left: 3px;

    top: 3px;

}


.tipo-cartao strong {

    display: block;

    font-size: 12px;

}


.tipo-cartao small {

    display: block;

    color: #666666;

    font-size: 10px;

    margin-top: 4px;

}


/* =========================================================
   RESUMO LATERAL
========================================================= */

.resumo {

    background:
        linear-gradient(
            145deg,
            #151515,
            #0d0d0d
        );

    border: 1px solid #292929;

    border-radius: 17px;

    padding: 25px;

    position: sticky;

    top: 135px;

    box-shadow:
        0 20px 60px
        rgba(0,0,0,.3);

}


.resumo-titulo {

    font-size: 17px;

    padding-bottom: 18px;

    border-bottom: 1px solid #292929;

    margin-bottom: 20px;

}


.resumo-linha {

    display: flex;

    justify-content: space-between;

    align-items: center;

    margin-bottom: 14px;

    color: #777777;

    font-size: 12px;

}


.resumo-linha strong {

    color: #dddddd;

}


.resumo-linha.frete strong {

    color: #50d889;

}


.resumo-total {

    margin-top: 20px;

    padding-top: 20px;

    border-top: 1px solid #292929;

    display: flex;

    align-items: flex-end;

    justify-content: space-between;

}


.resumo-total span {

    color: #999999;

    font-size: 11px;

}


.resumo-valor {

    color: #ff2525;

    font-size: 27px;

    font-weight: bold;

}


/* =========================================================
   SEGURANÇA
========================================================= */

.seguro {

    margin-top: 20px;

    padding: 16px;

    background: #0b0b0b;

    border: 1px solid #242424;

    border-radius: 10px;

    display: flex;

    gap: 12px;

}


.seguro-icone {

    font-size: 19px;

}


.seguro strong {

    display: block;

    font-size: 11px;

    margin-bottom: 5px;

}


.seguro p {

    color: #666666;

    font-size: 10px;

    line-height: 1.5;

}


/* =========================================================
   BOTÕES
========================================================= */

.botoes {

    display: grid;

    grid-template-columns:
        1fr 1.5fr;

    gap: 12px;

    margin-top: 25px;

}


.botao {

    min-height: 51px;

    border-radius: 8px;

    display: flex;

    align-items: center;

    justify-content: center;

    text-decoration: none;

    font-size: 11px;

    font-weight: bold;

    letter-spacing: .5px;

    cursor: pointer;

    transition: .3s;

}


.voltar-botao {

    background: #181818;

    color: #aaaaaa;

    border: 1px solid #292929;

}


.voltar-botao:hover {

    background: #222222;

    color: #ffffff;

    border-color: #444444;

}


.confirmar {

    background: #ff2525;

    color: #ffffff;

    border: 1px solid #ff2525;

    box-shadow:
        0 12px 30px
        rgba(255,37,37,.14);

}


.confirmar:hover {

    background: #d90000;

    border-color: #d90000;

    transform: translateY(-2px);

}


/* =========================================================
   ERRO
========================================================= */

.erro {

    background: #291010;

    border: 1px solid #762222;

    color: #ff7070;

    padding: 15px 18px;

    border-radius: 10px;

    margin-bottom: 20px;

    font-size: 12px;

}


/* =========================================================
   SUCESSO
========================================================= */

.sucesso {

    min-height: 550px;

    background:

        radial-gradient(
            circle at 50% 0%,
            rgba(255,37,37,.10),
            transparent 45%
        ),

        #101010;

    border: 1px solid #292929;

    border-radius: 20px;

    display: flex;

    flex-direction: column;

    align-items: center;

    justify-content: center;

    text-align: center;

    padding: 60px 30px;

    box-shadow:
        0 25px 70px
        rgba(0,0,0,.35);

}


.icone-sucesso {

    width: 95px;

    height: 95px;

    border-radius: 50%;

    background: #170909;

    border: 2px solid #ff2525;

    display: flex;

    align-items: center;

    justify-content: center;

    color: #ff2525;

    font-size: 43px;

    font-weight: bold;

    margin-bottom: 28px;

    box-shadow:
        0 15px 40px
        rgba(255,37,37,.10);

}


.sucesso h1 {

    font-size: 38px;

    margin-bottom: 14px;

}


.sucesso h1 span {

    color: #ff2525;

}


.sucesso p {

    color: #777777;

    font-size: 13px;

    line-height: 1.7;

}


.botoes-sucesso {

    display: flex;

    justify-content: center;

    gap: 12px;

    margin-top: 30px;

    flex-wrap: wrap;

}


.botao-rastrear,
.botao-loja {

    min-width: 195px;

    height: 48px;

    display: flex;

    align-items: center;

    justify-content: center;

    padding: 0 25px;

    border-radius: 8px;

    text-decoration: none;

    font-size: 11px;

    font-weight: bold;

    transition: .3s;

}


.botao-rastrear {

    color: #ffffff;

    background: transparent;

    border: 1px solid #ff2525;

}


.botao-rastrear:hover {

    background: #ff2525;

    transform: translateY(-2px);

}


.botao-loja {

    color: #ffffff;

    background: #ff2525;

    border: 1px solid #ff2525;

}


.botao-loja:hover {

    background: #d90000;

    border-color: #d90000;

    transform: translateY(-2px);

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

    max-width: 1250px;

    width: 90%;

    margin: auto;

    padding: 50px 0 40px;

    display: grid;

    grid-template-columns:
        1.5fr 1fr 1fr 1fr;

    gap: 40px;

}


.footer-logo img {

    width: 140px;

    margin-bottom: 15px;

}


.footer-logo p {

    max-width: 290px;

    color: #666666;

    font-size: 11px;

    line-height: 1.7;

}


.footer-col h3 {

    color: #ffffff;

    font-size: 11px;

    text-transform: uppercase;

    letter-spacing: 1px;

    margin-bottom: 17px;

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

    padding: 20px;

    text-align: center;

    color: #555555;

    font-size: 10px;

}


/* =========================================================
   RESPONSIVIDADE
========================================================= */

@media (max-width: 1000px) {

    .checkout {

        grid-template-columns: 1fr;

    }


    .resumo {

        position: static;

    }


    .opcoes-pagamento {

        grid-template-columns:
            1fr 1fr;

    }

}


@media (max-width: 700px) {

    .endereco-grid {

        grid-template-columns: 1fr;

    }

    .campo-endereco.completo {

        grid-column: auto;

    }

    .header-direita {

        gap: 12px;

    }


    .header-link {

        font-size: 11px;

    }


    .menu {

        overflow-x: auto;

        gap: 25px;

    }


    .titulo h1 {

        font-size: 32px;

    }


    .caixa {

        padding: 20px;

    }


    .opcoes-pagamento {

        grid-template-columns: 1fr;

    }


    .tipos-cartao {

        grid-template-columns: 1fr;

    }


    .footer-conteudo {

        grid-template-columns:
            1fr 1fr;

    }

}


@media (max-width: 480px) {

    .topo {

        font-size: 9px;

    }


    .logo img {

        width: 125px;

    }


    .header-link {

        font-size: 10px;

    }


    .titulo h1 {

        font-size: 28px;

    }


    .produto {

        align-items: flex-start;

    }


    .produto-imagem {

        width: 58px;

        height: 58px;

    }


    .produto-imagem img {

        width: 50px;

        height: 50px;

    }


    .produto-preco {

        font-size: 12px;

    }


    .total {

        font-size: 25px;

    }


    .botoes {

        grid-template-columns: 1fr;

    }


    .sucesso {

        min-height: 480px;

        padding: 40px 20px;

    }


    .sucesso h1 {

        font-size: 29px;

    }


    .botao-rastrear,
    .botao-loja {

        width: 100%;

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
            href="<?php
                echo $sucesso
                    ? "index.php"
                    : "carrinho.php";
            ?>"
            class="logo"
        >

            <img
                src="img/logo.png"
                alt="Freaky Suplements"
            >

        </a>


        <div class="header-direita">


            <a
                href="rastrear.php"
                class="header-link destaque"
            >
                🚚 Rastrear Pedido
            </a>


            <a
                href="carrinho.php"
                class="header-link"
            >
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

            <a
                href="carrinho.php"
                class="<?php
                    echo !$sucesso
                        ? "ativo"
                        : "";
                ?>"
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


<?php if ($sucesso): ?>


    <!-- =====================================================
         PEDIDO CONCLUÍDO
    ====================================================== -->

    <section class="sucesso">


        <div class="icone-sucesso">

            ✓

        </div>


        <h1>

            Compra <span>concluída!</span>

        </h1>


        <p>

            Seu pedido foi registrado com sucesso.

        </p>


        <p>

            Obrigado por comprar na Freaky Suplements.

        </p>


        <div class="botoes-sucesso">


            <a
                href="rastrear.php"
                class="botao-rastrear"
            >

                🚚 RASTREAR PEDIDO

            </a>


            <a
                href="index.php"
                class="botao-loja"
            >

                VOLTAR PARA A LOJA

            </a>


        </div>


    </section>


<?php else: ?>


    <!-- =====================================================
         TÍTULO
    ====================================================== -->

    <section class="titulo">


        <div class="titulo-mini">

            CHECKOUT

        </div>


        <h1>

            Finalizar <span>compra.</span>

        </h1>


        <p>

            Confira seu pedido e escolha a forma de pagamento.

        </p>


    </section>


<?php if (!empty($erro)): ?>


    <div class="erro">

        <?php

        echo htmlspecialchars(
            $erro
        );

        ?>

    </div>


<?php endif; ?>


    <!-- =====================================================
         CHECKOUT
    ====================================================== -->

    <div class="checkout">


        <!-- =================================================
             LADO ESQUERDO
        ================================================== -->

        <div>


            <!-- =============================================
                 PRODUTOS
            ============================================== -->

            <section class="caixa">


                <div class="caixa-titulo">

                    <div class="numero-secao">
                        1
                    </div>

                    <h2>
                        Resumo dos produtos
                    </h2>

                </div>


                <p class="caixa-subtitulo">

                    Confira os itens selecionados antes de finalizar.

                </p>


<?php foreach (
    $produtos_carrinho
    as $produto
): ?>


                <div class="produto">


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


                    <div class="produto-info">


                        <div class="produto-nome">

                            <?php

                            echo htmlspecialchars(
                                $produto["nome"]
                            );

                            ?>

                        </div>


                        <div class="produto-detalhe">

                            <?php

                            echo intval(
                                $produto["quantidade"]
                            );

                            ?>

                            × R$

                            <?php

                            echo number_format(
                                $produto["preco"],
                                2,
                                ",",
                                "."
                            );

                            ?>

                        </div>


                    </div>


                    <div class="produto-preco">

                        R$

                        <?php

                        echo number_format(
                            $produto["subtotal"],
                            2,
                            ",",
                            "."
                        );

                        ?>

                    </div>


                </div>


<?php endforeach; ?>


                <div class="total-area">


                    <div class="total-texto">

                        Total da compra

                    </div>


                    <div class="total">

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


            </section>


            <form
                method="POST"
                id="form-pagamento"
            >


            <!-- =============================================
                 ENDEREÇO DE ENTREGA
            ============================================== -->

            <section class="caixa">

                <div class="caixa-titulo">

                    <div class="numero-secao">
                        2
                    </div>

                    <h2>
                        Endereço de entrega
                    </h2>

                </div>

                <p class="caixa-subtitulo">
                    Informe o endereço onde deseja receber seu pedido.
                </p>

                <div class="endereco-grid">

                    <div class="campo-endereco">
                        <label for="cep">CEP *</label>
                        <input
                            type="text"
                            id="cep"
                            name="cep"
                            value="<?php echo htmlspecialchars($cep); ?>"
                            placeholder="00000-000"
                            maxlength="10"
                            inputmode="numeric"
                            required
                        >
                        <small>Digite seu CEP.</small>
                    </div>

                    <div class="campo-endereco">
                        <label for="numero">NÚMERO *</label>
                        <input
                            type="text"
                            id="numero"
                            name="numero"
                            value="<?php echo htmlspecialchars($numero); ?>"
                            placeholder="Ex.: 123"
                            maxlength="20"
                            required
                        >
                    </div>

                    <div class="campo-endereco completo">
                        <label for="rua">RUA *</label>
                        <input
                            type="text"
                            id="rua"
                            name="rua"
                            value="<?php echo htmlspecialchars($rua); ?>"
                            placeholder="Digite o nome da rua"
                            maxlength="150"
                            autocomplete="street-address"
                            required
                        >
                    </div>

                    <div class="campo-endereco">
                        <label for="bairro">BAIRRO *</label>
                        <input
                            type="text"
                            id="bairro"
                            name="bairro"
                            value="<?php echo htmlspecialchars($bairro); ?>"
                            placeholder="Digite o bairro"
                            maxlength="100"
                            required
                        >
                    </div>

                    <div class="campo-endereco">
                        <label for="cidade">CIDADE *</label>
                        <input
                            type="text"
                            id="cidade"
                            name="cidade"
                            value="<?php echo htmlspecialchars($cidade); ?>"
                            placeholder="Digite a cidade"
                            maxlength="100"
                            required
                        >
                    </div>

                    <div class="campo-endereco">
                        <label for="estado">ESTADO *</label>
                        <input
                            type="text"
                            id="estado"
                            name="estado"
                            value="<?php echo htmlspecialchars($estado); ?>"
                            placeholder="Ex.: São Paulo"
                            maxlength="50"
                            required
                        >
                    </div>

                    <div class="campo-endereco">
                        <label for="complemento">COMPLEMENTO</label>
                        <input
                            type="text"
                            id="complemento"
                            name="complemento"
                            value="<?php echo htmlspecialchars($complemento); ?>"
                            placeholder="Apartamento, bloco, etc."
                            maxlength="100"
                        >
                    </div>

                </div>

            </section>


            <!-- =============================================
                 PAGAMENTO
            ============================================== -->

            <section class="caixa">


                <div class="caixa-titulo">

                    <div class="numero-secao">
                        3
                    </div>

                    <h2>
                        Forma de pagamento
                    </h2>

                </div>


                <p class="caixa-subtitulo">

                    Escolha como deseja pagar sua compra.

                </p>


                    <div class="opcoes-pagamento">


                        <!-- CARTÃO -->

                        <label class="opcao-pagamento">


                            <input
                                type="radio"
                                name="pagamento"
                                value="cartao"
                                onchange="mostrarCartao()"
                                required
                            >


                            <div class="cartao-pagamento">


                                <div class="icone-pagamento">

                                    💳

                                </div>


                                <div class="texto-pagamento">

                                    <strong>
                                        Cartão
                                    </strong>

                                    <span>
                                        Crédito ou débito
                                    </span>

                                </div>


                                <div class="check">

                                    ✓

                                </div>


                            </div>


                        </label>


                        <!-- PIX -->

                        <label class="opcao-pagamento">


                            <input
                                type="radio"
                                name="pagamento"
                                value="pix"
                                onchange="esconderCartao()"
                            >


                            <div class="cartao-pagamento">


                                <div class="icone-pagamento">

                                    PIX

                                </div>


                                <div class="texto-pagamento">

                                    <strong>
                                        PIX
                                    </strong>

                                    <span>
                                        Pagamento instantâneo
                                    </span>

                                </div>


                                <div class="check">

                                    ✓

                                </div>


                            </div>


                        </label>


                        <!-- BOLETO -->

                        <label class="opcao-pagamento">


                            <input
                                type="radio"
                                name="pagamento"
                                value="boleto"
                                onchange="esconderCartao()"
                            >


                            <div class="cartao-pagamento">


                                <div class="icone-pagamento">

                                    ▤

                                </div>


                                <div class="texto-pagamento">

                                    <strong>
                                        Boleto
                                    </strong>

                                    <span>
                                        Pagamento por boleto
                                    </span>

                                </div>


                                <div class="check">

                                    ✓

                                </div>


                            </div>


                        </label>


                    </div>


                    <!-- =====================================
                         TIPO DE CARTÃO
                    ====================================== -->

                    <div
                        id="opcoes-cartao"
                        class="opcoes-cartao"
                    >


                        <h4>

                            Escolha o tipo de cartão

                        </h4>


                        <div class="tipos-cartao">


                            <!-- CRÉDITO -->

                            <label>


                                <input
                                    type="radio"
                                    name="tipo_cartao"
                                    value="credito"
                                >


                                <div class="tipo-cartao">


                                    <span
                                        class="radio-visual"
                                    ></span>


                                    <div>

                                        <strong>
                                            Crédito
                                        </strong>

                                        <small>
                                            Pague no cartão de crédito
                                        </small>

                                    </div>


                                </div>


                            </label>


                            <!-- DÉBITO -->

                            <label>


                                <input
                                    type="radio"
                                    name="tipo_cartao"
                                    value="debito"
                                >


                                <div class="tipo-cartao">


                                    <span
                                        class="radio-visual"
                                    ></span>


                                    <div>

                                        <strong>
                                            Débito
                                        </strong>

                                        <small>
                                            Pague no cartão de débito
                                        </small>

                                    </div>


                                </div>


                            </label>


                        </div>


                    </div>


                    <!-- =====================================
                         BOTÕES
                    ====================================== -->

                    <div class="botoes">


                        <a
                            href="carrinho.php"
                            class="botao voltar-botao"
                        >

                            ← VOLTAR

                        </a>


                        <button
                            type="submit"
                            class="botao confirmar"
                        >

                            CONFIRMAR PEDIDO

                        </button>


                    </div>


                </form>


            </section>


        </div>


        <!-- =================================================
             RESUMO LATERAL
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

                    <?php
                    echo $quantidade_total;
                    ?>

                    <?php

                    echo
                        $quantidade_total == 1
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


            <div class="resumo-total">

                <span>
                    Total
                </span>


                <div class="resumo-valor">

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


            <div class="seguro">


                <div class="seguro-icone">
                    🔒
                </div>


                <div>

                    <strong>
                        Compra segura
                    </strong>

                    <p>
                        Seus dados são utilizados
                        somente para processar
                        seu pedido.
                    </p>

                </div>


            </div>


        </aside>


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


<!-- =========================================================
     JAVASCRIPT
========================================================= -->

<script>


/* =========================================================
   FORMATAÇÃO AUTOMÁTICA DO CEP
========================================================= */

const campoCep = document.getElementById("cep");

if (campoCep) {

    campoCep.addEventListener("input", function () {

        let cep = this.value.replace(/\D/g, "");

        cep = cep.substring(0, 8);

        if (cep.length > 5) {
            cep = cep.substring(0, 5) + "-" + cep.substring(5);
        }

        this.value = cep;

    });

}


function mostrarCartao() {


    document
        .getElementById("opcoes-cartao")
        .classList
        .add("ativo");


    document
        .querySelectorAll(
            'input[name="tipo_cartao"]'
        )
        .forEach(
            function(input) {

                input.required = true;

            }
        );

}


function esconderCartao() {


    document
        .getElementById("opcoes-cartao")
        .classList
        .remove("ativo");


    document
        .querySelectorAll(
            'input[name="tipo_cartao"]'
        )
        .forEach(
            function(input) {

                input.required = false;

                input.checked = false;

            }
        );

}


</script>


</body>

</html>
