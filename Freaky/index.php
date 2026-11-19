<?php

session_start();
include "conexao.php";

/* =========================================================
   CONFIGURAÇÕES INICIAIS
   ========================================================= */

$usuario_logado = isset($_SESSION["id_cliente"]);
$nome_usuario = "";

if ($usuario_logado) {
    $nome_usuario = $_SESSION["nome_usuario"] ?? "Cliente";
}

/* =========================================================
   BUSCA DOS PRODUTOS
   ========================================================= */

$produtos = [];

$sql = "
    SELECT
        id_produto,
        nome,
        descricao,
        preco,
        estoque,
        categoria,
        imagem,
        id_categoria
    FROM produtos
    ORDER BY id_produto ASC
";

$resultado = $conexao->query($sql);

if ($resultado && $resultado->num_rows > 0) {

    while ($produto = $resultado->fetch_assoc()) {
        $produtos[] = $produto;
    }
}

/* =========================================================
   CONTADOR DO CARRINHO
   ========================================================= */

$quantidade_carrinho = 0;

if (isset($_SESSION["carrinho"]) && is_array($_SESSION["carrinho"])) {

    foreach ($_SESSION["carrinho"] as $item) {

        $quantidade_carrinho += intval(
            $item["quantidade"] ?? 0
        );
    }
}

/* =========================================================
   CATEGORIAS
   ========================================================= */

$categorias = [];

foreach ($produtos as $produto) {

    $categoria = trim($produto["categoria"] ?? "");

    if ($categoria !== "" && !in_array($categoria, $categorias)) {
        $categorias[] = $categoria;
    }
}

/* =========================================================
   FUNÇÕES AUXILIARES
   ========================================================= */

function limparTexto($texto)
{
    return htmlspecialchars(
        $texto ?? "",
        ENT_QUOTES,
        "UTF-8"
    );
}

function formatarPreco($preco)
{
    return number_format(
        floatval($preco),
        2,
        ",",
        "."
    );
}

