<?php
require_once __DIR__ . "/PAGINAS/parciais/bootstrap.php";
$base = "";
$paginaAtual = "index.php";

$pdo = conectarBanco();

// Jogo em destaque: o mais recente que tem foto
// (se nenhum tiver, o mais recente cadastrado)
$destaque = $pdo->query(
    "SELECT * FROM jogo WHERE foto IS NOT NULL AND foto <> '' ORDER BY id_jogo DESC LIMIT 1"
)->fetch();

if (!$destaque) {
    $destaque = $pdo->query(
        "SELECT * FROM jogo ORDER BY id_jogo DESC LIMIT 1"
    )->fetch();
}

// Gêneros existentes no catálogo
$generos = $pdo->query(
    "SELECT DISTINCT genero FROM jogo WHERE genero IS NOT NULL AND genero <> '' ORDER BY genero"
)->fetchAll(PDO::FETCH_COLUMN);

// Todos os jogos, do mais novo para o mais antigo
$jogos = $pdo->query(
    "SELECT * FROM jogo ORDER BY id_jogo DESC"
)->fetchAll();

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

    <!-- Configuração do aplicativo PWA -->
    <link rel="manifest" href="/manifest.webmanifest">
    <meta name="theme-color" content="#08070d">

    <style>
        #ghostAppDownloadArea {
            padding-top: 20px;
            padding-bottom: 8px;
        }

        #ghostAppDownloadArea[hidden] {
            display: none !important;
        }

        .gg-download-panel {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            padding: 18px 22px;
            border: 1px solid var(--gg-borda-forte);
            border-radius: 16px;
            background: linear-gradient(115deg, #20132e, #120f1c 75%);
        }

        .gg-download-info {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .gg-download-icon {
            display: grid;
            place-items: center;
            width: 48px;
            height: 48px;
            flex-shrink: 0;
            border-radius: 12px;
            background: #39204b;
            color: #e0b0f2;
        }

        .gg-download-info strong {
            display: block;
            font-size: 18px;
            color: var(--gg-texto);
        }

        .gg-download-info small {
            display: block;
            color: var(--gg-texto-2);
            font-size: 14px;
        }

        #ghostAppDownloadHome {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            min-height: 48px;
            padding: 12px 22px;
            border: 1px solid #c17ad8;
            border-radius: 10px;
            background: linear-gradient(135deg, #79349c, #a044b8);
            color: #fff;
            font-weight: 700;
            font-size: 15px;
            box-shadow: 0 5px 20px #86269e40;
            transition: transform .2s, box-shadow .2s;
            text-align: center;
        }

        #ghostAppDownloadHome:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px #ab58bc65;
        }

        #ghostAppDownloadHome:focus-visible {
            outline: 3px solid #e0b0f2;
            outline-offset: 4px;
        }

        @media (max-width: 600px) {
            .gg-download-panel {
                flex-direction: column;
                align-items: stretch;
                padding: 16px;
                gap: 14px;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            #ghostAppDownloadHome {
                transition: none;
            }

            #ghostAppDownloadHome:hover {
                transform: none;
            }
        }
    </style>
</head>

