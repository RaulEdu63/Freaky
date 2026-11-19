<?php
session_start();
include "conexao.php";

$id_produto = isset($_GET["id"]) ? intval($_GET["id"]) : 0;

if ($id_produto <= 0) {
    header("Location: index.php#catalogo");
    exit;
}

$sql = "SELECT * FROM produtos WHERE id_produto = ? LIMIT 1";
$stmt = $conexao->prepare($sql);
$stmt->bind_param("i", $id_produto);
$stmt->execute();
$resultado = $stmt->get_result();

if (!$resultado || $resultado->num_rows === 0) {
    header("Location: index.php#catalogo");
    exit;
}

$produto = $resultado->fetch_assoc();
$nome = htmlspecialchars($produto["nome"]);
$descricao = htmlspecialchars($produto["descricao"] ?? "");
$categoria = htmlspecialchars($produto["categoria"] ?? "");
$imagem = htmlspecialchars($produto["imagem"] ?? "");
$preco = number_format((float)$produto["preco"], 2, ",", ".");
$estoque = intval($produto["estoque"]);

// Tabelas ilustrativas escolhidas pelo nome/categoria do produto.
// Os números são fictícios e servem apenas para demonstrar o visual.
$tipo_produto = strtolower(($produto["nome"] ?? "") . " " . ($produto["categoria"] ?? ""));

