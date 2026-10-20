<?php
require_once __DIR__ . "/parciais/bootstrap.php";
require_once __DIR__ . "/../XAMP/config_hardware.php";
require_once __DIR__ . "/../XAMP/csrf.php";

$base = "../";
$paginaAtual = "telaJogo.php";
$base = "../";
$paginaAtual = "telaJogo.php";

$idJogo = (int) ($_GET["id"] ?? 0);
if ($idJogo <= 0) {
    header("Location: biblioteca.php");
    exit;
}

$stmt = conectarBanco()->prepare("SELECT * FROM jogo WHERE id_jogo = :id");
$stmt->execute(["id" => $idJogo]);
$jogo = $stmt->fetch();

if (!$jogo) {
    header("Location: biblioteca.php?erro=jogo_nao_encontrado");
    exit;
}

// Já está na biblioteca do usuário logado?
$naBiblioteca = false;
if ($logado) {
    $stmtCheck = conectarBanco()->prepare(
        "SELECT 1 FROM biblioteca WHERE id_cliente = :id_cliente AND id_jogo = :id_jogo"
    );
    $stmtCheck->execute(["id_cliente" => $usuarioLogado["id"], "id_jogo" => $idJogo]);
    $naBiblioteca = (bool) $stmtCheck->fetchColumn();
}

