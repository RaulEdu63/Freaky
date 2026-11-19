<?php

session_start();

include "conexao.php";

/* =========================================================

   VERIFICAR SE JÁ ESTÁ LOGADO

========================================================= */

if (isset($_SESSION["id_cliente"])) {

    header("Location: index.php");

    exit;

}

/* =========================================================

   VARIÁVEIS

========================================================= */

$erro = "";

$email = "";

/* =========================================================

   PROCESSAR LOGIN

========================================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim(

        $_POST["email"] ?? ""

    );

    $senha = $_POST["senha"] ?? "";

    /* =====================================================

       VALIDAR CAMPOS

    ===================================================== */

    if (

        empty($email) ||

        empty($senha)

    ) {

        $erro =

            "Preencha todos os campos.";

    } else {

        /* =================================================

           BUSCAR USUÁRIO

        ================================================= */

        $sql = $conexao->prepare("

            SELECT

                id_cliente,

                nome,

                email,

                senha

            FROM clientes

            WHERE email = ?

            LIMIT 1

        ");

        $sql->bind_param(

            "s",

            $email

        );

        $sql->execute();

        $resultado =

            $sql->get_result();

        $cliente =

            $resultado->fetch_assoc();

        /* =================================================

           VERIFICAR SENHA

        ================================================= */

        if (

            $cliente &&

            password_verify(

                $senha,

                $cliente["senha"]

            )

        ) {

            /* =============================================

               CRIAR SESSÃO

            ============================================= */

            $_SESSION["id_cliente"] =

                $cliente["id_cliente"];

            $_SESSION["nome_usuario"] =

                $cliente["nome"];

            $_SESSION["email_usuario"] =

                $cliente["email"];

            /* =============================================

               GARANTIR CARRINHO

            ============================================= */

            if (

                !isset(

                    $_SESSION["carrinho"]

                )

            ) {

                $_SESSION["carrinho"] = [];

            }

            /* =============================================

               ENTRAR NA LOJA

            ============================================= */

            header(

                "Location: index.php"

            );

            exit;

        } else {

            $erro =

                "E-mail ou senha incorretos.";

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

    Entrar | Freaky Suplements

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

    min-height: 100vh;

    background: #080808;

    color: #ffffff;

    font-family:

        Arial,

        Helvetica,

        sans-serif;

    display: flex;

    flex-direction: column;

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

    font-size: 10px;

    font-weight: bold;

    letter-spacing: .5px;

}

/* =========================================================

   HEADER

========================================================= */

header {

    height: 82px;

    background: #0d0d0d;

    border-bottom: 1px solid #242424;

    display: flex;

    align-items: center;

    justify-content: space-between;

    padding: 0 6%;

}

.logo img {

    width: 150px;

    max-height: 60px;

    object-fit: contain;

}

.voltar {

    color: #cccccc;

    text-decoration: none;

    font-size: 11px;

    font-weight: bold;

    border: 1px solid #333333;

    padding: 11px 17px;

    border-radius: 7px;

    transition: .3s;

}

.voltar:hover {

    color: #ffffff;

    border-color: #ff2525;

    background: #151515;

}

/* =========================================================

   CONTEÚDO

========================================================= */

main {

    flex: 1;

    width: 100%;

    display: flex;

    justify-content: center;

    align-items: center;

    padding: 60px 20px;

}

/* =========================================================

   ÁREA DE LOGIN

========================================================= */

.login-area {

    width: 100%;

    max-width: 430px;

}

/* =========================================================

   TÍTULO

========================================================= */

.titulo {

    text-align: center;

    margin-bottom: 28px;

}

.titulo-mini {

    color: #ff2525;

    font-size: 9px;

    font-weight: bold;

    letter-spacing: 2px;

    margin-bottom: 10px;

}

.titulo h1 {

    font-size: 36px;

    line-height: 1.1;

}

.titulo h1 span {

    color: #ff2525;

}

.titulo p {

    color: #777777;

    font-size: 12px;

    margin-top: 10px;

}

/* =========================================================

   CARD

========================================================= */

.login-card {

    background: #101010;

    border: 1px solid #292929;

    border-radius: 16px;

    padding: 32px;

    box-shadow:

        0 25px 70px

        rgba(0,0,0,.35);

}

/* =========================================================

   ERRO

========================================================= */

.erro {

    background: #1b0b0b;

    border: 1px solid #5b2222;

    border-left: 3px solid #ff2525;

    color: #ff6b6b;

    border-radius: 7px;

    padding: 13px;

    margin-bottom: 22px;

    font-size: 11px;

}

/* =========================================================

   CAMPOS

========================================================= */

.campo {

    margin-bottom: 19px;

}

.campo label {

    display: block;

    color: #cccccc;

    font-size: 10px;

    font-weight: bold;

    letter-spacing: .5px;

    margin-bottom: 8px;

}

.campo input {

    width: 100%;

    height: 48px;

    background: #171717;

    border: 1px solid #333333;

    border-radius: 8px;

    color: #ffffff;

    padding: 0 15px;

    outline: none;

    font-size: 13px;

    transition: .3s;

}

.campo input::placeholder {

    color: #555555;

}

.campo input:focus {

    border-color: #ff2525;

    background: #1a1a1a;

    box-shadow:

        0 0 0 3px

        rgba(255,37,37,.08);

}

/* =========================================================

   BOTÃO

========================================================= */

.botao-login {

    width: 100%;

    height: 49px;

    border: none;

    border-radius: 8px;

    background: #ff2525;

    color: #ffffff;

    font-size: 11px;

    font-weight: bold;

    cursor: pointer;

    transition: .3s;

}

.botao-login:hover {

    background: #d90000;

    transform: translateY(-1px);

}

/* =========================================================

   SEPARADOR

========================================================= */

.separador {

    display: flex;

    align-items: center;

    gap: 12px;

    margin: 25px 0;

}

.separador::before,

.separador::after {

    content: "";

    flex: 1;

    height: 1px;

    background: #292929;

}

.separador span {

    color: #555555;

    font-size: 9px;

    text-transform: uppercase;

}

/* =========================================================

   CADASTRO

========================================================= */

.cadastro {

    text-align: center;

}

.cadastro p {

    color: #777777;

    font-size: 11px;

    margin-bottom: 12px;

}

.botao-cadastro {

    display: flex;

    align-items: center;

    justify-content: center;

    width: 100%;

    height: 46px;

    border: 1px solid #333333;

    border-radius: 8px;

    color: #dddddd;

    text-decoration: none;

    font-size: 10px;

    font-weight: bold;

    transition: .3s;

}

.botao-cadastro:hover {

    border-color: #ff2525;

    color: #ffffff;

    background: #151515;

}

/* =========================================================

   RODAPÉ

========================================================= */

footer {

    background: #0d0d0d;

    border-top: 1px solid #242424;

    text-align: center;

    padding: 20px;

    color: #555555;

    font-size: 9px;

}

/* =========================================================

   RESPONSIVO

========================================================= */

@media (max-width: 600px) {

    header {

        padding: 0 20px;

    }

    .logo img {

        width: 125px;

    }

    .voltar {

        padding: 9px 12px;

        font-size: 9px;

    }

    main {

        padding: 40px 15px;

    }

    .login-card {

        padding: 24px 20px;

    }

    .titulo h1 {

        font-size: 30px;

    }

}



/* =========================================================
   FORMAS DECORATIVAS NO FUNDO
========================================================= */
.fundo-formas {
    position: fixed;
    inset: 0;
    overflow: hidden;
    pointer-events: none;
    z-index: 0;
}

.forma {
    position: absolute;
    bottom: -35px;
    left: var(--left);
    opacity: 0;
    animation: subir-forma var(--duracao) linear var(--atraso) infinite;
    will-change: transform, opacity;
}

.forma.x {
    color: #ff2525;
    font-size: var(--tamanho);
    font-weight: 300;
    line-height: 1;
}

.forma.circulo {
    width: var(--tamanho);
    height: var(--tamanho);
    border: 1px solid rgba(255, 37, 37, .75);
    border-radius: 50%;
}

.forma.quadrado {
    width: var(--tamanho);
    height: var(--tamanho);
    border: 1px solid rgba(255, 37, 37, .7);
    transform: rotate(45deg);
}

.forma.triangulo {
    width: 0;
    height: 0;
    border-left: calc(var(--tamanho) / 2) solid transparent;
    border-right: calc(var(--tamanho) / 2) solid transparent;
    border-bottom: var(--tamanho) solid rgba(255, 37, 37, .7);
}

.forma.triangulo::after {
    content: "";
    position: absolute;
    left: calc(var(--tamanho) / -2 + 1.5px);
    top: 2px;
    width: 0;
    height: 0;
    border-left: calc(var(--tamanho) / 2 - 1.5px) solid transparent;
    border-right: calc(var(--tamanho) / 2 - 1.5px) solid transparent;
    border-bottom: calc(var(--tamanho) - 3px) solid #080808;
}

@keyframes subir-forma {
    0% {
        transform: translate3d(0, 0, 0) rotate(0deg);
        opacity: 0;
    }
    10% { opacity: .55; }
    80% { opacity: .35; }
    100% {
        transform: translate3d(var(--desvio), -110vh, 0) rotate(180deg);
        opacity: 0;
    }
}

/* Mantém o conteúdo do login acima das formas */
.topo, header, main, footer {
    position: relative;
    z-index: 1;
}

@media (prefers-reduced-motion: reduce) {
    .forma {
        animation: none;
        opacity: .2;
    }
}

</style>

</head>

<body>
<div class="fundo-formas" id="fundoFormas" aria-hidden="true"></div>


<header>

    <a

        href="index.php"

        class="logo"

    >

        <img

            src="img/logo.png"

            alt="Freaky Suplements"

        >

    </a>

    <a

        href="index.php"

        class="voltar"

    >

        ← Voltar para a loja

    </a>

</header>

<!-- =========================================================

     CONTEÚDO

========================================================= -->

<main>

    <section class="login-area">

        <div class="titulo">

            <div class="titulo-mini">

                ÁREA DO CLIENTE

            </div>

            <h1>

                Entrar na

                <span>Freaky.</span>

            </h1>

            <p>

                Acesse sua conta para continuar.

            </p>

        </div>

        <div class="login-card">

<?php if (!empty($erro)): ?>

            <div class="erro">

                <?php

                echo htmlspecialchars(

                    $erro

                );

                ?>

            </div>

<?php endif; ?>

            <form

                method="POST"

                action="login.php"

            >

                <div class="campo">

                    <label>

                        E-MAIL

                    </label>

                    <input

                        type="email"

                        name="email"

                        value="<?php

                        echo htmlspecialchars(

                            $email

                        );

                        ?>"

                        placeholder="Digite seu e-mail"

                        autocomplete="email"

                        required

                    >

                </div>

                <div class="campo">

                    <label>

                        SENHA

                    </label>

                    <input

                        type="password"

                        name="senha"

                        placeholder="Digite sua senha"

                        autocomplete="current-password"

                        required

                    >

                </div>

                <button

                    type="submit"

                    class="botao-login"

                >

                    ENTRAR NA MINHA CONTA

                </button>

            </form>

            <div class="separador">

                <span>

                    ou

                </span>

            </div>

            <div class="cadastro">

                <p>

                    Ainda não possui uma conta?

                </p>

                <a

                    href="cadastro.php"

                    class="botao-cadastro"

                >

                    CRIAR UMA CONTA

                </a>

            </div>

        </div>

    </section>

</main>

<!-- =========================================================

     FOOTER

========================================================= -->

<footer>

    © 2026 Freaky Suplements.

    Todos os direitos reservados.

</footer>





<script>
(function () {
    const fundo = document.getElementById("fundoFormas");
    if (!fundo) return;

    const tipos = ["x", "triangulo", "quadrado", "circulo"];
    const quantidade = 32;

    for (let i = 0; i < quantidade; i++) {
        const forma = document.createElement("span");
        const tipo = tipos[Math.floor(Math.random() * tipos.length)];

        forma.className = "forma " + tipo;
        if (tipo === "x") forma.textContent = "×";

        forma.style.setProperty("--left", (Math.random() * 100) + "%");
        forma.style.setProperty("--tamanho", (tipo === "x" ? 9 + Math.random() * 8 : 5 + Math.random() * 9) + "px");
        forma.style.setProperty("--duracao", (12 + Math.random() * 17) + "s");
        forma.style.setProperty("--atraso", (-Math.random() * 28) + "s");
        forma.style.setProperty("--desvio", (-35 + Math.random() * 70) + "px");

        fundo.appendChild(forma);
    }
})();
</script>

</body>

</html>