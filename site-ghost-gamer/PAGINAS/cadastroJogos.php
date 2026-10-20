<?php
require_once __DIR__ . "/parciais/bootstrap.php";
$base = "../";
$paginaAtual = "cadastroJogos.php";

if (!$logado || $tipo !== "admin") {
    header("Location: loginCliente.php");
    exit;
}

$empresas = conectarBanco()->query("SELECT DISTINCT email FROM empresa ORDER BY email")->fetchAll(PDO::FETCH_COLUMN);

$generos = ["Ação", "Aventura", "RPG", "Estratégia", "Simulação", "Esportes", "Corrida", "Luta",
            "Terror", "Sobrevivência", "Plataforma", "Puzzle", "Tiro", "Battle Royale",
            "MMORPG", "MOBA", "Musical", "Indie"];
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ghost Gamer - Cadastro de jogo</title>
    <link rel="stylesheet" href="../ASSETS/CSS/global.css">
    <link rel="stylesheet" href="../ASSETS/CSS/admin.css">
</head>
<body>

    <?php include __DIR__ . "/parciais/nav.php"; ?>

    <main class="container admin-page">
        <h1>Cadastro de jogo</h1>

        <form method="post" action="../XAMP/gravarcad.php" enctype="multipart/form-data" class="panel admin-form admin-form-grid">

            <div class="field"><label>Título do jogo</label><input type="text" name="titulo" required></div>

            <div class="field">
                <label>E-mail da empresa</label>
                <select name="empresa_email" required>
                    <option value="" disabled selected>Selecione a empresa</option>
                    <?php foreach ($empresas as $email): ?>
                        <option value="<?= htmlspecialchars($email) ?>"><?= htmlspecialchars($email) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="field">
                <label>Gênero</label>
                <select name="genero" required>
                    <option value="" disabled selected>Selecione o gênero</option>
                    <?php foreach ($generos as $genero): ?>
                        <option value="<?= htmlspecialchars($genero) ?>"><?= htmlspecialchars($genero) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="field"><label>Núcleos (mínimo)</label><input type="number" name="nucleos" min="1" required></div>
            <div class="field"><label>Threads (mínimo)</label><input type="number" name="threads" min="1" required></div>
            <div class="field"><label>Frequência (GHz)</label><input type="number" step="0.1" name="frequencia" min="0" required></div>
            <div class="field"><label>Memória RAM (GB)</label><input type="number" name="ram_gb" min="1" required></div>
            <div class="field"><label>VRAM (GB)</label><input type="number" name="vram_gb" min="0" required></div>
            <div class="field"><label>Armazenamento (GB)</label><input type="number" name="armazenamento" min="1" required></div>
            <div class="field"><label>Imagem de capa</label><input type="file" name="foto" accept="image/png, image/jpeg, image/webp" required></div>

            <div class="field">
                <label>Arquivo do jogo (.zip ou .exe)</label>
                <input type="file" name="arquivo_jogo" accept=".zip,.exe" required>
            </div>
            <div class="admin-form-acoes admin-form-grid-full">
                <button type="submit" class="btn btn-primary">Cadastrar jogo</button>
            </div>
        </form>
    </main>

    <?php include __DIR__ . "/parciais/footer.php"; ?>
</body>
</html>
