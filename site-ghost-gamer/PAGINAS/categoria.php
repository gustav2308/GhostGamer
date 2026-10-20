<?php
require_once __DIR__ . "/parciais/bootstrap.php";
$base = "../";
$paginaAtual = "categoria.php";

$pdo = conectarBanco();

// Lista de gêneros distintos (usa o índice idx_jogo_genero para ser rápido)
$generos = $pdo->query("SELECT DISTINCT genero FROM jogo WHERE genero IS NOT NULL AND genero <> '' ORDER BY genero")
                ->fetchAll(PDO::FETCH_COLUMN);

$generoSelecionado = trim($_GET["genero"] ?? "");
$jogosDoGenero = [];

if ($generoSelecionado !== "") {
    $stmt = $pdo->prepare("SELECT * FROM jogo WHERE genero = :genero ORDER BY titulo");
    $stmt->execute(["genero" => $generoSelecionado]);
    $jogosDoGenero = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ghost Gamer - Categorias</title>
    <link rel="stylesheet" href="../ASSETS/CSS/global.css">
    <link rel="stylesheet" href="../ASSETS/CSS/categoria.css">
</head>
<body>

    <?php include __DIR__ . "/parciais/nav.php"; ?>

    <main class="container categoria-page">

        <section class="titulo-biblioteca">
            <h1>Categorias</h1>
            <p>Escolha um gênero para ver os jogos disponíveis.</p>
        </section>

        <section class="grid-categorias">
            <?php if (empty($generos)): ?>
                <p>Nenhuma categoria cadastrada ainda.</p>
            <?php endif; ?>
            <?php foreach ($generos as $genero): ?>
                <a href="categoria.php?genero=<?= urlencode($genero) ?>"
                   class="card-categoria <?= $genero === $generoSelecionado ? 'ativo' : '' ?>">
                    <?= icone(iconeGenero($genero), 26) ?>
                    <span class="card-categoria-nome"><?= htmlspecialchars($genero) ?></span>
                </a>
            <?php endforeach; ?>
        </section>

        <?php if ($generoSelecionado !== ""): ?>
            <section class="resultado-categoria">
                <h2>Jogos de <?= htmlspecialchars($generoSelecionado) ?></h2>

                <?php if (empty($jogosDoGenero)): ?>
                    <p>Nenhum jogo encontrado nesta categoria.</p>
                <?php else: ?>
                    <div class="grid-jogos">
                        <?php foreach ($jogosDoGenero as $jogo): ?>
                            <a href="telaJogo.php?id=<?= (int) $jogo["id_jogo"] ?>" class="card" data-titulo="<?= htmlspecialchars($jogo["titulo"]) ?>">
                                <div class="imagem-card"
                                    <?php if ($jogo["foto"]): ?>
                                        style="background-image:url('<?= htmlspecialchars($base . $jogo["foto"]) ?>')"
                                    <?php endif; ?>
                                >
                                    <?php if ($jogo["genero"]): ?><span class="badge"><?= htmlspecialchars($jogo["genero"]) ?></span><?php endif; ?>
                                </div>
                                <div class="informacoes-card">
                                    <h3><?= htmlspecialchars($jogo["titulo"]) ?></h3>
                                    <p><?= htmlspecialchars($jogo["genero"]) ?></p>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        <?php endif; ?>

    </main>

    <?php include __DIR__ . "/parciais/footer.php"; ?>
</body>
</html>