function imagemProduto($imagem)
{
    $imagem = trim($imagem ?? "");

    if ($imagem === "") {
        return "img/produto-sem-imagem.png";
    }

    return "img/" . basename($imagem);
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<meta
    name="description"
    content="Freaky Suplements - suplementos, performance e acessórios para seus treinos."
>

<meta
    name="keywords"
    content="suplementos, whey, creatina, vitaminas, academia, treino"
>

<meta
    name="author"
    content="Freaky Suplements"
>

<title>
    Freaky Suplements | Sua evolução começa aqui
</title>

<link
    rel="preconnect"
    href="https://fonts.googleapis.com"
>

<link
    rel="preconnect"
    href="https://fonts.gstatic.com"
    crossorigin
>

<link
    href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap"
    rel="stylesheet"
>

<style>

    /* =====================================================
       RESET
       ===================================================== */

    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    html {
        scroll-behavior: smooth;
    }

    body {
        font-family: "Inter", Arial, sans-serif;
        background: #080808;
        color: #ffffff;
        line-height: 1.6;
        overflow-x: hidden;
    }

    body.modal-aberto {
        overflow: hidden;
    }

    a {
        color: inherit;
        text-decoration: none;
    }

    button,
    input,
    select {
        font-family: inherit;
    }

    button {
        cursor: pointer;
    }

    img {
        max-width: 100%;
        display: block;
    }

    ::selection {
        background: #ff2525;
        color: #ffffff;
    }

    /* =====================================================
       SCROLLBAR
       ===================================================== */

    ::-webkit-scrollbar {
        width: 10px;
    }

    ::-webkit-scrollbar-track {
        background: #0b0b0b;
    }

    ::-webkit-scrollbar-thumb {
        background: #ff2525;
        border-radius: 20px;
    }

    ::-webkit-scrollbar-thumb:hover {
        background: #ff4545;
    }

    /* =====================================================
       VARIÁVEIS
       ===================================================== */

    :root {
        --vermelho: #ff2525;
        --vermelho-escuro: #c91414;
        --vermelho-claro: #ff4545;

        --preto: #080808;
        --preto-2: #0d0d0d;
        --preto-3: #111111;
        --preto-4: #171717;

        --cinza: #999999;
        --cinza-claro: #cccccc;

        --borda: rgba(255,255,255,0.08);

        --sombra:
            0 20px 50px rgba(0,0,0,0.35);

        --transicao:
            all 0.3s ease;
    }

    /* =====================================================
       BARRA SUPERIOR
       ===================================================== */

    .barra-superior {
        width: 100%;
        background: var(--vermelho);
        color: white;
        text-align: center;
        padding: 9px 20px;
        font-size: 13px;
        font-weight: 700;
        letter-spacing: 0.3px;
    }

    /* =====================================================
       HEADER
       ===================================================== */

    header {
        position: sticky;
        top: 0;
        z-index: 1000;

        background:
            rgba(8,8,8,0.94);

        backdrop-filter:
            blur(18px);

        border-bottom:
            1px solid var(--borda);
    }

    .header-principal {
        max-width: 1450px;
        margin: auto;

        min-height: 82px;

        display: flex;
        align-items: center;
        gap: 30px;

        padding:
            12px 30px;
    }

    /* =====================================================
       LOGO
       ===================================================== */

    .logo {
        flex-shrink: 0;
    }

    .logo img {
        width: 165px;
        height: auto;
        object-fit: contain;
    }

    /* =====================================================
       BUSCA
       ===================================================== */

    .area-busca {
        flex: 1;
        max-width: 620px;
        position: relative;
    }

    .busca {
        width: 100%;

        height: 48px;

        background: #121212;

        border:
            1px solid rgba(255,255,255,0.10);

        border-radius: 12px;

        color: white;

        padding:
            0 50px 0 18px;

        outline: none;

        font-size: 14px;

        transition:
            var(--transicao);
    }

    .busca::placeholder {
        color: #777;
    }

    .busca:focus {
        border-color:
            var(--vermelho);

        box-shadow:
            0 0 0 3px
            rgba(255,37,37,0.10);
    }

    .icone-busca {
        position: absolute;
        right: 17px;
        top: 50%;

        transform:
            translateY(-50%);

        font-size: 20px;

        color:
            #777;

        pointer-events: none;
    }

    /* =====================================================
       AÇÕES DO HEADER
       ===================================================== */

    .acoes-header {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .acao-header {
        min-height: 44px;

        display: flex;
        align-items: center;
        gap: 8px;

        padding:
            8px 13px;

        border:
            1px solid var(--borda);

        border-radius: 10px;

        color: #ddd;

        font-size: 13px;

        transition:
            var(--transicao);
    }

    .acao-header:hover {
        background: #151515;
        border-color:
            rgba(255,37,37,0.4);

        color: white;
    }

    .acao-header.destaque {
        border-color:
            rgba(255,37,37,0.45);

        color: #fff;
    }

    .usuario-header {
        display: flex;
        flex-direction: column;
        line-height: 1.2;
    }

    .usuario-header small {
        color: #777;
        font-size: 10px;
    }

    .usuario-header strong {
        color: white;
        font-size: 12px;
    }

    /* =====================================================
       CARRINHO
       ===================================================== */

    .carrinho-header {
        position: relative;
    }

    .contador-carrinho {
        position: absolute;

        top: -7px;
        right: -7px;

        min-width: 20px;
        height: 20px;

        padding: 0 5px;

        display: flex;
        align-items: center;
        justify-content: center;

        background: var(--vermelho);
        color: white;

        border-radius: 50%;

        font-size: 10px;
        font-weight: 800;

        border: 2px solid var(--preto);
    }

    /* =====================================================
       MENU
       ===================================================== */

    .menu-principal {
        background: #0d0d0d;
        border-top:
            1px solid rgba(255,255,255,0.03);

        border-bottom:
            1px solid var(--borda);
    }

    .menu-conteudo {
        max-width: 1450px;
        margin: auto;

        display: flex;
        align-items: center;
        justify-content: center;

        gap: 8px;

        padding:
            0 30px;
    }

    .menu-link {
        position: relative;

        padding:
            15px 22px;

        color: #aaa;

        font-size: 13px;
        font-weight: 600;

        transition:
            var(--transicao);
    }

    .menu-link::after {
        content: "";

        position: absolute;

        left: 20px;
        right: 20px;
        bottom: 0;

        height: 2px;

        background:
            var(--vermelho);

        transform:
            scaleX(0);

        transition:
            var(--transicao);
    }

    .menu-link:hover {
        color: white;
    }

    .menu-link:hover::after {
        transform:
            scaleX(1);
    }

    /* =====================================================
       HERO / BANNERS
       ===================================================== */

    .hero {
        width: 100%;
        position: relative;

        background: #050505;
        overflow: hidden;
    }

    .slider {
        width: 100%;
        position: relative;
    }

    .slides {
        width: 100%;
        position: relative;
    }

    .slide {
        display: none;

        width: 100%;
        position: relative;

        animation:
            aparecerSlide 0.6s ease;
    }

    .slide.ativo {
        display: block;
    }

    .slide img {
        width: 100%;
        height: 520px;

        object-fit: cover;
        object-position: center;

        user-select: none;
    }

    .slide-overlay {
        position: absolute;

        inset: 0;

        background:
            linear-gradient(
                90deg,
                rgba(0,0,0,0.65),
                rgba(0,0,0,0.10),
                rgba(0,0,0,0.10)
            );

        pointer-events: none;
    }

    .slide-conteudo {
        position: absolute;

        left: 7%;
        top: 50%;

        transform:
            translateY(-50%);

        max-width: 560px;
    }

    .slide-tag {
        display: inline-block;

        padding:
            7px 13px;

        background:
            rgba(255,37,37,0.9);

        border-radius: 5px;

        font-size: 11px;
        font-weight: 800;

        text-transform: uppercase;
        letter-spacing: 1px;

        margin-bottom: 14px;
    }

    .slide-conteudo h1 {
        font-size:
            clamp(35px, 5vw, 68px);

        line-height: 1.02;

        font-weight: 900;

        margin-bottom: 18px;
    }

    .slide-conteudo p {
        color: #ddd;

        font-size: 16px;

        max-width: 470px;

        margin-bottom: 25px;
    }

    .botao-hero {
        display: inline-flex;

        align-items: center;
        justify-content: center;

        padding:
            14px 24px;

        background:
            var(--vermelho);

        color: white;

        border-radius: 8px;

        font-size: 13px;
        font-weight: 800;

        transition:
            var(--transicao);
    }

    .botao-hero:hover {
        background:
            var(--vermelho-claro);

        transform:
            translateY(-2px);

        box-shadow:
            0 10px 30px
            rgba(255,37,37,0.22);
    }

    /* =====================================================
       SETAS DO SLIDER
       ===================================================== */

    .slider-seta {
        position: absolute;

        top: 50%;

        transform:
            translateY(-50%);

        width: 48px;
        height: 48px;

        border:
            1px solid
            rgba(255,255,255,0.15);

        border-radius: 50%;

        background:
            rgba(0,0,0,0.45);

        color: white;

        display: flex;
        align-items: center;
        justify-content: center;

        font-size: 25px;

        transition:
            var(--transicao);

        z-index: 5;
    }

    .slider-seta:hover {
        background:
            var(--vermelho);

        border-color:
            var(--vermelho);
    }

    .slider-anterior {
        left: 25px;
    }

    .slider-proximo {
        right: 25px;
    }

    /* =====================================================
       INDICADORES
       ===================================================== */

    .indicadores {
        position: absolute;

        left: 50%;
        bottom: 20px;

        transform:
            translateX(-50%);

        display: flex;
        gap: 8px;

        z-index: 6;
    }

    .indicador {
        width: 28px;
        height: 4px;

        border: 0;

        border-radius: 20px;

        background:
            rgba(255,255,255,0.35);

        transition:
            var(--transicao);
    }

    .indicador.ativo {
        width: 45px;
        background:
            var(--vermelho);
    }

    /* =====================================================
       BENEFÍCIOS
       ===================================================== */

    .beneficios {
        background: #0d0d0d;

        border-bottom:
            1px solid var(--borda);
    }

    .beneficios-grid {
        max-width: 1250px;
        margin: auto;

        display: grid;

        grid-template-columns:
            repeat(4, 1fr);

        gap: 0;
    }

    .beneficio {
        padding:
            27px 20px;

        display: flex;

        align-items: center;

        justify-content: center;

        gap: 13px;

        border-right:
            1px solid var(--borda);
    }

    .beneficio:last-child {
        border-right: 0;
    }

    .beneficio-icone {
        width: 43px;
        height: 43px;

        flex-shrink: 0;

        display: flex;
        align-items: center;
        justify-content: center;

        background:
            rgba(255,37,37,0.10);

        color:
            var(--vermelho);

        border-radius: 10px;

        font-size: 20px;
    }

    .beneficio strong {
        display: block;

        font-size: 12px;
        font-weight: 800;
    }

    .beneficio span {
        display: block;

        color: #777;

        font-size: 10px;
    }

    /* =====================================================
       CONTAINER
       ===================================================== */

    .container {
        width: min(
            1250px,
            calc(100% - 40px)
        );

        margin: auto;
    }

    .secao {
        padding:
            90px 0;
    }

    .cabecalho-secao {
        display: flex;

        align-items: flex-end;
        justify-content: space-between;

        gap: 20px;

        margin-bottom: 38px;
    }

    .cabecalho-secao h2 {
        font-size:
            clamp(28px, 4vw, 42px);

        line-height: 1.1;

        font-weight: 900;
    }

    .cabecalho-secao p {
        color: #777;

        font-size: 13px;

        margin-top: 8px;
    }

    .ver-todos {
        color:
            var(--vermelho);

        font-size: 12px;
        font-weight: 800;

        white-space: nowrap;
    }

    .ver-todos:hover {
        text-decoration: underline;
    }

    /* =====================================================
       CATEGORIAS
       ===================================================== */

    .categorias {
        background: #0a0a0a;
    }

    .categorias-grid {
        display: grid;

        grid-template-columns:
            repeat(4, 1fr);

        gap: 15px;
    }

    .categoria {
        position: relative;

        min-height: 145px;

        padding: 25px;

        display: flex;

        flex-direction: column;

        justify-content: flex-end;

        overflow: hidden;

        border:
            1px solid var(--borda);

        border-radius: 15px;

        background:
            linear-gradient(
                135deg,
                #151515,
                #0d0d0d
            );

        transition:
            var(--transicao);
    }

    .categoria::before {
        content: "";

        position: absolute;

        width: 100px;
        height: 100px;

        right: -30px;
        top: -30px;

        border-radius: 50%;

        background:
            rgba(255,37,37,0.12);

        transition:
            var(--transicao);
    }

    .categoria:hover {
        transform:
            translateY(-5px);

        border-color:
            rgba(255,37,37,0.4);
    }

    .categoria:hover::before {
        transform:
            scale(1.5);
    }

    .categoria-numero {
        color:
            var(--vermelho);

        font-size: 11px;

        font-weight: 800;

        margin-bottom: 5px;
    }

    .categoria h3 {
        font-size: 18px;
        font-weight: 800;
    }

    .categoria p {
        color: #777;

        font-size: 11px;

        margin-top: 3px;
    }

    /* =====================================================
       PRODUTOS
       ===================================================== */

    .produtos {
        background: #080808;
    }

    .filtros-produtos {
        display: flex;

        flex-wrap: wrap;

        gap: 8px;

        margin-bottom: 30px;
    }

    .filtro {
        border:
            1px solid var(--borda);

        background:
            #111;

        color: #888;

        padding:
            9px 15px;

        border-radius: 8px;

        font-size: 11px;
        font-weight: 700;

        transition:
            var(--transicao);
    }

    .filtro:hover,
    .filtro.ativo {
        background:
            var(--vermelho);

        color: white;

        border-color:
            var(--vermelho);
    }

    .ordenacao {
        margin-left: auto;

        border:
            1px solid var(--borda);

        background: #111;

        color: #bbb;

        padding:
            9px 12px;

        border-radius: 8px;

        outline: none;

        font-size: 11px;
    }

    .produtos-topo {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 25px;
    }

    .produtos-contagem {
        color: #666;
        font-size: 11px;
    }

    .grade-produtos {
        display: grid;

        grid-template-columns:
            repeat(4, 1fr);

        gap: 18px;
    }

    .produto {
        position: relative;

        background:
            #101010;

        border:
            1px solid var(--borda);

        border-radius: 14px;

        overflow: hidden;

        transition:
            var(--transicao);
    }

    .produto:hover {
        transform:
            translateY(-6px);

        border-color:
            rgba(255,37,37,0.30);

        box-shadow:
            0 20px 50px
            rgba(0,0,0,0.35);
    }

    .produto.oculto {
        display: none;
    }

    .produto-imagem {
        position: relative;

        height: 270px;

        background:
            #151515;

        overflow: hidden;
    }

    .produto-imagem img {
        width: 100%;
        height: 100%;

        object-fit: contain;

        padding: 25px;

        transition:
            transform 0.5s ease;
    }

    .produto:hover
    .produto-imagem img {
        transform:
            scale(1.07);
    }

    .produto-tag {
        position: absolute;

        left: 12px;
        top: 12px;

        background:
            var(--vermelho);

        color: white;

        padding:
            5px 9px;

        border-radius: 5px;

        font-size: 9px;
        font-weight: 800;

        text-transform: uppercase;
    }

    .produto-visual-botoes {
        position: absolute;

        right: 12px;
        top: 12px;

        display: flex;

        flex-direction: column;

        gap: 6px;
    }

    .produto-icone {
        width: 34px;
        height: 34px;

        border:
            1px solid
            rgba(255,255,255,0.10);

        border-radius: 8px;

        background:
            rgba(0,0,0,0.65);

        color: white;

        display: flex;
        align-items: center;
        justify-content: center;

        transition:
            var(--transicao);
    }

    .produto-icone:hover {
        background:
            var(--vermelho);

        border-color:
            var(--vermelho);
    }

    .produto-info {
        padding: 20px;
    }

    .produto-categoria {
        color:
            var(--vermelho);

        font-size: 9px;

        font-weight: 800;

        text-transform: uppercase;

        letter-spacing: 0.8px;
    }

    .produto h3 {
        font-size: 16px;

        margin:
            5px 0 7px;

        font-weight: 800;
    }

    .produto-descricao {
        color: #777;

        font-size: 11px;

        min-height: 38px;

        display:
            -webkit-box;

        -webkit-line-clamp: 2;

        -webkit-box-orient: vertical;

        overflow: hidden;
    }

    .produto-rodape {
        display: flex;

        align-items: center;

        justify-content: space-between;

        gap: 10px;

        margin-top: 17px;

        padding-top: 15px;

        border-top:
            1px solid var(--borda);
    }

    .produto-preco small {
        display: block;

        color: #666;

        font-size: 9px;
    }

    .produto-preco strong {
        font-size: 20px;

        font-weight: 900;
    }

    .produto-botao {
        width: 43px;
        height: 43px;

        flex-shrink: 0;

        border: 0;

        border-radius: 9px;

        background:
            var(--vermelho);

        color: white;

        display: flex;
        align-items: center;
        justify-content: center;

        font-size: 19px;

        transition:
            var(--transicao);
    }

    .produto-botao:hover {
        background:
            var(--vermelho-claro);

        transform:
            scale(1.05);
    }

    .produto-botao.desativado {
        background:
            #333;

        cursor:
            not-allowed;
    }

    .sem-produtos {
        grid-column: 1 / -1;

        text-align: center;

        padding: 80px 20px;

        border:
            1px solid var(--borda);

        border-radius: 15px;

        background:
            #101010;

        color: #777;
    }

    .sem-produtos strong {
        display: block;

        color: white;

        font-size: 20px;

        margin-bottom: 8px;
    }

    /* =====================================================
       DESTAQUE
       ===================================================== */

    .destaque {
        background:
            linear-gradient(
                135deg,
                #171717,
                #0c0c0c
            );
    }

    .destaque-caixa {
        position: relative;

        overflow: hidden;

        min-height: 300px;

        border:
            1px solid
            rgba(255,37,37,0.18);

        border-radius: 20px;

        padding:
            55px;

        display: flex;

        align-items: center;
    }

    .destaque-caixa::before {
        content: "";

        position: absolute;

        width: 400px;
        height: 400px;

        right: -150px;
        top: -150px;

        border-radius: 50%;

        background:
            rgba(255,37,37,0.10);
    }

    .destaque-conteudo {
        position: relative;

        z-index: 2;

        max-width: 650px;
    }

    .destaque-conteudo span {
        color:
            var(--vermelho);

        font-size: 11px;

        font-weight: 800;

        text-transform: uppercase;

        letter-spacing: 1.2px;
    }

    .destaque-conteudo h2 {
        font-size:
            clamp(30px, 4vw, 48px);

        line-height: 1.1;

        margin:
            10px 0 15px;

        font-weight: 900;
    }

    .destaque-conteudo p {
        color: #888;

        font-size: 14px;

        max-width: 550px;

        margin-bottom: 25px;
    }

    .botao-vermelho {
        display: inline-flex;

        align-items: center;
        justify-content: center;

        background:
            var(--vermelho);

        color: white;

        padding:
            13px 21px;

        border-radius: 8px;

        font-size: 12px;

        font-weight: 800;

        transition:
            var(--transicao);
    }

    .botao-vermelho:hover {
        background:
            var(--vermelho-claro);

        transform:
            translateY(-2px);
    }

    /* =====================================================
       SOBRE
       ===================================================== */

    .sobre {
        background: #0a0a0a;
    }

    .sobre-grid {
        display: grid;

        grid-template-columns:
            1fr 1fr;

        gap: 70px;

        align-items: center;
    }

    .sobre-imagem {
        min-height: 430px;

        border-radius: 20px;

        overflow: hidden;

        background:
            linear-gradient(
                135deg,
                #181818,
                #0d0d0d
            );

        border:
            1px solid var(--borda);

        display: flex;

        align-items: center;

        justify-content: center;

        position: relative;
    }

    .sobre-imagem::before {
        content: "";

        position: absolute;

        width: 230px;
        height: 230px;

        border-radius: 50%;

        background:
            rgba(255,37,37,0.12);
    }

    .sobre-imagem img {
        position: relative;

        z-index: 2;

        width: 65%;

        max-height: 280px;

        object-fit: contain;
    }

    .sobre-texto span {
        color:
            var(--vermelho);

        font-size: 11px;

        font-weight: 800;

        text-transform: uppercase;

        letter-spacing: 1px;
    }

    .sobre-texto h2 {
        font-size:
            clamp(30px, 4vw, 45px);

        line-height: 1.1;

        margin:
            10px 0 18px;

        font-weight: 900;
    }

    .sobre-texto p {
        color: #888;

        font-size: 14px;

        margin-bottom: 15px;
    }

    .sobre-lista {
        display: grid;

        grid-template-columns:
            repeat(2, 1fr);

        gap: 12px;

        margin-top: 25px;
    }

    .sobre-item {
        padding:
            15px;

        background:
            #111;

        border:
            1px solid var(--borda);

        border-radius: 10px;
    }

    .sobre-item strong {
        display: block;

        font-size: 12px;
    }

    .sobre-item span {
        display: block;

        color: #666;

        font-size: 10px;

        text-transform: none;

        letter-spacing: 0;

        margin-top: 2px;
    }

    /* =====================================================
       DIFERENCIAIS
       ===================================================== */

    .diferenciais-grid {
        display: grid;

        grid-template-columns:
            repeat(3, 1fr);

        gap: 18px;
    }

    .diferencial {
        padding: 30px;

        background:
            #101010;

        border:
            1px solid var(--borda);

        border-radius: 15px;

        transition:
            var(--transicao);
    }

    .diferencial:hover {
        transform:
            translateY(-5px);

        border-color:
            rgba(255,37,37,0.3);
    }

    .diferencial-icone {
        width: 50px;
        height: 50px;

        display: flex;
        align-items: center;
        justify-content: center;

        border-radius: 12px;

        background:
            rgba(255,37,37,0.10);

        color:
            var(--vermelho);

        font-size: 23px;

        margin-bottom: 20px;
    }

    .diferencial h3 {
        font-size: 17px;

        margin-bottom: 7px;
    }

    .diferencial p {
        color: #777;

        font-size: 12px;
    }

    /* =====================================================
       AVALIAÇÕES
       ===================================================== */

    .avaliacoes {
        background: #0a0a0a;
    }

    .avaliacoes-grid {
        display: grid;

        grid-template-columns:
            repeat(3, 1fr);

        gap: 18px;
    }

    .avaliacao {
        padding: 25px;

        background:
            #111;

        border:
            1px solid var(--borda);

        border-radius: 15px;
    }

    .estrelas {
        color:
            #ffb000;

        letter-spacing: 2px;

        font-size: 13px;

        margin-bottom: 15px;
    }

    .avaliacao p {
        color: #aaa;

        font-size: 12px;

        line-height: 1.7;

        min-height: 70px;
    }

    .cliente {
        display: flex;

        align-items: center;

        gap: 10px;

        margin-top: 20px;

        padding-top: 15px;

        border-top:
            1px solid var(--borda);
    }

    .cliente-avatar {
        width: 38px;
        height: 38px;

        border-radius: 50%;

        display: flex;
        align-items: center;
        justify-content: center;

        background:
            var(--vermelho);

        font-weight: 900;

        font-size: 13px;
    }

    .cliente strong {
        display: block;

        font-size: 11px;
    }

    .cliente span {
        color: #666;

        font-size: 9px;
    }

    /* =====================================================
       FAQ
       ===================================================== */

    .faq {
        max-width: 900px;

        margin: auto;
    }

    .faq-item {
        border-bottom:
            1px solid var(--borda);
    }

    .faq-pergunta {
        width: 100%;

        border: 0;

        background: transparent;

        color: white;

        padding:
            21px 5px;

        display: flex;

        align-items: center;

        justify-content: space-between;

        text-align: left;

        font-size: 14px;

        font-weight: 700;
    }

    .faq-pergunta span:last-child {
        color:
            var(--vermelho);

        font-size: 20px;

        transition:
            var(--transicao);
    }

    .faq-resposta {
        max-height: 0;

        overflow: hidden;

        transition:
            max-height 0.35s ease;
    }

    .faq-resposta p {
        color: #777;

        font-size: 12px;

        padding:
            0 5px 20px;
    }

    .faq-item.aberto
    .faq-resposta {
        max-height: 200px;
    }

    .faq-item.aberto
    .faq-pergunta span:last-child {
        transform:
            rotate(45deg);
    }

    /* =====================================================
       SUPORTE
       ===================================================== */

    .suporte {
        background:
            #0a0a0a;
    }

    .suporte-grid {
        display: grid;

        grid-template-columns:
            repeat(3, 1fr);

        gap: 18px;
    }

    .suporte-card {
        padding: 28px;

        background:
            #111;

        border:
            1px solid var(--borda);

        border-radius: 15px;

        text-align: center;
    }

    .suporte-icone {
        font-size: 28px;

        margin-bottom: 15px;
    }

    .suporte-card h3 {
        font-size: 16px;

        margin-bottom: 7px;
    }

    .suporte-card p {
        color: #777;

        font-size: 11px;

        margin-bottom: 15px;
    }

    .suporte-card a {
        color:
            var(--vermelho);

        font-size: 11px;

        font-weight: 800;
    }

    /* =====================================================
       NEWSLETTER
       ===================================================== */

    .newsletter {
        padding:
            70px 0;
    }

    .newsletter-caixa {
        padding:
            50px;

        border:
            1px solid
            rgba(255,37,37,0.18);

        border-radius: 20px;

        background:
            linear-gradient(
                135deg,
                #171717,
                #0d0d0d
            );

        display: flex;

        align-items: center;

        justify-content: space-between;

        gap: 40px;
    }

    .newsletter-texto h2 {
        font-size:
            clamp(25px, 4vw, 38px);

        line-height: 1.1;

        margin-bottom: 8px;
    }

    .newsletter-texto p {
        color: #777;

        font-size: 12px;
    }

    .newsletter-form {
        display: flex;

        gap: 8px;

        width: 100%;

        max-width: 480px;
    }

    .newsletter-input {
        flex: 1;

        min-width: 0;

        height: 46px;

        border:
            1px solid var(--borda);

        border-radius: 8px;

        background:
            #080808;

        color: white;

        outline: none;

        padding:
            0 15px;

        font-size: 11px;
    }

    .newsletter-input:focus {
        border-color:
            var(--vermelho);
    }

    .newsletter-botao {
        border: 0;

        background:
            var(--vermelho);

        color: white;

        padding:
            0 20px;

        border-radius: 8px;

        font-size: 11px;

        font-weight: 800;
    }

    /* =====================================================
       FOOTER
       ===================================================== */

    footer {
        background:
            #050505;

        border-top:
            1px solid var(--borda);
    }

    .footer-principal {
        max-width: 1250px;

        margin: auto;

        padding:
            65px 20px 45px;

        display: grid;

        grid-template-columns:
            1.5fr repeat(3, 1fr);

        gap: 45px;
    }

    .footer-logo img {
        width: 155px;

        margin-bottom: 18px;
    }

    .footer-logo p {
        color: #666;

        font-size: 11px;

        max-width: 300px;
    }

    .footer-coluna h3 {
        font-size: 12px;

        margin-bottom: 17px;
    }

    .footer-coluna a {
        display: block;

        color: #666;

        font-size: 11px;

        margin-bottom: 10px;

        transition:
            var(--transicao);
    }

    .footer-coluna a:hover {
        color:
            var(--vermelho);

        transform:
            translateX(3px);
    }

    .footer-base {
        max-width: 1250px;

        margin: auto;

        padding:
            20px;

        border-top:
            1px solid var(--borda);

        display: flex;

        align-items: center;

        justify-content: space-between;

        gap: 20px;
    }

    .footer-base span {
        color: #555;

        font-size: 10px;
    }

    /* =====================================================
       BOTÃO TOPO
       ===================================================== */

    .botao-topo {
        position: fixed;

        right: 20px;
        bottom: 20px;

        width: 43px;
        height: 43px;

        border: 1px solid var(--borda);

        border-radius: 10px;

        background:
            #151515;

        color: white;

        display: flex;

        align-items: center;

        justify-content: center;

        font-size: 18px;

        opacity: 0;

        visibility: hidden;

        transform:
            translateY(15px);

        transition:
            var(--transicao);

        z-index: 900;
    }

    .botao-topo.visivel {
        opacity: 1;

        visibility: visible;

        transform:
            translateY(0);
    }

    .botao-topo:hover {
        background:
            var(--vermelho);

        border-color:
            var(--vermelho);
    }

    /* =====================================================
       MODAL PRODUTO
       ===================================================== */

    .modal-produto {
        position: fixed;

        inset: 0;

        background:
            rgba(0,0,0,0.80);

        backdrop-filter:
            blur(8px);

        display: none;

        align-items: center;
        justify-content: center;

        padding: 20px;

        z-index: 2000;
    }

    .modal-produto.aberto {
        display: flex;
    }

    .modal-caixa {
        width: min(
            850px,
            100%
        );

        max-height:
            90vh;

        overflow-y:
            auto;

        background:
            #101010;

        border:
            1px solid var(--borda);

        border-radius: 18px;

        position: relative;

        animation:
            subirModal 0.3s ease;
    }

    .fechar-modal {
        position: absolute;

        top: 15px;
        right: 15px;

        width: 38px;
        height: 38px;

        border:
            1px solid var(--borda);

        border-radius: 50%;

        background:
            rgba(0,0,0,0.5);

        color: white;

        font-size: 20px;

        z-index: 5;
    }

    .fechar-modal:hover {
        background:
            var(--vermelho);

        border-color:
            var(--vermelho);
    }

    .modal-produto-conteudo {
        display: grid;

        grid-template-columns:
            1fr 1fr;
    }

    .modal-produto-imagem {
        min-height: 450px;

        background:
            #151515;

        display: flex;

        align-items: center;

        justify-content: center;
    }

    .modal-produto-imagem img {
        width: 80%;
        height: 80%;

        object-fit: contain;
    }

    .modal-produto-info {
        padding: 50px 35px;
    }

    .modal-produto-info .categoria-modal {
        color:
            var(--vermelho);

        font-size: 10px;

        font-weight: 800;

        text-transform: uppercase;
    }

    .modal-produto-info h2 {
        font-size: 32px;

        line-height: 1.1;

        margin:
            8px 0 15px;
    }

    .modal-produto-info p {
        color: #777;

        font-size: 13px;

        margin-bottom: 20px;
    }

    .modal-preco {
        font-size: 30px;

        font-weight: 900;

        margin-bottom: 10px;
    }

    .modal-estoque {
        color: #666;

        font-size: 10px;

        margin-bottom: 25px;
    }

    .modal-botao {
        display: flex;

        width: 100%;

        height: 48px;

        align-items: center;
        justify-content: center;

        background:
            var(--vermelho);

        color: white;

        border-radius: 8px;

        font-size: 12px;

        font-weight: 800;
    }

    /* =====================================================
       ANIMAÇÕES
       ===================================================== */

    @keyframes aparecerSlide {

        from {
            opacity: 0;
        }

        to {
            opacity: 1;
        }
    }

    @keyframes subirModal {

        from {
            opacity: 0;
            transform:
                translateY(30px)
                scale(0.98);
        }

        to {
            opacity: 1;
            transform:
                translateY(0)
                scale(1);
        }
    }

    /* =====================================================
       RESPONSIVIDADE
       ===================================================== */

    @media (max-width: 1150px) {

        .header-principal {
            gap: 15px;
        }

        .acoes-header .acao-header span {
            display: none;
        }

        .grade-produtos {
            grid-template-columns:
                repeat(3, 1fr);
        }

        .categorias-grid {
            grid-template-columns:
                repeat(2, 1fr);
        }

    }

    @media (max-width: 900px) {

        .header-principal {
            flex-wrap: wrap;
        }

        .logo {
            order: 1;
        }

        .acoes-header {
            order: 2;

            margin-left: auto;
        }

        .area-busca {
            order: 3;

            max-width: none;

            flex-basis: 100%;
        }

        .header-principal {
            padding-bottom: 15px;
        }

        .menu-conteudo {
            overflow-x: auto;

            justify-content: flex-start;

            scrollbar-width: none;
        }

        .menu-link {
            white-space: nowrap;
        }

        .slide img {
            height: 430px;
        }

        .slide-conteudo {
            left: 8%;
            right: 8%;
        }

        .beneficios-grid {
            grid-template-columns:
                repeat(2, 1fr);
        }

        .beneficio {
            border-bottom:
                1px solid var(--borda);
        }

        .beneficio:nth-child(2) {
            border-right: 0;
        }

        .sobre-grid {
            grid-template-columns: 1fr;

            gap: 35px;
        }

        .diferenciais-grid,
        .avaliacoes-grid,
        .suporte-grid {
            grid-template-columns:
                1fr 1fr;
        }

        .newsletter-caixa {
            flex-direction: column;

            align-items: flex-start;
        }

        .newsletter-form {
            max-width: none;
        }

        .footer-principal {
            grid-template-columns:
                1fr 1fr;
        }

    }

    @media (max-width: 650px) {

        .barra-superior {
            font-size: 10px;
        }

        .header-principal {
            padding:
                10px 15px;
        }

        .logo img {
            width: 130px;
        }

        .acoes-header {
            gap: 5px;
        }

        .acao-header {
            padding:
                8px 9px;
        }

        .menu-conteudo {
            padding:
                0 15px;
        }

        .menu-link {
            padding:
                13px 15px;
        }

        .slide img {
            height: 390px;
        }

        .slide-conteudo {
            left: 25px;
            right: 25px;
        }

        .slide-conteudo h1 {
            font-size: 36px;
        }

        .slide-conteudo p {
            font-size: 12px;
        }

        .slider-seta {
            width: 38px;
            height: 38px;

            font-size: 19px;
        }

        .slider-anterior {
            left: 10px;
        }

        .slider-proximo {
            right: 10px;
        }

        .beneficios-grid {
            grid-template-columns: 1fr;
        }

        .beneficio {
            border-right: 0;
        }

        .categorias-grid {
            grid-template-columns: 1fr;
        }

        .grade-produtos {
            grid-template-columns:
                repeat(2, 1fr);

            gap: 10px;
        }

        .produto-imagem {
            height: 190px;
        }

        .produto-info {
            padding: 13px;
        }

        .produto h3 {
            font-size: 13px;
        }

        .produto-descricao {
            font-size: 9px;
        }

        .produto-preco strong {
            font-size: 16px;
        }

        .produto-botao {
            width: 37px;
            height: 37px;
        }

        .cabecalho-secao {
            flex-direction: column;

            align-items: flex-start;
        }

        .produtos-topo {
            flex-wrap: wrap;
        }

        .ordenacao {
            margin-left: 0;
        }

        .diferenciais-grid,
        .avaliacoes-grid,
        .suporte-grid {
            grid-template-columns: 1fr;
        }

        .sobre-lista {
            grid-template-columns: 1fr;
        }

        .destaque-caixa {
            padding:
                35px 25px;
        }

        .newsletter-caixa {
            padding:
                35px 25px;
        }

        .newsletter-form {
            flex-direction: column;
        }

        .newsletter-botao {
            height: 44px;
        }

        .footer-principal {
            grid-template-columns:
                1fr;
        }

        .footer-base {
            flex-direction: column;

            align-items: flex-start;
        }

        .modal-produto-conteudo {
            grid-template-columns:
                1fr;
        }

        .modal-produto-imagem {
            min-height: 280px;
        }

        .modal-produto-info {
            padding:
                30px 22px;
        }

    }



    /* =====================================================
       ACESSIBILIDADE / TEXTOS MAIORES
       ===================================================== */

    body {
        font-size: 17px;
    }

    .barra-superior {
        font-size: 15px;
        padding: 11px 20px;
    }

    .busca {
        font-size: 16px;
    }

    .acao-header {
        font-size: 15px;
    }

    .usuario-header small {
        font-size: 12px;
    }

    .usuario-header strong {
        font-size: 14px;
    }

    .menu-link {
        font-size: 15px;
    }

    .slide-conteudo p {
        font-size: 19px;
        line-height: 1.65;
    }

    .botao-hero {
        font-size: 16px;
        padding: 15px 26px;
    }

    .beneficio strong {
        font-size: 16px;
    }

    .beneficio span {
        font-size: 14px;
    }

    .cabecalho-secao p {
        font-size: 17px;
        line-height: 1.6;
    }

    .ver-todos {
        font-size: 15px;
    }

    .categoria h3 {
        font-size: 22px;
    }

    .categoria p {
        font-size: 16px;
    }

    .produto-categoria {
        font-size: 14px;
    }

    .produto h3 {
        font-size: 23px;
        line-height: 1.35;
    }

    .produto-descricao {
        font-size: 16px;
        line-height: 1.55;
    }

    .produto-preco small {
        font-size: 14px;
    }

    .produto-preco strong {
        font-size: 27px;
    }

    .produto-botao {
        font-size: 24px;
    }

    .diferencial h3 {
        font-size: 21px;
    }

    .diferencial p {
        font-size: 16px;
        line-height: 1.6;
    }

    .avaliacao p {
        font-size: 16px;
        line-height: 1.6;
    }

    .newsletter-texto p {
        font-size: 17px;
        line-height: 1.6;
    }

    .newsletter-input {
        font-size: 16px;
    }

    .newsletter-botao {
        font-size: 16px;
    }

    .footer-logo p {
        font-size: 15px;
        line-height: 1.6;
    }

    .footer-coluna h3 {
        font-size: 17px;
    }

    .footer-coluna a {
        font-size: 15px;
    }

    .footer-base span {
        font-size: 13px;
    }

    @media (max-width: 650px) {

        .barra-superior {
            font-size: 14px;
        }

        .acao-header {
            font-size: 14px;
        }

        .menu-link {
            font-size: 14px;
        }

        .slide-conteudo p {
            font-size: 17px;
        }

        .cabecalho-secao p {
            font-size: 16px;
        }

        .produto h3 {
            font-size: 21px;
        }

        .produto-descricao {
            font-size: 15px;
        }

        .produto-preco strong {
            font-size: 24px;
        }

        .diferencial p,
        .avaliacao p {
            font-size: 15px;
        }

        .footer-coluna a,
        .footer-logo p {
            font-size: 14px;
        }
    }

</style>

</head>
<body>

<!-- =====================================================
     BARRA SUPERIOR
     ===================================================== -->

<div class="barra-superior">
    FRETE GRÁTIS PARA COMPRAS ACIMA DE R$100
</div>


<!-- =====================================================
     HEADER
     ===================================================== -->

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


        <div class="area-busca">

            <input
                type="text"
                id="campoBusca"
                class="busca"
                placeholder="O que você está procurando?"
                autocomplete="off"
            >

            <span class="icone-busca">
                ⌕
            </span>

        </div>


        <div class="acoes-header">

            <a
                href="rastrear.php"
                class="acao-header"
            >

                <span>
                    🚚
                </span>

                <span>
                    Rastrear pedido
                </span>

            </a>


            <a
                href="carrinho.php"
                class="acao-header destaque carrinho-header"
            >

                <span>
                    🛒
                </span>

                <span>
                    Carrinho
                </span>

                <strong
                    class="contador-carrinho"
                    id="contadorCarrinho"
                >
                    <?php echo $quantidade_carrinho; ?>
                </strong>

            </a>


            <?php if ($usuario_logado): ?>

                <div class="acao-header">

                    <span>
                        👤
                    </span>

                    <div class="usuario-header">

                        <small>
                            Olá
                        </small>

                        <strong>
                            <?php
                            echo limparTexto($nome_usuario);
                            ?>
                        </strong>

                    </div>

                </div>

                <a
                    href="logout.php"
                    class="acao-header"
                >
                    Sair
                </a>

            <?php else: ?>

                <a
                    href="login.php"
                    class="acao-header"
                >
                    Entrar
                </a>

                <a
                    href="cadastro.php"
                    class="acao-header destaque"
                >
                    Criar conta
                </a>

            <?php endif; ?>

        </div>

    </div>


    <!-- =================================================
         MENU
         ================================================= -->

    <nav class="menu-principal">

        <div class="menu-conteudo">

            <a
                href="#inicio"
                class="menu-link"
            >
                Início
            </a>

            <a
                href="#produtos"
                class="menu-link"
            >
                Produtos
            </a>

            <a
                href="#categorias"
                class="menu-link"
            >
                Categorias
            </a>

            <a
                href="#sobre"
                class="menu-link"
            >
                Sobre nós
            </a>

            <a
                href="#diferenciais"
                class="menu-link"
            >
                Diferenciais
            </a>

            <a
                href="#faq"
                class="menu-link"
            >
                FAQ
            </a>

            <a
                href="#suporte"
                class="menu-link"
            >
                Suporte
            </a>

        </div>

    </nav>

</header>


<!-- =====================================================
     HERO
     ===================================================== -->

<main id="inicio">

    <section class="hero">

        <div
            class="slider"
            id="slider"
        >

            <div class="slides">


                <!-- SLIDE 1 -->

                <div class="slide ativo">

                    <img
                        src="img/imagem1.png"
                        alt="Freaky Suplements"
                    >

                    <div class="slide-overlay"></div>

                    <div class="slide-conteudo">

                        <span class="slide-tag">
                            Freaky Suplements
                        </span>

                        <h1>
                            Evolua.
                            Supere.
                            Conquiste.
                        </h1>

                        <p>
                            Suplementos selecionados para
                            acompanhar sua rotina de treino
                            e seus objetivos.
                        </p>

                        <a
                            href="#produtos"
                            class="botao-hero"
                        >
                            Ver produtos
                        </a>

                    </div>

                </div>


                <!-- SLIDE 2 -->

                <div class="slide">

                    <img
                        src="img/imagem2.png"
                        alt="Linha Freaky Suplements"
                    >

                    <div class="slide-overlay"></div>

                    <div class="slide-conteudo">

                        <span class="slide-tag">
                            Linha Freaky
                        </span>

                        <h1>
                            Sua rotina.
                            Seu ritmo.
                            Seu resultado.
                        </h1>

                        <p>
                            Encontre produtos para complementar
                            sua alimentação e sua rotina de
                            treinamento.
                        </p>

                        <a
                            href="#produtos"
                            class="botao-hero"
                        >
                            Explorar catálogo
                        </a>

                    </div>

                </div>

            </div>


            <!-- SETA ANTERIOR -->

            <button
                type="button"
                class="slider-seta slider-anterior"
                id="sliderAnterior"
                aria-label="Banner anterior"
            >
                ‹
            </button>


            <!-- SETA PRÓXIMO -->

            <button
                type="button"
                class="slider-seta slider-proximo"
                id="sliderProximo"
                aria-label="Próximo banner"
            >
                ›
            </button>


            <!-- INDICADORES -->

            <div
                class="indicadores"
                id="indicadores"
            >

                <button
                    type="button"
                    class="indicador ativo"
                    aria-label="Ir para o banner 1"
                ></button>

                <button
                    type="button"
                    class="indicador"
                    aria-label="Ir para o banner 2"
                ></button>

            </div>

        </div>

    </section>


    <!-- =================================================
         BENEFÍCIOS
         ================================================= -->

    <section class="beneficios">

        <div class="beneficios-grid">

            <div class="beneficio">

                <div class="beneficio-icone">
                    ✓
                </div>

                <div>

                    <strong>
                        Compra segura
                    </strong>

                    <span>
                        Ambiente protegido
                    </span>

                </div>

            </div>


            <div class="beneficio">

                <div class="beneficio-icone">
                    🚚
                </div>

                <div>

                    <strong>
                        Entrega rápida
                    </strong>

                    <span>
                        Acompanhe seu pedido
                    </span>

                </div>

            </div>


            <div class="beneficio">

                <div class="beneficio-icone">
                    ★
                </div>

                <div>

                    <strong>
                        Produtos selecionados
                    </strong>

                    <span>
                        Catálogo pensado para você
                    </span>

                </div>

            </div>


            <div class="beneficio">

                <div class="beneficio-icone">
                    ♡
                </div>

                <div>

                    <strong>
                        Atendimento
                    </strong>

                    <span>
                        Suporte quando precisar
                    </span>

                </div>

            </div>

        </div>

    </section>


    <!-- =================================================
         CATEGORIAS
         ================================================= -->

    <section
        class="secao categorias"
        id="categorias"
    >

        <div class="container">

            <div class="cabecalho-secao">

                <div>

                    <h2>
                        Explore por categoria
                    </h2>

                    <p>
                        Encontre produtos de acordo
                        com o que você procura.
                    </p>

                </div>

                <a
                    href="#produtos"
                    class="ver-todos"
                >
                    Ver catálogo →
                </a>

            </div>


            <div class="categorias-grid">

                <?php

                $numero_categoria = 1;

                if (count($categorias) > 0):

                    foreach ($categorias as $categoria):

                ?>

                    <a
                        href="#produtos"
                        class="categoria categoria-link"
                        data-categoria="<?php echo limparTexto($categoria); ?>"
                    >

                        <span class="categoria-numero">
                            0<?php echo $numero_categoria; ?>
                        </span>

                        <h3>
                            <?php
                            echo limparTexto($categoria);
                            ?>
                        </h3>

                        <p>
                            Ver produtos
                        </p>

                    </a>

                <?php

                        $numero_categoria++;

                        if ($numero_categoria > 4) {
                            break;
                        }

                    endforeach;

                else:

                ?>

                    <a
                        href="#produtos"
                        class="categoria categoria-link"
                        data-categoria="Proteínas"
                    >
                        <span class="categoria-numero">
                            01
                        </span>

                        <h3>
                            Proteínas
                        </h3>

                        <p>
                            Ver produtos
                        </p>
                    </a>


                    <a
                        href="#produtos"
                        class="categoria categoria-link"
                        data-categoria="Performance"
                    >
                        <span class="categoria-numero">
                            02
                        </span>

                        <h3>
                            Performance
                        </h3>

                        <p>
                            Ver produtos
                        </p>
                    </a>


                    <a
                        href="#produtos"
                        class="categoria categoria-link"
                        data-categoria="Vitaminas"
                    >
                        <span class="categoria-numero">
                            03
                        </span>

                        <h3>
                            Vitaminas
                        </h3>

                        <p>
                            Ver produtos
                        </p>
                    </a>


                    <a
                        href="#produtos"
                        class="categoria categoria-link"
                        data-categoria="Acessórios"
                    >
                        <span class="categoria-numero">
                            04
                        </span>

                        <h3>
                            Acessórios
                        </h3>

                        <p>
                            Ver produtos
                        </p>
                    </a>

                <?php endif; ?>

            </div>

        </div>

    </section>


    <!-- =================================================
         PRODUTOS
         ================================================= -->

    <section
        class="secao produtos"
        id="produtos"
    >

        <div class="container">

            <div class="cabecalho-secao">

                <div>

                    <h2>
                        Nossos produtos
                    </h2>

                    <p>
                        Confira os produtos disponíveis
                        na Freaky Suplements.
                    </p>

                </div>

            </div>


            <div class="filtros-produtos">

                <button
                    type="button"
                    class="filtro ativo"
                    data-filtro="todos"
                >
                    Todos
                </button>

                <?php foreach ($categorias as $categoria): ?>

                    <button
                        type="button"
                        class="filtro"
                        data-filtro="<?php echo limparTexto($categoria); ?>"
                    >
                        <?php
                        echo limparTexto($categoria);
                        ?>
                    </button>

                <?php endforeach; ?>

                <select
                    id="ordenacao"
                    class="ordenacao"
                >

                    <option value="padrao">
                        Ordenar
                    </option>

                    <option value="menor">
                        Menor preço
                    </option>

                    <option value="maior">
                        Maior preço
                    </option>

                    <option value="az">
                        Nome A-Z
                    </option>

                    <option value="za">
                        Nome Z-A
                    </option>

                </select>

            </div>


            <div class="produtos-topo">

                <span
                    class="produtos-contagem"
                    id="contadorProdutos"
                >
                    <?php
                    echo count($produtos);
                    ?>
                    produtos encontrados
                </span>

            </div>


            <div
                class="grade-produtos"
                id="gradeProdutos"
            >

                <?php if (count($produtos) > 0): ?>

                    <?php foreach ($produtos as $produto): ?>

                        <?php

                        $id = intval(
                            $produto["id_produto"]
                        );

                        $nome = limparTexto(
                            $produto["nome"]
                        );

                        $descricao = limparTexto(
                            $produto["descricao"]
                        );

                        $categoria = limparTexto(
                            $produto["categoria"]
                        );

                        $preco = floatval(
                            $produto["preco"]
                        );

                        $estoque = intval(
                            $produto["estoque"]
                        );

                        $imagem = imagemProduto(
                            $produto["imagem"]
                        );

                        ?>

                        <article
                            class="produto"
                            data-nome="<?php echo $nome; ?>"
                            data-categoria="<?php echo $categoria; ?>"
                            data-preco="<?php echo $preco; ?>"
                            onclick="if (!event.target.closest('a, button')) { window.location.href='produto.php?id=<?php echo $id; ?>'; }"
                            style="cursor: pointer;"
                        >

                            <div class="produto-imagem">

                                <?php if ($estoque > 0): ?>

                                    <span class="produto-tag">
                                        Disponível
                                    </span>

                                <?php else: ?>

                                    <span class="produto-tag">
                                        Esgotado
                                    </span>

                                <?php endif; ?>


                                <div class="produto-visual-botoes">

                                    <a
                                        href="produto.php?id=<?php echo $id; ?>"
                                        class="produto-icone"
                                        title="Ver detalhes"
                                    >
                                        ⌕
                                    </a>

                                </div>


                                <img
                                    src="<?php echo $imagem; ?>"
                                    alt="<?php echo $nome; ?>"
                                    onerror="this.src='img/produto-sem-imagem.png';"
                                >

                            </div>


                            <div class="produto-info">

                                <span class="produto-categoria">
                                    <?php echo $categoria; ?>
                                </span>

                                <h3>
                                    <?php echo $nome; ?>
                                </h3>

                                <p class="produto-descricao">
                                    <?php echo $descricao; ?>
                                </p>


                                <div class="produto-rodape">

                                    <div class="produto-preco">

                                        <small>
                                            Por apenas
                                        </small>

                                        <strong>
                                            R$
                                            <?php
                                            echo formatarPreco(
                                                $preco
                                            );
                                            ?>
                                        </strong>

                                    </div>


                                    <?php if ($estoque > 0): ?>

                                        <a
                                            href="carrinho.php?adicionar=<?php echo $id; ?>"
                                            class="produto-botao"
                                            title="Adicionar ao carrinho"
                                        >
                                            +
                                        </a>

                                    <?php else: ?>

                                        <button
                                            type="button"
                                            class="produto-botao desativado"
                                            disabled
                                        >
                                            ×
                                        </button>

                                    <?php endif; ?>

                                </div>

                            </div>

                        </article>

                    <?php endforeach; ?>

                <?php else: ?>

                    <div class="sem-produtos">

                        <strong>
                            Nenhum produto encontrado
                        </strong>

                        <span>
                            Cadastre produtos na tabela
                            produtos do banco Freaky.
                        </span>

                    </div>

                <?php endif; ?>

            </div>

        </div>

    </section>


    <!-- =================================================
         DESTAQUE
         ================================================= -->

    <section class="secao destaque">

        <div class="container">

            <div class="destaque-caixa">

                <div class="destaque-conteudo">

                    <span>
                        Freaky Suplements
                    </span>

                    <h2>
                        Seu objetivo começa
                        com sua rotina.
                    </h2>

                    <p>
                        Encontre suplementos e acessórios
                        para complementar seu dia a dia
                        e acompanhar seus treinos.
                    </p>

                    <a
                        href="#produtos"
                        class="botao-vermelho"
                    >
                        Conhecer produtos
                    </a>

                </div>

            </div>

        </div>

    </section>


    <!-- =================================================
         SOBRE
         ================================================= -->

    <section
        class="secao sobre"
        id="sobre"
    >

        <div class="container">

            <div class="sobre-grid">

                <div class="sobre-imagem">

                    <img
                        src="img/logo.png"
                        alt="Freaky Suplements"
                    >

                </div>


                <div class="sobre-texto">

                    <span>
                        Sobre a Freaky
                    </span>

                    <h2>
                        Mais que produtos.
                        Uma rotina.
                    </h2>

                    <p>
                        A Freaky Suplements foi pensada
                        para oferecer uma experiência
                        simples e moderna para quem busca
                        produtos relacionados a treino,
                        performance e bem-estar.
                    </p>

                    <p>
                        Nosso objetivo é facilitar sua
                        escolha, apresentar os produtos de
                        forma clara e deixar sua compra
                        mais prática.
                    </p>


                    <div class="sobre-lista">

                        <div class="sobre-item">

                            <strong>
                                Catálogo organizado
                            </strong>

                            <span>
                                Produtos separados por categoria.
                            </span>

                        </div>


                        <div class="sobre-item">

                            <strong>
                                Compra prática
                            </strong>

                            <span>
                                Processo simples e direto.
                            </span>

                        </div>


                        <div class="sobre-item">

                            <strong>
                                Acompanhamento
                            </strong>

                            <span>
                                Consulte seus pedidos.
                            </span>

                        </div>


                        <div class="sobre-item">

                            <strong>
                                Atendimento
                            </strong>

                            <span>
                                Suporte para suas dúvidas.
                            </span>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </section>


    <!-- =================================================
         DIFERENCIAIS
         ================================================= -->

    <section
        class="secao"
        id="diferenciais"
    >

        <div class="container">

            <div class="cabecalho-secao">

                <div>

                    <h2>
                        Por que Freaky?
                    </h2>

                    <p>
                        Uma experiência pensada para
                        deixar sua compra mais simples.
                    </p>

                </div>

            </div>


            <div class="diferenciais-grid">

                <div class="diferencial">

                    <div class="diferencial-icone">
                        ⚡
                    </div>

                    <h3>
                        Navegação rápida
                    </h3>

                    <p>
                        Encontre produtos rapidamente
                        através da busca e dos filtros.
                    </p>

                </div>


                <div class="diferencial">

                    <div class="diferencial-icone">
                        ◈
                    </div>

                    <h3>
                        Informações claras
                    </h3>

                    <p>
                        Cada produto possui informações
                        organizadas para facilitar sua escolha.
                    </p>

                </div>


                <div class="diferencial">

                    <div class="diferencial-icone">
                        ✓
                    </div>

                    <h3>
                        Experiência simples
                    </h3>

                    <p>
                        Do catálogo ao carrinho,
                        tudo foi pensado para ser fácil de usar.
                    </p>

                </div>

            </div>

        </div>

    </section>


    <!-- =================================================
         AVALIAÇÕES
         ================================================= -->

    <section class="secao avaliacoes">

        <div class="container">

            <div class="cabecalho-secao">

                <div>

                    <h2>
                        O que nossos clientes dizem
                    </h2>

                    <p>
                        Alguns exemplos de avaliações
                        exibidas na loja.
                    </p>

                </div>

            </div>


            <div class="avaliacoes-grid">

                <div class="avaliacao">

                    <div class="estrelas">
                        ★★★★★
                    </div>

                    <p>
                        "Gostei bastante da organização
                        do site e foi fácil encontrar
                        o produto que eu estava procurando."
                    </p>

                    <div class="cliente">

                        <div class="cliente-avatar">
                            M
                        </div>

                        <div>

                            <strong>
                                Marcos
                            </strong>

                            <span>
                                Cliente Freaky
                            </span>

                        </div>

                    </div>

                </div>


                <div class="avaliacao">

                    <div class="estrelas">
                        ★★★★★
                    </div>

                    <p>
                        "A experiência de compra é simples
                        e o catálogo deixa as informações
                        bem organizadas."
                    </p>

                    <div class="cliente">

                        <div class="cliente-avatar">
                            A
                        </div>

                        <div>

                            <strong>
                                Amanda
                            </strong>

                            <span>
                                Cliente Freaky
                            </span>

                        </div>

                    </div>

                </div>


                <div class="avaliacao">

                    <div class="estrelas">
                        ★★★★★
                    </div>

                    <p>
                        "O site ficou bonito, rápido e
                        fácil de navegar pelo celular."
                    </p>

                    <div class="cliente">

                        <div class="cliente-avatar">
                            R
                        </div>

                        <div>

                            <strong>
                                Rafael
                            </strong>

                            <span>
                                Cliente Freaky
                            </span>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </section>


    <!-- =================================================
         FAQ
         ================================================= -->

    <section
        class="secao"
        id="faq"
    >

        <div class="container">

            <div class="cabecalho-secao">

                <div>

                    <h2>
                        Perguntas frequentes
                    </h2>

                    <p>
                        Confira algumas dúvidas comuns.
                    </p>

                </div>

            </div>


            <div class="faq">


                <div class="faq-item">

                    <button
                        type="button"
                        class="faq-pergunta"
                    >

                        <span>
                            Como faço uma compra?
                        </span>

                        <span>
                            +
                        </span>

                    </button>

                    <div class="faq-resposta">

                        <p>
                            Escolha o produto desejado,
                            adicione ao carrinho e siga
                            para a finalização do pedido.
                        </p>

                    </div>

                </div>


                <div class="faq-item">

                    <button
                        type="button"
                        class="faq-pergunta"
                    >

                        <span>
                            Posso acompanhar meu pedido?
                        </span>

                        <span>
                            +
                        </span>

                    </button>

                    <div class="faq-resposta">

                        <p>
                            Sim. Depois de realizar uma
                            compra, você pode utilizar a
                            área de rastreamento para
                            consultar o status do pedido.
                        </p>

                    </div>

                </div>


                <div class="faq-item">

                    <button
                        type="button"
                        class="faq-pergunta"
                    >

                        <span>
                            Preciso criar uma conta?
                        </span>

                        <span>
                            +
                        </span>

                    </button>

                    <div class="faq-resposta">

                        <p>
                            Para finalizar uma compra,
                            é necessário estar conectado
                            a uma conta cadastrada no site.
                        </p>

                    </div>

                </div>


                <div class="faq-item">

                    <button
                        type="button"
                        class="faq-pergunta"
                    >

                        <span>
                            Como vejo meus produtos no carrinho?
                        </span>

                        <span>
                            +
                        </span>

                    </button>

                    <div class="faq-resposta">

                        <p>
                            Clique no botão Carrinho no
                            topo da página para visualizar
                            os produtos adicionados.
                        </p>

                    </div>

                </div>

            </div>

        </div>

    </section>


    <!-- =================================================
         SUPORTE
         ================================================= -->

    <section
        class="secao suporte"
        id="suporte"
    >

        <div class="container">

            <div class="cabecalho-secao">

                <div>

                    <h2>
                        Precisa de ajuda?
                    </h2>

                    <p>
                        Encontre uma forma de entrar em contato.
                    </p>

                </div>

            </div>


            <div class="suporte-grid">

                <div class="suporte-card">

                    <div class="suporte-icone">
                        ✉
                    </div>

                    <h3>
                        E-mail
                    </h3>

                    <p>
                        Envie sua dúvida para nossa equipe.
                    </p>

                    <a href="mailto:contato@freakysuplementos.com">
                        Entrar em contato
                    </a>

                </div>


                <div class="suporte-card">

                    <div class="suporte-icone">
                        ?
                    </div>

                    <h3>
                        Central de ajuda
                    </h3>

                    <p>
                        Confira as perguntas frequentes
                        antes de entrar em contato.
                    </p>

                    <a href="#faq">
                        Ver perguntas
                    </a>

                </div>


                <div class="suporte-card">

                    <div class="suporte-icone">
                        🚚
                    </div>

                    <h3>
                        Rastreamento
                    </h3>

                    <p>
                        Consulte o andamento do seu pedido.
                    </p>

                    <a href="rastrear.php">
                        Rastrear pedido
                    </a>

                </div>

            </div>

        </div>

    </section>


    <!-- =================================================
         NEWSLETTER
         ================================================= -->

    <section class="newsletter">

        <div class="container">

            <div class="newsletter-caixa">

                <div class="newsletter-texto">

                    <h2>
                        Fique por dentro.
                    </h2>

                    <p>
                        Receba novidades e informações
                        sobre a Freaky Suplements.
                    </p>

                </div>


                <form
                    class="newsletter-form"
                    onsubmit="enviarNewsletter(event)"
                >

                    <input
                        type="email"
                        class="newsletter-input"
                        placeholder="Seu melhor e-mail"
                        required
                    >

                    <button
                        type="submit"
                        class="newsletter-botao"
                    >
                        Cadastrar
                    </button>

                </form>

            </div>

        </div>

    </section>

</main>


<!-- =====================================================
     FOOTER
     ===================================================== -->

<footer>

    <div class="footer-principal">


        <div class="footer-logo">

            <img
                src="img/logo.png"
                alt="Freaky Suplements"
            >

            <p>
                Freaky Suplements.
                Produtos, performance e uma
                experiência de compra moderna.
            </p>

        </div>


        <div class="footer-coluna">

            <h3>
                Loja
            </h3>

            <a href="#produtos">
                Produtos
            </a>

            <a href="#categorias">
                Categorias
            </a>

            <a href="carrinho.php">
                Carrinho
            </a>

            <a href="rastrear.php">
                Rastrear pedido
            </a>

        </div>


        <div class="footer-coluna">

            <h3>
                Conta
            </h3>

            <?php if ($usuario_logado): ?>

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

            <a href="#faq">
                Perguntas frequentes
            </a>

        </div>


        <div class="footer-coluna">

            <h3>
                Empresa
            </h3>

            <a href="#sobre">
                Sobre nós
            </a>

            <a href="#diferenciais">
                Diferenciais
            </a>

            <a href="#suporte">
                Suporte
            </a>

            <a href="mailto:contato@freakysuplementos.com">
                Contato
            </a>

        </div>

    </div>


    <div class="footer-base">

        <span>
            © <?php echo date("Y"); ?>
            Freaky Suplements.
            Todos os direitos reservados.
        </span>

        <span>
            Desenvolvido para a Freaky Suplements.
        </span>

    </div>

</footer>


<!-- =====================================================
     BOTÃO VOLTAR AO TOPO
     ===================================================== -->

<button
    type="button"
    class="botao-topo"
    id="botaoTopo"
    aria-label="Voltar ao topo"
>
    ↑
</button>


<!-- =====================================================
     MODAL DE PRODUTO
     ===================================================== -->

<div
    class="modal-produto"
    id="modalProduto"
>

    <div class="modal-caixa">

        <button
            type="button"
            class="fechar-modal"
            onclick="fecharProduto()"
            aria-label="Fechar"
        >
            ×
        </button>


        <div class="modal-produto-conteudo">

            <div class="modal-produto-imagem">

                <img
                    id="modalImagem"
                    src=""
                    alt=""
                >

            </div>


            <div class="modal-produto-info">

                <span
                    class="categoria-modal"
                    id="modalCategoria"
                ></span>

                <h2 id="modalNome"></h2>

                <p id="modalDescricao"></p>

                <div
                    class="modal-preco"
                    id="modalPreco"
                ></div>

                <div
                    class="modal-estoque"
                    id="modalEstoque"
                ></div>

                <a
                    href="#"
                    id="modalAdicionar"
                    class="modal-botao"
                >
                    Adicionar ao carrinho
                </a>

            </div>

        </div>

    </div>

</div>


<!-- =====================================================
     JAVASCRIPT
     ===================================================== -->

<script>

    /* =====================================================
       DADOS DOS PRODUTOS
       ===================================================== */

    const produtos = [

        <?php

        $dadosJavascript = [];

        foreach ($produtos as $produto) {

            $dadosJavascript[] = [

                "id" =>
                    intval(
                        $produto["id_produto"]
                    ),

                "nome" =>
                    $produto["nome"],

                "descricao" =>
                    $produto["descricao"],

                "preco" =>
                    floatval(
                        $produto["preco"]
                    ),

                "estoque" =>
                    intval(
                        $produto["estoque"]
                    ),

                "categoria" =>
                    $produto["categoria"],

                "imagem" =>
                    imagemProduto(
                        $produto["imagem"]
                    )
            ];
        }

        echo json_encode(
            $dadosJavascript,
            JSON_UNESCAPED_UNICODE |
            JSON_UNESCAPED_SLASHES
        );

        ?>

    ];


    /* =====================================================
       SLIDER
       ===================================================== */

    const slides =
        document.querySelectorAll(".slide");

    const indicadores =
        document.querySelectorAll(".indicador");

    let slideAtual = 0;

    let intervaloSlider;


    function mostrarSlide(numero) {

        slides.forEach(
            function(slide, indice) {

                slide.classList.toggle(
                    "ativo",
                    indice === numero
                );

            }
        );


        indicadores.forEach(
            function(indicador, indice) {

                indicador.classList.toggle(
                    "ativo",
                    indice === numero
                );

            }
        );

        slideAtual = numero;
    }


    function proximoSlide() {

        let proximo =
            slideAtual + 1;

        if (proximo >= slides.length) {
            proximo = 0;
        }

        mostrarSlide(proximo);

    }


    function slideAnterior() {

        let anterior =
            slideAtual - 1;

        if (anterior < 0) {
            anterior =
                slides.length - 1;
        }

        mostrarSlide(anterior);

    }


    function iniciarSlider() {

        intervaloSlider =
            setInterval(
                proximoSlide,
                5000
            );

    }


    function reiniciarSlider() {

        clearInterval(
            intervaloSlider
        );

        iniciarSlider();

    }


    document
        .getElementById("sliderProximo")
        .addEventListener(
            "click",
            function() {

                proximoSlide();

                reiniciarSlider();

            }
        );


    document
        .getElementById("sliderAnterior")
        .addEventListener(
            "click",
            function() {

                slideAnterior();

                reiniciarSlider();

            }
        );


    indicadores.forEach(
        function(indicador, indice) {

            indicador.addEventListener(
                "click",
                function() {

                    mostrarSlide(indice);

                    reiniciarSlider();

                }
            );

        }
    );


    iniciarSlider();


    /* =====================================================
       BUSCA
       ===================================================== */

    const campoBusca =
        document.getElementById(
            "campoBusca"
        );


    campoBusca.addEventListener(
        "input",
        function() {

            const texto =
                this.value
                    .toLowerCase()
                    .trim();

            const cards =
                document.querySelectorAll(
                    ".produto"
                );

            let encontrados = 0;


            cards.forEach(
                function(card) {

                    const nome =
                        card.dataset.nome
                            .toLowerCase();

                    const categoria =
                        card.dataset.categoria
                            .toLowerCase();

                    const corresponde =
                        nome.includes(texto) ||
                        categoria.includes(texto);

                    card.classList.toggle(
                        "oculto",
                        !corresponde
                    );

                    if (corresponde) {
                        encontrados++;
                    }

                }
            );


            atualizarContador(
                encontrados
            );

        }
    );


    function atualizarContador(numero) {

        const contador =
            document.getElementById(
                "contadorProdutos"
            );

        contador.textContent =
            numero +
            (
                numero === 1
                    ? " produto encontrado"
                    : " produtos encontrados"
            );

    }


    /* =====================================================
       FILTROS
       ===================================================== */

    const filtros =
        document.querySelectorAll(
            ".filtro"
        );


    filtros.forEach(
        function(filtro) {

            filtro.addEventListener(
                "click",
                function() {

                    filtros.forEach(
                        function(item) {

                            item.classList.remove(
                                "ativo"
                            );

                        }
                    );


                    this.classList.add(
                        "ativo"
                    );


                    const categoria =
                        this.dataset.filtro
                            .toLowerCase();

                    const cards =
                        document.querySelectorAll(
                            ".produto"
                        );

                    let encontrados = 0;


                    cards.forEach(
                        function(card) {

                            const cardCategoria =
                                card.dataset.categoria
                                    .toLowerCase();

                            const mostrar =
                                categoria === "todos" ||
                                cardCategoria === categoria;

                            card.classList.toggle(
                                "oculto",
                                !mostrar
                            );

                            if (mostrar) {
                                encontrados++;
                            }

                        }
                    );


                    campoBusca.value = "";

                    atualizarContador(
                        encontrados
                    );

                    document
                        .getElementById("produtos")
                        .scrollIntoView({
                            behavior: "smooth"
                        });

                }
            );

        }
    );


    /* =====================================================
       ORDENAÇÃO
       ===================================================== */

    const ordenacao =
        document.getElementById(
            "ordenacao"
        );


    ordenacao.addEventListener(
        "change",
        function() {

            const valor =
                this.value;

            const grade =
                document.getElementById(
                    "gradeProdutos"
                );

            const cards =
                Array.from(
                    grade.querySelectorAll(
                        ".produto"
                    )
                );


            cards.sort(
                function(a, b) {

                    if (valor === "menor") {

                        return (
                            parseFloat(
                                a.dataset.preco
                            )
                            -
                            parseFloat(
                                b.dataset.preco
                            )
                        );

                    }


                    if (valor === "maior") {

                        return (
                            parseFloat(
                                b.dataset.preco
                            )
                            -
                            parseFloat(
                                a.dataset.preco
                            )
                        );

                    }


                    if (valor === "az") {

                        return a.dataset.nome
                            .localeCompare(
                                b.dataset.nome,
                                "pt-BR"
                            );

                    }


                    if (valor === "za") {

                        return b.dataset.nome
                            .localeCompare(
                                a.dataset.nome,
                                "pt-BR"
                            );

                    }


                    return 0;

                }
            );


            cards.forEach(
                function(card) {

                    grade.appendChild(
                        card
                    );

                }
            );

        }
    );


    /* =====================================================
       CATEGORIAS
       ===================================================== */

    const categoriaLinks =
        document.querySelectorAll(
            ".categoria-link"
        );


    categoriaLinks.forEach(
        function(link) {

            link.addEventListener(
                "click",
                function(event) {

                    event.preventDefault();

                    const categoria =
                        this.dataset.categoria;

                    filtros.forEach(
                        function(filtro) {

                            if (
                                filtro.dataset.filtro
                                    .toLowerCase()
                                ===
                                categoria.toLowerCase()
                            ) {

                                filtro.click();

                            }

                        }
                    );

                }
            );

        }
    );


    /* =====================================================
       MODAL DE PRODUTO
       ===================================================== */

    const modal =
        document.getElementById(
            "modalProduto"
        );


    const modalImagem =
        document.getElementById(
            "modalImagem"
        );


    const modalNome =
        document.getElementById(
            "modalNome"
        );


    const modalDescricao =
        document.getElementById(
            "modalDescricao"
        );


    const modalPreco =
        document.getElementById(
            "modalPreco"
        );


    const modalCategoria =
        document.getElementById(
            "modalCategoria"
        );


    const modalEstoque =
        document.getElementById(
            "modalEstoque"
        );


    const modalAdicionar =
        document.getElementById(
            "modalAdicionar"
        );


    function abrirProduto(id) {

        const produto =
            produtos.find(
                function(item) {

                    return item.id === id;

                }
            );


        if (!produto) {
            return;
        }


        modalImagem.src =
            produto.imagem;

        modalImagem.alt =
            produto.nome;

        modalNome.textContent =
            produto.nome;

        modalDescricao.textContent =
            produto.descricao;

        modalCategoria.textContent =
            produto.categoria;

        modalPreco.textContent =
            "R$ " +
            produto.preco.toLocaleString(
                "pt-BR",
                {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                }
            );


        if (produto.estoque > 0) {

            modalEstoque.textContent =
                produto.estoque +
                " unidades disponíveis";

            modalAdicionar.href =
                "carrinho.php?adicionar=" +
                produto.id;

            modalAdicionar.style.display =
                "flex";

        } else {

            modalEstoque.textContent =
                "Produto esgotado";

            modalAdicionar.style.display =
                "none";

        }


        modal.classList.add(
            "aberto"
        );

        document.body.classList.add(
            "modal-aberto"
        );

    }


    function fecharProduto() {

        modal.classList.remove(
            "aberto"
        );

        document.body.classList.remove(
            "modal-aberto"
        );

    }


    modal.addEventListener(
        "click",
        function(event) {

            if (event.target === modal) {
                fecharProduto();
            }

        }
    );


    document.addEventListener(
        "keydown",
        function(event) {

            if (event.key === "Escape") {
                fecharProduto();
            }

        }
    );


    /* =====================================================
       FAQ
       ===================================================== */

    const perguntas =
        document.querySelectorAll(
            ".faq-pergunta"
        );


    perguntas.forEach(
        function(pergunta) {

            pergunta.addEventListener(
                "click",
                function() {

                    const item =
                        this.parentElement;

                    const todos =
                        document.querySelectorAll(
                            ".faq-item"
                        );


                    todos.forEach(
                        function(outro) {

                            if (
                                outro !== item
                            ) {

                                outro.classList.remove(
                                    "aberto"
                                );

                            }

                        }
                    );


                    item.classList.toggle(
                        "aberto"
                    );

                }
            );

        }
    );


    /* =====================================================
       BOTÃO VOLTAR AO TOPO
       ===================================================== */

    const botaoTopo =
        document.getElementById(
            "botaoTopo"
        );


    window.addEventListener(
        "scroll",
        function() {

            if (window.scrollY > 500) {

                botaoTopo.classList.add(
                    "visivel"
                );

            } else {

                botaoTopo.classList.remove(
                    "visivel"
                );

            }

        }
    );


    botaoTopo.addEventListener(
        "click",
        function() {

            window.scrollTo(
                {
                    top: 0,
                    behavior: "smooth"
                }
            );

        }
    );


    /* =====================================================
       NEWSLETTER
       ===================================================== */

    function enviarNewsletter(event) {

        event.preventDefault();

        alert(
            "Cadastro realizado com sucesso!"
        );

        event.target.reset();

    }


    /* =====================================================
       LINKS SUAVES
       ===================================================== */

    document
        .querySelectorAll(
            'a[href^="#"]'
        )
        .forEach(
            function(link) {

                link.addEventListener(
                    "click",
                    function(event) {

                        const destino =
                            this.getAttribute(
                                "href"
                            );

                        if (
                            destino === "#" ||
                            destino === ""
                        ) {
                            return;
                        }


                        const elemento =
                            document.querySelector(
                                destino
                            );


                        if (!elemento) {
                            return;
                        }


                        event.preventDefault();


                        elemento.scrollIntoView(
                            {
                                behavior: "smooth",
                                block: "start"
                            }
                        );

                    }
                );

            }
        );


    /* =====================================================
       ANIMAÇÃO DOS CARDS
       ===================================================== */

    const elementosAnimados =
        document.querySelectorAll(
            ".produto, .diferencial, .avaliacao, .suporte-card"
        );


    const observador =
        new IntersectionObserver(
            function(elementos) {

                elementos.forEach(
                    function(elemento) {

                        if (
                            elemento.isIntersecting
                        ) {

                            elemento.target.style.opacity =
                                "1";

                            elemento.target.style.transform =
                                "translateY(0)";

                            observador.unobserve(
                                elemento.target
                            );

                        }

                    }
                );

            },
            {
                threshold: 0.08
            }
        );


    elementosAnimados.forEach(
        function(elemento) {

            elemento.style.opacity = "0";

            elemento.style.transform =
                "translateY(15px)";

            elemento.style.transition =
                "opacity 0.5s ease, transform 0.5s ease";

            observador.observe(
                elemento
            );

        }
    );

</script>

</body>
</html>