if (strpos($tipo_produto, "creatina") !== false) {
    $porcao = "3 g";
    $nutrientes = [
        ["Valor energético", "0 kcal"],
        ["Carboidratos", "0 g"],
        ["Proteínas", "0 g"],
        ["Creatina", "3 g"],
        ["Gorduras totais", "0 g"],
        ["Sódio", "0 mg"]
    ];
} elseif (strpos($tipo_produto, "pré-treino") !== false || strpos($tipo_produto, "pre-treino") !== false || strpos($tipo_produto, "pre treino") !== false) {
    $porcao = "10 g";
    $nutrientes = [
        ["Valor energético", "15 kcal"],
        ["Carboidratos", "3 g"],
        ["Açúcares totais", "0 g"],
        ["Proteínas", "0 g"],
        ["Cafeína", "200 mg"],
        ["Sódio", "100 mg"]
    ];
} elseif (strpos($tipo_produto, "hipercal") !== false || strpos($tipo_produto, "mass gainer") !== false || strpos($tipo_produto, "mass gain") !== false || strpos($tipo_produto, "ganho de massa") !== false) {
    $porcao = "160 g";
    $nutrientes = [
        ["Valor energético", "620 kcal"],
        ["Carboidratos", "110 g"],
        ["Açúcares totais", "18 g"],
        ["Proteínas", "25 g"],
        ["Gorduras totais", "8 g"],
        ["Sódio", "230 mg"]
    ];
} elseif (strpos($tipo_produto, "whey") !== false || strpos($tipo_produto, "proteína") !== false || strpos($tipo_produto, "proteina") !== false) {
    $porcao = "30 g";
    $nutrientes = [
        ["Valor energético", "120 kcal"],
        ["Carboidratos", "4 g"],
        ["Açúcares totais", "2 g"],
        ["Proteínas", "22 g"],
        ["Gorduras totais", "2 g"],
        ["Sódio", "85 mg"]
    ];
} else {
    $porcao = "30 g";
    $nutrientes = [
        ["Valor energético", "115 kcal"],
        ["Carboidratos", "5 g"],
        ["Açúcares totais", "1 g"],
        ["Proteínas", "20 g"],
        ["Gorduras totais", "1,5 g"],
        ["Sódio", "90 mg"]
    ];
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $nome; ?> - Freaky Suplements</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        body {
            font-family: Arial, Helvetica, sans-serif;
            background: #080808;
            color: #fff;
            min-height: 100vh;
        }
        a { text-decoration: none; color: inherit; }
        .barra-topo {
            background: #ff2525;
            color: #fff;
            text-align: center;
            padding: 11px;
            font-size: 15px;
            font-weight: bold;
        }
        header {
            background: #0d0d0d;
            border-bottom: 1px solid #252525;
        }
        .header-principal {
            width: 92%;
            max-width: 1400px;
            margin: auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 20px 0;
            gap: 25px;
        }
        .logo img { width: 190px; max-height: 80px; object-fit: contain; }
        .acoes { display: flex; align-items: center; gap: 22px; flex-wrap: wrap; justify-content: flex-end; }
        .acoes a { font-size: 15px; color: #fff; }
        .acoes a:hover { color: #ff2525; }
        .usuario { color: #ff2525; font-weight: bold; }
        .sair { color: #aaa !important; }
        nav { background: #0d0d0d; border-top: 1px solid #181818; }
        .menu {
            width: 92%; max-width: 1400px; margin: auto;
            display: flex; justify-content: center; align-items: center;
            gap: 45px; padding: 16px 0;
        }
        .menu a { color: #ddd; font-size: 16px; }
        .menu a:hover { color: #ff2525; }
        .pagina {
            width: 92%; max-width: 1200px; margin: 55px auto 90px;
        }
        .voltar {
            display: inline-block; color: #bbb; font-size: 16px;
            margin-bottom: 30px;
        }
        .voltar:hover { color: #ff2525; }
        .produto-detalhes {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 55px;
            align-items: center;
        }
        .imagem-produto {
            min-height: 520px;
            background: #111;
            border: 1px solid #292929;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px;
        }
        .imagem-produto img {
            width: 100%; height: 100%; max-height: 440px;
            object-fit: contain;
        }
        .sem-imagem { color: #777; font-size: 20px; }
        .categoria {
            color: #ff2525; font-size: 16px; font-weight: bold;
            text-transform: uppercase; letter-spacing: .5px; margin-bottom: 12px;
        }
        .informacoes h1 { font-size: 42px; line-height: 1.15; margin-bottom: 18px; }
        .descricao {
            color: #ccc; font-size: 18px; line-height: 1.7; margin: 20px 0;
        }
        .preco { font-size: 36px; font-weight: bold; margin: 25px 0; }
        .estoque { color: #bbb; font-size: 16px; margin-bottom: 25px; }
        .estoque strong { color: #fff; }
        .form-carrinho { display: flex; gap: 12px; align-items: center; }
        .quantidade {
            width: 90px; padding: 15px; background: #171717;
            border: 1px solid #444; border-radius: 8px; color: #fff;
            font-size: 17px; text-align: center;
        }
        .botao {
            display: inline-block; padding: 16px 25px; background: #ff2525;
            color: #fff; border: 0; border-radius: 8px; font-weight: bold;
            font-size: 16px; cursor: pointer;
        }
        .botao:hover { background: #d90000; }
        .botao-desativado { background: #444; cursor: not-allowed; }
        .secao-nutricional {
            max-width: 760px;
            margin: 65px auto 0;
            padding: 30px;
            background: #111;
            border: 1px solid #292929;
            border-radius: 14px;
        }
        .secao-nutricional h2 {
            font-size: 28px;
            margin-bottom: 8px;
        }
        .aviso-nutricional {
            color: #aaa;
            font-size: 13px;
            line-height: 1.5;
            margin-bottom: 22px;
        }
        .tabela-nutricional {
            width: 100%;
            border-collapse: collapse;
            background: #0b0b0b;
            color: #f5f5f5;
            font-size: 15px;
            border: 1px solid #303030;
        }
        .tabela-nutricional th, .tabela-nutricional td {
            padding: 12px 14px;
            border-bottom: 1px solid #303030;
            text-align: left;
        }
        .tabela-nutricional th {
            background: #191919;
            color: #ff3535;
            font-weight: bold;
        }
        .tabela-nutricional td:last-child, .tabela-nutricional th:last-child {
            text-align: right;
        }
        .tabela-nutricional tr:last-child td { border-bottom: 0; }
        @media (max-width: 500px) {
            .secao-nutricional { padding: 18px 12px; margin-top: 40px; }
            .secao-nutricional h2 { font-size: 23px; }
            .tabela-nutricional { font-size: 13px; }
            .tabela-nutricional th, .tabela-nutricional td { padding: 10px 8px; }
        }
        footer {
            background: #050505; border-top: 1px solid #222;
            text-align: center; padding: 35px 20px; color: #777; font-size: 14px;
        }
        @media (max-width: 800px) {
            .header-principal { flex-direction: column; }
            .acoes { justify-content: center; }
            .produto-detalhes { grid-template-columns: 1fr; gap: 30px; }
            .imagem-produto { min-height: 380px; }
            .informacoes h1 { font-size: 34px; }
            .menu { gap: 18px; flex-wrap: wrap; }
        }
        @media (max-width: 500px) {
            .pagina { width: 94%; }
            .imagem-produto { min-height: 300px; padding: 25px; }
            .form-carrinho { flex-direction: column; align-items: stretch; }
            .quantidade { width: 100%; }
            .botao { text-align: center; width: 100%; }
        }
    </style>
</head>
<body>
    <div class="barra-topo">Frete grátis para compras acima de R$100</div>

    <header>
        <div class="header-principal">
            <a href="index.php" class="logo">
                <img src="img/logo.png" alt="Freaky Suplements">
            </a>

            <div class="acoes">
                <a href="rastrear.php">🚚 Rastrear Pedido</a>
                <a href="carrinho.php">🛒 Carrinho</a>

                <?php if (isset($_SESSION["id_cliente"])): ?>
                    <span class="usuario">
                        Olá, <?php echo htmlspecialchars($_SESSION["nome_usuario"] ?? "Cliente"); ?>!
                    </span>
                    <a href="logout.php" class="sair">Sair</a>
                <?php else: ?>
                    <a href="login.php">Entrar</a>
                    <a href="cadastro.php">Cadastrar</a>
                <?php endif; ?>
            </div>
        </div>

        <nav>
            <div class="menu">
                <a href="index.php">Início</a>
                <a href="index.php#catalogo">Produtos</a>
                <a href="index.php#sobre">Sobre nós</a>
                <a href="carrinho.php">Suas compras</a>
                <a href="index.php#suporte">Suporte</a>
            </div>
        </nav>
    </header>

    <main class="pagina">
        <a href="index.php#catalogo" class="voltar">← Voltar para os produtos</a>

        <div class="produto-detalhes">
            <div class="imagem-produto">
                <?php if ($imagem !== ""): ?>
                    <img src="img/<?php echo $imagem; ?>" alt="<?php echo $nome; ?>">
                <?php else: ?>
                    <span class="sem-imagem">Sem imagem disponível</span>
                <?php endif; ?>
            </div>

            <div class="informacoes">
                <div class="categoria"><?php echo $categoria; ?></div>
                <h1><?php echo $nome; ?></h1>
                <p class="descricao"><?php echo $descricao; ?></p>
                <div class="preco">R$ <?php echo $preco; ?></div>

                <div class="estoque">
                    Disponibilidade: <strong><?php echo $estoque > 0 ? $estoque . " unidades em estoque" : "Produto esgotado"; ?></strong>
                </div>

                <?php if ($estoque > 0): ?>
                    <form action="carrinho.php" method="GET" class="form-carrinho">
                        <input type="hidden" name="adicionar" value="<?php echo $id_produto; ?>">
                        <input class="quantidade" type="number" name="quantidade" value="1" min="1" max="<?php echo $estoque; ?>">
                        <button type="submit" class="botao">Adicionar ao carrinho</button>
                    </form>
                <?php else: ?>
                    <button class="botao botao-desativado" disabled>Produto esgotado</button>
                <?php endif; ?>
            </div>
        </div>

        <section class="secao-nutricional" aria-labelledby="titulo-nutricional">
            <h2 id="titulo-nutricional">Tabela nutricional</h2>
            <p class="aviso-nutricional">Tabela demonstrativa para representar o layout da loja. Os valores abaixo são fictícios e não devem ser usados como informação nutricional real do produto.</p>
            <table class="tabela-nutricional">
                <thead>
                    <tr>
                        <th colspan="2">Informação nutricional</th>
                    </tr>
                    <tr>
                        <th>Componente</th>
                        <th>Quantidade por porção*</th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td>Porção</td><td><?php echo htmlspecialchars($porcao); ?></td></tr>
                    <?php foreach ($nutrientes as $nutriente): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($nutriente[0]); ?></td>
                            <td><?php echo htmlspecialchars($nutriente[1]); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <p class="aviso-nutricional" style="margin: 12px 0 0;">*Valores meramente ilustrativos. Consulte o rótulo oficial do fabricante para informações reais.</p>
        </section>
    </main>

    <footer>
        <p>© 2026 Freaky Suplements. Todos os direitos reservados.</p>
    </footer>
</body>
</html>