function formatarTamanhoArquivo(?int $bytes): string
{
    if (!$bytes) {
        return "";
    }

    if ($bytes >= 1024 ** 3) {
        return round($bytes / 1024 ** 3, 2) . " GB";
    }

    return round($bytes / 1024 ** 2, 1) . " MB";
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($jogo["titulo"]) ?> - Ghost Gamer</title>
    <link rel="stylesheet" href="../ASSETS/CSS/global.css">
    <link rel="stylesheet" href="../ASSETS/CSS/telajogo.css">
</head>
<body>

    <?php include __DIR__ . "/parciais/nav.php"; ?>

    <main class="container-jogo">
        <section class="topo-jogo">
            <div class="imagem-jogo"
                <?php if ($jogo["foto"]): ?>
                    style="background-image:url('<?= htmlspecialchars($base . $jogo["foto"]) ?>')"
                <?php endif; ?>
            ></div>

           <div class="informacoes-jogo">
                <h1><?= htmlspecialchars($jogo["titulo"]) ?></h1>
                <p class="genero-jogo"><?= htmlspecialchars($jogo["genero"] ?: "Sem gênero") ?></p>
                <p class="empresa-jogo">Desenvolvedor: <?= htmlspecialchars($jogo["empresa_email"] ?: "Não informado") ?></p>

                <div id="msgBiblioteca" class="form-msg"></div>

                <div class="acoes-jogo">
                    <?php if (!$logado): ?>
                        <a href="loginCliente.php?redirect=telaJogo.php?id=<?= $idJogo ?>" class="btn btn-primary">Entrar para adicionar</a>
                    <?php elseif ($naBiblioteca): ?>
                        <button class="btn btn-outline" disabled><?= icone('check', 16) ?> Já está na sua biblioteca</button>
                    <?php else: ?>
                        <button class="btn btn-primary" id="btnAdicionarBiblioteca" data-id="<?= $idJogo ?>" data-csrf="<?= htmlspecialchars(tokenCsrf()) ?>">
                            <?= icone('adicionar', 16) ?> Adicionar à biblioteca
                        </button>
                    <?php endif; ?>

                    <?php if ($logado && $naBiblioteca): ?>
                        <?php if (!empty($jogo["arquivo_jogo"])): ?>
                            <a href="../XAMP/baixarJogo.php?id=<?= $idJogo ?>" class="btn btn-outline" id="linkBaixarJogo">
                                <?= icone('download', 16) ?> Baixar jogo
                                <?php $tam = formatarTamanhoArquivo($jogo["arquivo_tamanho_bytes"]); ?>
                                <?php if ($tam): ?><span class="tamanho-arquivo">(<?= $tam ?>)</span><?php endif; ?>
                            </a>
                        <?php else: ?>
                            <span class="sem-arquivo">O desenvolvedor ainda não enviou o arquivo deste jogo.</span>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>

                <?php if (!empty($jogo["arquivo_jogo"]) && (int) $jogo["downloads"] > 0): ?>
                    <p class="contagem-downloads"><?= (int) $jogo["downloads"] ?> download(s)</p>
                <?php endif; ?>
            </div>
        </section>

        <section class="meio-jogo">
            <div class="descricao-jogo">
                <h2>Descrição do jogo</h2>
                <p><?= htmlspecialchars($jogo["titulo"]) ?> é um jogo do gênero <?= htmlspecialchars($jogo["genero"] ?: "não especificado") ?>.</p>
                <p>Para saber se o seu computador roda este jogo, use o verificador de compatibilidade ao lado.</p>
            </div>

            <!-- VERIFICADOR DE COMPATIBILIDADE -->
            <div class="panel compat-box">
                <h3><?= icone('monitor', 18) ?> Verificar meu PC</h3>
                <p class="compat-intro">Informe as especificações do seu computador para ver se ele atende aos requisitos.</p>

                <?php if (HARDWARE_PAINEL_URL !== ""): ?>
                    <a href="<?= htmlspecialchars(HARDWARE_PAINEL_URL) ?>?app=<?= urlencode($jogo["titulo"]) ?>"
                       target="_blank" rel="noopener" class="compat-link-avancado">
                        <?= icone('escudo-check', 14) ?> Verificação avançada pelo robô oficial
                    </a>
                <?php endif; ?>

                <!-- Verificação automática via Ghost Gamer Hardware Agent -->
                <div class="agente-intro">
                    <h4>Verificação automática</h4>
                    <p>Para verificar automaticamente os componentes do seu computador, baixe e execute o Ghost Gamer Hardware Agent.</p>
                </div>

                <div class="agente-acoes">
                    <a href="../ASSETS/AGENT/GhostGamerAgent.exe" class="btn btn-outline btn-block" download>
                        <?= icone('download', 16) ?> Baixar Ghost Gamer Agent
                    </a>

                    <button type="button" id="btnVerificarAgente" class="btn btn-primary btn-block"
                            data-jogo-titulo="<?= htmlspecialchars($jogo["titulo"]) ?>">
                        <?= icone('play', 16) ?> Verificar meu PC automaticamente
                    </button>
                </div>

                <div id="statusAgente" class="status-agente" role="status" aria-live="polite"></div>
                <div id="resultadoAgente" class="resultado-agente"></div>

                <div class="compat-separador"><span>ou preencha manualmente</span></div>

                <form id="formCompat" novalidate>
                    <div class="field">
                        <label for="compatNucleos">Núcleos do processador</label>
                        <input type="number" id="compatNucleos" min="1" required>
                    </div>
                    <div class="field">
                        <label for="compatFrequencia">Frequência (GHz)</label>
                        <input type="number" id="compatFrequencia" min="0" step="0.1" required>
                    </div>
                    <div class="field">
                        <label for="compatRam">Memória RAM (GB)</label>
                        <input type="number" id="compatRam" min="1" required>
                    </div>
                    <div class="field">
                        <label for="compatVram">Memória de vídeo (GB)</label>
                        <input type="number" id="compatVram" min="0" required>
                    </div>
                    <div class="field">
                        <label for="compatArmazenamento">Espaço livre em disco (GB)</label>
                        <input type="number" id="compatArmazenamento" min="0" required>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block">Verificar compatibilidade</button>
                </form>

                <div id="resultadoCompat" class="resultado-compat"></div>

                <p class="compat-aviso"><?= icone('alerta', 16) ?> Atender aos requisitos mínimos não garante 100% de desempenho ou qualidade gráfica — apenas indica que o jogo tende a rodar.</p>
            </div>
        </section>

        <section class="requisitos-jogo">
            <h2>Requisitos mínimos</h2>
            <div class="requisitos-grid">
                <div class="requisito">
                    <strong>Processador</strong>
                    <span><?= (int) $jogo["nucleos"] ?> núcleos / <?= (int) $jogo["threads"] ?> threads</span>
                </div>
                <div class="requisito">
                    <strong>Frequência</strong>
                    <span><?= htmlspecialchars((string) $jogo["frequencia"]) ?> GHz</span>
                </div>
                <div class="requisito">
                    <strong>Memória RAM</strong>
                    <span><?= (int) $jogo["ram_gb"] ?> GB</span>
                </div>
                <div class="requisito">
                    <strong>Memória de vídeo</strong>
                    <span><?= (int) $jogo["vram_gb"] ?> GB</span>
                </div>
                <div class="requisito">
                    <strong>Armazenamento</strong>
                    <span><?= (int) $jogo["armazenamento"] ?> GB</span>
                </div>
            </div>
        </section>
    </main>

    <?php include __DIR__ . "/parciais/footer.php"; ?>
    <script>
        window.REQUISITOS_JOGO = {
            nucleos: <?= (int) $jogo["nucleos"] ?>,
            frequencia: <?= json_encode((float) $jogo["frequencia"]) ?>,
            ram_gb: <?= (int) $jogo["ram_gb"] ?>,
            vram_gb: <?= (int) $jogo["vram_gb"] ?>,
            armazenamento: <?= (int) $jogo["armazenamento"] ?>,
        };

        // Config do Ghost Gamer Hardware Agent + API de comparação
        // (ASSETS/JS/hardwareAgent.js), vindas de XAMP/config_hardware.php.
        window.HARDWARE_AGENT_URL = <?= json_encode(HARDWARE_AGENT_URL) ?>;
        window.HARDWARE_API_URL   = <?= json_encode(HARDWARE_API_URL) ?>;
    </script>
    <script src="../ASSETS/JS/telajogo.js"></script>
    <script src="../ASSETS/JS/hardwareAgent.js"></script>
</body>
</html>