<body>

    <?php include __DIR__ . "/PAGINAS/parciais/nav.php"; ?>

    <main>

        <!-- Área de download do aplicativo Windows -->
        <div class="container" id="ghostAppDownloadArea">

            <div class="gg-download-panel">

                <div class="gg-download-info">

                    <span class="gg-download-icon" aria-hidden="true">
                        <svg width="24" height="24" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor"
                            stroke-width="1.7" stroke-linecap="round"
                            stroke-linejoin="round">
                            <rect x="3" y="3" width="18" height="13" rx="2"/>
                            <path d="M8 21h8m-4-5v5"/>
                        </svg>
                    </span>

                    <div>
                        <strong>Ghost Gamer para Windows</strong>
                        <small>Seu catálogo de jogos em um aplicativo.</small>
                    </div>

                </div>

                <a id="ghostAppDownloadHome"
                    href="/ASSETS/DOWNLOADS/GhostGamer Launcher Setup 1.0.0.exe"
                    download="GhostGamer Launcher Setup 1.0.0.exe">

                    <svg aria-hidden="true" width="20" height="20"
                        viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 3v12m-5-5 5 5 5-5M4 16v4a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1v-4"/>
                    </svg>

                    Baixar aplicativo
                </a>

            </div>
        </div>

        <script>
            (() => {
                const area = document.getElementById('ghostAppDownloadArea');
                const link = document.getElementById('ghostAppDownloadHome');
                const chave = 'ghostGamerWindowsExeBaixado';

                const modoApp =
                    window.matchMedia('(display-mode: standalone)').matches ||
                    window.matchMedia('(display-mode: minimal-ui)').matches ||
                    new URLSearchParams(location.search).get('app') === '1';

                if (!area || !link) return;

                try {
                    if (modoApp || localStorage.getItem(chave) === 'sim') {
                        area.hidden = true;
                    }
                } catch (e) {
                    if (modoApp) area.hidden = true;
                }

                link.addEventListener('click', () => {
                    try {
                        localStorage.setItem(chave, 'sim');
                    } catch (e) {}

                    area.hidden = true;
                });
            })();
        </script>

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

                            <?php if ($destaque["genero"]): ?>
                                <span class="chip">
                                    <?= htmlspecialchars($destaque["genero"]) ?>
                                </span>
                            <?php endif; ?>

                            <?php if (!empty($destaque["ram_gb"])): ?>
                                <span class="chip">
                                    <?= (int) $destaque["ram_gb"] ?> GB RAM
                                </span>
                            <?php endif; ?>

                            <?php if (!empty($destaque["vram_gb"])): ?>
                                <span class="chip">
                                    <?= (int) $destaque["vram_gb"] ?> GB VRAM
                                </span>
                            <?php endif; ?>

                        </div>

                        <p class="hero-desc">
                            <?= htmlspecialchars($destaque["titulo"]) ?>
                            é um dos títulos disponíveis no Ghost Gamer
                            <?= $destaque["genero"]
                                ? " na categoria " . htmlspecialchars($destaque["genero"])
                                : "" ?>.
                            Confira os requisitos e veja se ele combina com o seu computador.
                        </p>

                        <div class="hero-acoes">

                            <a class="btn btn-primary"
                                href="PAGINAS/telaJogo.php?id=<?= (int) $destaque["id_jogo"] ?>">
                                <?= icone('play', 16) ?> Ver jogo
                            </a>

                            <a class="btn btn-outline"
                                href="PAGINAS/categoria.php">
                                Explorar categorias
                            </a>

                        </div>
                    </div>

                    <a class="hero-capa"
                        href="PAGINAS/telaJogo.php?id=<?= (int) $destaque["id_jogo"] ?>"
                        aria-label="Abrir <?= htmlspecialchars($destaque["titulo"]) ?>">

                        <?php if ($destaque["foto"]): ?>
                            <img src="<?= htmlspecialchars($destaque["foto"]) ?>"
                                alt="Capa de <?= htmlspecialchars($destaque["titulo"]) ?>">
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

                        <a class="card-categoria"
                            href="PAGINAS/categoria.php?genero=<?= urlencode($genero) ?>">

                            <?= icone(iconeGenero($genero), 26) ?>

                            <span class="card-categoria-nome">
                                <?= htmlspecialchars($genero) ?>
                            </span>

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

            <p id="buscaVazia" class="busca-vazia">
                Nenhum jogo encontrado para essa busca.
            </p>

            <?php if ($totalJogos > 0): ?>

                <div class="grid-jogos">

                    <?php foreach ($jogos as $jogo): ?>

                        <a class="card"
                            href="PAGINAS/telaJogo.php?id=<?= (int) $jogo["id_jogo"] ?>"
                            data-titulo="<?= htmlspecialchars($jogo["titulo"]) ?>">

                            <div class="imagem-card"
                                <?php if ($jogo["foto"]): ?>
                                    style="background-image:url('<?= htmlspecialchars($jogo["foto"]) ?>')"
                                <?php endif; ?>>

                                <?php if ($jogo["genero"]): ?>
                                    <span class="badge">
                                        <?= htmlspecialchars($jogo["genero"]) ?>
                                    </span>
                                <?php endif; ?>

                            </div>

                            <div class="informacoes-card">

                                <h3><?= htmlspecialchars($jogo["titulo"]) ?></h3>

                                <p>
                                    <?= htmlspecialchars($jogo["genero"] ?: "Jogo") ?>
                                </p>

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

    <?php
    $base = "";
    include __DIR__ . "/PAGINAS/parciais/footer.php";
    ?>

</body>
</html>
