<?php
require_once __DIR__ . "/parciais/bootstrap.php";
$base = "../";

if (!isset($_SESSION["email_recuperacao"])) {
    header("Location: esqueciSenha.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ghost Gamer - Verificar código</title>
    <link rel="stylesheet" href="../ASSETS/CSS/global.css">
    <link rel="stylesheet" href="../ASSETS/CSS/auth.css">
</head>
<body>

    <?php include __DIR__ . "/parciais/nav.php"; ?>

    <main class="auth-page">
        <div class="panel auth-box">
            <div class="auth-logo">
                <img src="../ASSETS/IMG/logo.png" alt="Ghost Gamer">
                <span>GHOST GAMER</span>
            </div>

            <h2>Verificar código</h2>
            <p class="auth-subtitulo">Digite o código de 6 dígitos enviado para o seu e-mail.</p>

            <div id="msgCodigo" class="form-msg"></div>

            <form id="formCodigo" novalidate>
                <div class="field">
                    <label for="codigo">Código</label>
                    <input type="text" name="codigo" id="codigo" maxlength="6" inputmode="numeric" pattern="[0-9]{6}" placeholder="000000" required>
                </div>

                <button type="submit" class="btn btn-primary btn-block" id="btnCodigo">Verificar código</button>

                <p class="auth-rodape">
                    <a href="esqueciSenha.php">Reenviar código</a>
                </p>
            </form>
        </div>
    </main>

    <?php include __DIR__ . "/parciais/footer.php"; ?>
    <script src="../ASSETS/JS/recuperarSenha.js"></script>
</body>
</html>
