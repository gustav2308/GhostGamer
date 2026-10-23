<?php
session_start();
require_once __DIR__ . "/../PAGINAS/parciais/bootstrap.php";
require_once __DIR__ . "/csrf.php";
require_once __DIR__ . "/sincronizarRobo.php";

$base = "../";

if (!$logado || $tipo !== "admin") {
    header("Location: ../PAGINAS/loginCliente.php");
    exit;
}

$pdo = conectarBanco();
$id = (int) ($_GET["id"] ?? $_POST["id_jogo"] ?? 0);
$erro = null;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    exigirCsrfValido(comoJson: false, voltarPara: "?id=" . $id);

    $dados = [
        "titulo" => trim($_POST["titulo"] ?? ""),
        "empresa_email" => trim($_POST["empresa_email"] ?? ""),
        "genero" => trim($_POST["genero"] ?? ""),
        "nucleos" => (int) ($_POST["nucleos"] ?? 0),
        "threads" => (int) ($_POST["threads"] ?? 0),
        "frequencia" => (float) ($_POST["frequencia"] ?? 0),
        "ram_gb" => (int) ($_POST["ram_gb"] ?? 0),
        "vram_gb" => (int) ($_POST["vram_gb"] ?? 0),
        "armazenamento" => (int) ($_POST["armazenamento"] ?? 0),
        "id" => $id
    ];

    if ($dados["titulo"] === "") {
        $erro = "O título do jogo é obrigatório.";
    }

    $camposExtras = "";
    $erroUpload = $_FILES["arquivo_jogo"]["error"] ?? UPLOAD_ERR_NO_FILE;

    if (!$erro && $erroUpload === UPLOAD_ERR_OK) {
        $arquivo = $_FILES["arquivo_jogo"];
        $extensao = strtolower(pathinfo($arquivo["name"], PATHINFO_EXTENSION));

        if (!in_array($extensao, ["zip", "exe"], true)) {
            $erro = "O arquivo do jogo precisa ser .zip ou .exe.";
        } else {
            $pastaDownloads = dirname(__DIR__) . "/ASSETS/DOWNLOADS/";

            if (!is_dir($pastaDownloads)) {
                if (!mkdir($pastaDownloads, 0755, true)) {
                    $erro = "Não foi possível criar a pasta de downloads.";
                }
            }

            if (!$erro) {
                $nomeBase = preg_replace("/[^a-zA-Z0-9]/", "_", $dados["titulo"] ?: "jogo");
                $nomeArquivoFinal = $nomeBase . "_" . uniqid() . "." . $extensao;
                $caminhoNovo = $pastaDownloads . $nomeArquivoFinal;

                if (!move_uploaded_file($arquivo["tmp_name"], $caminhoNovo)) {
                    $erro = "Não foi possível salvar o novo arquivo do jogo.";
                } else {
                    $stmtAntigo = $pdo->prepare(
                        "SELECT arquivo_jogo FROM jogo WHERE id_jogo = :id"
                    );
                    $stmtAntigo->execute(["id" => $id]);
                    $antigo = $stmtAntigo->fetchColumn();

                    $dados["arquivo_jogo"] = "ASSETS/DOWNLOADS/" . $nomeArquivoFinal;
                    $dados["arquivo_nome_original"] = $arquivo["name"];
                    $dados["arquivo_tamanho_bytes"] = filesize($caminhoNovo);

                    $camposExtras = ",
                        arquivo_jogo = :arquivo_jogo,
                        arquivo_nome_original = :arquivo_nome_original,
                        arquivo_tamanho_bytes = :arquivo_tamanho_bytes";

                    try {
                        $stmt = $pdo->prepare(
                            "UPDATE jogo SET
                            titulo=:titulo,
                            empresa_email=:empresa_email,
                            genero=:genero,
                            nucleos=:nucleos,
                            threads=:threads,
                            frequencia=:frequencia,
                            ram_gb=:ram_gb,
                            vram_gb=:vram_gb,
                            armazenamento=:armazenamento
                            $camposExtras
                            WHERE id_jogo=:id"
                        );

                        $stmt->execute($dados);

                        if ($antigo) {
                            $caminhoAntigo = dirname(__DIR__) . "/" . $antigo;

                            if (is_file($caminhoAntigo) && $caminhoAntigo !== $caminhoNovo) {
                                @unlink($caminhoAntigo);
                            }
                        }

                        avisarRoboSincronizarJogo($id);

                        header("Location: consulta.php?aba=jogos&sucesso=1");
                        exit;

                    } catch (Exception $e) {
                        if (is_file($caminhoNovo)) {
                            @unlink($caminhoNovo);
                        }

                        $erro = "Erro ao alterar o jogo: " . $e->getMessage();
                    }
                }
            }
        }
    } elseif (
        !$erro &&
        ($erroUpload === UPLOAD_ERR_INI_SIZE || $erroUpload === UPLOAD_ERR_FORM_SIZE)
    ) {
        $erro = "O arquivo é maior do que o limite configurado no servidor. (upload_max_filesize = " . ini_get("upload_max_filesize") . ").";
    }

    if (!$erro && $erroUpload === UPLOAD_ERR_NO_FILE) {
        $stmt = $pdo->prepare(
            "UPDATE jogo SET
            titulo=:titulo,
            empresa_email=:empresa_email,
            genero=:genero,
            nucleos=:nucleos,
            threads=:threads,
            frequencia=:frequencia,
            ram_gb=:ram_gb,
            vram_gb=:vram_gb,
            armazenamento=:armazenamento
            WHERE id_jogo=:id"
        );

        $stmt->execute($dados);

        header("Location: consulta.php?aba=jogos&sucesso=1");
        exit;
    }
}

