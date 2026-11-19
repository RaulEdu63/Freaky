<?php

session_start();

include "conexao.php";


/* =========================================================
   VARIÁVEIS
========================================================= */

$erro = "";

$sucesso = "";

$nome = "";

$email = "";


/* =========================================================
   PROCESSAR CADASTRO
========================================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {


    $nome = trim(
        $_POST["nome"] ?? ""
    );


    $email = trim(
        $_POST["email"] ?? ""
    );


    $senha = $_POST["senha"] ?? "";


    $confirmar_senha =
        $_POST["confirmar_senha"] ?? "";


    /* =====================================================
       VALIDAR CAMPOS
    ===================================================== */

    if (
        empty($nome) ||
        empty($email) ||
        empty($senha) ||
        empty($confirmar_senha)
    ) {

        $erro =
            "Preencha todos os campos.";

    }


    /* =====================================================
       VALIDAR E-MAIL
    ===================================================== */

    elseif (!filter_var(
        $email,
        FILTER_VALIDATE_EMAIL
    )) {

        $erro =
            "Digite um e-mail válido.";

    }


    /* =====================================================
       VALIDAR SENHA
    ===================================================== */

    elseif (strlen($senha) < 6) {

        $erro =
            "A senha deve ter pelo menos 6 caracteres.";

    }


    /* =====================================================
       CONFIRMAR SENHA
    ===================================================== */

    elseif (
        $senha !== $confirmar_senha
    ) {

        $erro =
            "As senhas não são iguais.";

    }


    /* =====================================================
       VERIFICAR E-MAIL EXISTENTE
    ===================================================== */

    else {


        $verificar =
            $conexao->prepare("
                SELECT id_cliente
                FROM clientes
                WHERE email = ?
                LIMIT 1
            ");


        $verificar->bind_param(
            "s",
            $email
        );


        $verificar->execute();


        $resultado =
            $verificar->get_result();


        if (
            $resultado->num_rows > 0
        ) {

            $erro =
                "Este e-mail já está cadastrado.";

        }


        /* =================================================
           CADASTRAR
        ================================================= */

        else {


            $senha_hash =
                password_hash(
                    $senha,
                    PASSWORD_DEFAULT
                );


            $cadastrar =
                $conexao->prepare("
                    INSERT INTO clientes
                    (
                        nome,
                        email,
                        senha
                    )
                    VALUES
                    (
                        ?,
                        ?,
                        ?
                    )
                ");


            $cadastrar->bind_param(
                "sss",
                $nome,
                $email,
                $senha_hash
            );


            if (
                $cadastrar->execute()
            ) {


                /* =========================================
                   ENTRAR AUTOMATICAMENTE
                ========================================= */

                $_SESSION["id_cliente"] =
                    $cadastrar->insert_id;


                $_SESSION["nome_usuario"] =
                    $nome;


                $_SESSION["email_usuario"] =
                    $email;


                if (
                    !isset(
                        $_SESSION["carrinho"]
                    )
                ) {

                    $_SESSION["carrinho"] = [];

                }


                header(
                    "Location: index.php"
                );

                exit;


            } else {

                $erro =
                    "Não foi possível realizar o cadastro.";

            }

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
    Criar Conta | Freaky Suplements
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

    padding: 55px 20px;

}


/* =========================================================
   ÁREA
========================================================= */

.cadastro-area {

    width: 100%;

    max-width: 460px;

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

.cadastro-card {

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

    line-height: 1.5;

}


/* =========================================================
   CAMPOS
========================================================= */

.campo {

    margin-bottom: 18px;

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
   INFORMAÇÃO DA SENHA
========================================================= */

.info-senha {

    color: #555555;

    font-size: 9px;

    margin-top: -8px;

    margin-bottom: 20px;

}


/* =========================================================
   BOTÃO
========================================================= */

.botao-cadastro {

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


.botao-cadastro:hover {

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
   LOGIN
========================================================= */

.login {

    text-align: center;

}


.login p {

    color: #777777;

    font-size: 11px;

    margin-bottom: 12px;

}


.botao-login {

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


.botao-login:hover {

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


    .cadastro-card {

        padding: 24px 20px;

    }


    .titulo h1 {

        font-size: 30px;

    }

}

</style>

</head>


<body>


<!-- =========================================================
     TOPO
========================================================= -->

<div class="topo">

    CRIE SUA CONTA FREAKY

</div>


<!-- =========================================================
     HEADER
========================================================= -->

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


    <section class="cadastro-area">


        <div class="titulo">


            <div class="titulo-mini">

                NOVO CLIENTE

            </div>


            <h1>

                Criar sua
                <span>conta.</span>

            </h1>


            <p>

                Cadastre-se para comprar
                e acompanhar seus pedidos.

            </p>


        </div>


        <div class="cadastro-card">


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
                action="cadastro.php"
            >


                <div class="campo">


                    <label>

                        NOME

                    </label>


                    <input
                        type="text"
                        name="nome"
                        value="<?php

                        echo htmlspecialchars(
                            $nome
                        );

                        ?>"
                        placeholder="Digite seu nome"
                        autocomplete="name"
                        maxlength="100"
                        required
                    >


                </div>


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
                        maxlength="100"
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
                        placeholder="Crie uma senha"
                        autocomplete="new-password"
                        required
                    >


                </div>


                <div class="info-senha">

                    A senha deve possuir
                    pelo menos 6 caracteres.

                </div>


                <div class="campo">


                    <label>

                        CONFIRMAR SENHA

                    </label>


                    <input
                        type="password"
                        name="confirmar_senha"
                        placeholder="Digite a senha novamente"
                        autocomplete="new-password"
                        required
                    >


                </div>


                <button
                    type="submit"
                    class="botao-cadastro"
                >

                    CRIAR MINHA CONTA

                </button>


            </form>


            <div class="separador">

                <span>
                    ou
                </span>

            </div>


            <div class="login">


                <p>

                    Já possui uma conta?

                </p>


                <a
                    href="login.php"
                    class="botao-login"
                >

                    ENTRAR NA MINHA CONTA

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


</body>

</html>