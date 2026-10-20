<?php
require_once __DIR__ . "/PAGINAS/parciais/bootstrap.php";
$base = "";
$paginaAtual = "index.php";

$pdo = conectarBanco();

// Jogo em destaque: o mais recente que tem foto (se nenhum tiver, o mais recente cadastrado)
$destaque = $pdo->query(
    "SELECT * FROM jogo WHERE foto IS NOT NULL AND foto <> '' ORDER BY id_jogo DESC LIMIT 1"
)->fetch();
if (!$destaque) {
    $destaque = $pdo->query("SELECT * FROM jogo ORDER BY id_jogo DESC LIMIT 1")->fetch();
}

// Gêneros existentes no catálogo (para os blocos de categoria)
$generos = $pdo->query(
    "SELECT DISTINCT genero FROM jogo WHERE genero IS NOT NULL AND genero <> '' ORDER BY genero"
)->fetchAll(PDO::FETCH_COLUMN);

// Todos os jogos, do mais novo para o mais antigo
$jogos = $pdo->query("SELECT * FROM jogo ORDER BY id_jogo DESC")->fetchAll();
$totalJogos = count($jogos);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ghost Gamer - Home</title>
    <link rel="stylesheet" href="ASSETS/CSS/global.css">
    <link rel="stylesheet" href="ASSETS/CSS/index.css">
</head>
<body>

    <?php include __DIR__ . "/PAGINAS/parciais/nav.php"; ?>

    <main>

        <?php if ($destaque): ?>
            <!-- HERO: jogo em destaque -->
            <section class="hero">
                <div class="hero-fundo" aria-hidden="true">
                    <?php if ($destaque["foto"]): ?>
                        <img src="<?= htmlspecialchars($destaque["foto"]) ?>" alt="">
                    <?php endif; ?>
                </div>

                <div class="container hero-conteudo">
                    <div class="hero-texto">
                        <span class="badge">Em destaque</span>
                        <h1><?= htmlspecialchars($destaque["titulo"]) ?></h1>

                        <div class="hero-meta">
                            <?php if ($destaque["genero"]): ?><span class="chip"><?= htmlspecialchars($destaque["genero"]) ?></span><?php endif; ?>
                            <?php if (!empty($destaque["ram_gb"])): ?><span class="chip"><?= (int) $destaque["ram_gb"] ?> GB RAM</span><?php endif; ?>
                            <?php if (!empty($destaque["vram_gb"])): ?><span class="chip"><?= (int) $destaque["vram_gb"] ?> GB VRAM</span><?php endif; ?>
                        </div>

                        <p class="hero-desc">
                            <?= htmlspecialchars($destaque["titulo"]) ?> é um dos títulos disponíveis no Ghost Gamer<?= $destaque["genero"] ? " na categoria " . htmlspecialchars($destaque["genero"]) : "" ?>.
                            Confira os requisitos e veja se ele combina com o seu computador.
                        </p>

                        <div class="hero-acoes">
                            <a class="btn btn-primary" href="PAGINAS/telaJogo.php?id=<?= (int) $destaque["id_jogo"] ?>"><?= icone('play', 16) ?> Ver jogo</a>
                            <a class="btn btn-outline" href="PAGINAS/categoria.php">Explorar categorias</a>
                        </div>
                    </div>

                    <a class="hero-capa" href="PAGINAS/telaJogo.php?id=<?= (int) $destaque["id_jogo"] ?>" aria-label="Abrir <?= htmlspecialchars($destaque["titulo"]) ?>">
                        <?php if ($destaque["foto"]): ?>
                            <img src="<?= htmlspecialchars($destaque["foto"]) ?>" alt="Capa de <?= htmlspecialchars($destaque["titulo"]) ?>">
                        <?php endif; ?>
                    </a>
                </div>
            </section>
        <?php else: ?>
            <section class="hero hero-simples">
                <div class="container hero-conteudo">
                    <div class="hero-texto">
                        <h2>Bem-vindo ao</h2>
                        <h1>GHOST GAMER</h1>
                        <p class="hero-desc">Seus jogos favoritos em um só lugar.</p>
                    </div>
                </div>
            </section>
        <?php endif; ?>

        <!-- CATEGORIAS -->
        <?php if (!empty($generos)): ?>
        <section class="container secao" id="categorias">
            <div class="titulo-secao">
                <h2>Categorias</h2>
                <p>Escolha seu gênero favorito</p>
            </div>

            <div class="grid-categorias">
                <?php foreach ($generos as $genero): ?>
                    <a class="card-categoria" href="PAGINAS/categoria.php?genero=<?= urlencode($genero) ?>">
                        <?= icone(iconeGenero($genero), 26) ?>
                        <span class="card-categoria-nome"><?= htmlspecialchars($genero) ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>

        <!-- JOGOS -->
        <section class="container secao" id="jogos">
            <div class="titulo-secao">
                <h2>Jogos disponíveis</h2>
                <p>Seus jogos favoritos em um só lugar</p>
            </div>

            <p id="buscaVazia" class="busca-vazia">Nenhum jogo encontrado para essa busca.</p>

            <?php if ($totalJogos > 0): ?>
                <div class="grid-jogos">
                    <?php foreach ($jogos as $jogo): ?>
                        <a class="card" href="PAGINAS/telaJogo.php?id=<?= (int) $jogo["id_jogo"] ?>" data-titulo="<?= htmlspecialchars($jogo["titulo"]) ?>">
                            <div class="imagem-card"
                                <?php if ($jogo["foto"]): ?>
                                    style="background-image:url('<?= htmlspecialchars($jogo["foto"]) ?>')"
                                <?php endif; ?>
                            >
                                <?php if ($jogo["genero"]): ?><span class="badge"><?= htmlspecialchars($jogo["genero"]) ?></span><?php endif; ?>
                            </div>
                            <div class="informacoes-card">
                                <h3><?= htmlspecialchars($jogo["titulo"]) ?></h3>
                                <p><?= htmlspecialchars($jogo["genero"] ?: "Jogo") ?></p>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="estado-vazio">
                    <?= icone('controle', 34) ?>
                    <p>Nenhum jogo cadastrado ainda.</p>
                </div>
            <?php endif; ?>
        </section>

    </main>

    <?php $base = ""; include __DIR__ . "/PAGINAS/parciais/footer.php"; ?>

</body>
</html>