$stmt = $pdo->prepare("SELECT * FROM jogo WHERE id_jogo = :id");
$stmt->execute(["id" => $id]);
$jogo = $stmt->fetch();

if (!$jogo) {
    header("Location: consulta.php?aba=jogos");
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ghost Gamer - Editar jogo</title>
    <link rel="stylesheet" href="../ASSETS/CSS/global.css">
    <link rel="stylesheet" href="../ASSETS/CSS/admin.css">
</head>
<body>

<?php include __DIR__ . "/../PAGINAS/parciais/nav.php"; ?>

<main class="container admin-page">
    <h1>Editar jogo</h1>

    <?php if ($erro): ?>
        <div class="form-msg erro"><?= htmlspecialchars($erro) ?></div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data" class="panel admin-form admin-form-grid">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(tokenCsrf()) ?>">

        <input type="hidden" name="id_jogo" value="<?= (int) $jogo["id_jogo"] ?>">

        <div class="field">
            <label>Título</label>
            <input type="text" name="titulo" value="<?= htmlspecialchars($jogo["titulo"]) ?>" required>
        </div>

        <div class="field">
            <label>E-mail da empresa</label>
            <input type="email" name="empresa_email" value="<?= htmlspecialchars($jogo["empresa_email"] ?? "") ?>">
        </div>

        <div class="field">
            <label>Gênero</label>
            <input type="text" name="genero" value="<?= htmlspecialchars($jogo["genero"] ?? "") ?>">
        </div>

        <div class="field">
            <label>Núcleos (mínimo)</label>
            <input type="number" name="nucleos" value="<?= (int) $jogo["nucleos"] ?>" min="0">
        </div>

        <div class="field">
            <label>Threads (mínimo)</label>
            <input type="number" name="threads" value="<?= (int) $jogo["threads"] ?>" min="0">
        </div>

        <div class="field">
            <label>Frequência (GHz)</label>
            <input type="number" step="0.1" name="frequencia" value="<?= htmlspecialchars((string) $jogo["frequencia"]) ?>" min="0">
        </div>

        <div class="field">
            <label>RAM (GB)</label>
            <input type="number" name="ram_gb" value="<?= (int) $jogo["ram_gb"] ?>" min="0">
        </div>

        <div class="field">
            <label>VRAM (GB)</label>
            <input type="number" name="vram_gb" value="<?= (int) $jogo["vram_gb"] ?>" min="0">
        </div>

        <div class="field">
            <label>Armazenamento (GB)</label>
            <input type="number" name="armazenamento" value="<?= (int) $jogo["armazenamento"] ?>" min="0">
        </div>

        <div class="field admin-form-grid-full">
            <label>Arquivo do jogo (.zip ou .exe)</label>

            <?php if (!empty($jogo["arquivo_nome_original"])): ?>
                <p class="field-hint">
                    Atual: <?= htmlspecialchars($jogo["arquivo_nome_original"]) ?>
                </p>
            <?php else: ?>
                <p class="field-hint">Nenhum arquivo enviado ainda.</p>
            <?php endif; ?>

            <input type="file" name="arquivo_jogo" accept=".zip,.exe">

            <p class="field-hint">
                Deixe em branco para manter o arquivo atual.
            </p>
        </div>

        <div class="field admin-form-grid-full">
            <label>Downloads</label>

            <input
                type="text"
                value="<?= (int) ($jogo["downloads"] ?? 0) ?>"
                disabled
            >

            <p class="field-hint">
                A quantidade de downloads não é alterada ao editar o jogo.
            </p>
        </div>

        <div class="admin-form-acoes admin-form-grid-full">
            <a href="consulta.php?aba=jogos" class="btn btn-outline">
                Cancelar
            </a>

            <button type="submit" class="btn btn-primary">
                Salvar alterações
            </button>
        </div>
    </form>
</main>

<?php include __DIR__ . "/../PAGINAS/parciais/footer.php"; 
header("Location: ../PAGINAS/...");
exit;
?>

</body>
</html>