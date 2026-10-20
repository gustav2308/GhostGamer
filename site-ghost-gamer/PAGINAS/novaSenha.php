<?php
require_once __DIR__ . "/parciais/bootstrap.php";
$base = "../";

if (!isset($_SESSION["codigo_verificado"]) || !isset($_SESSION["email_recuperacao"])) {
    header("Location: esqueciSenha.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ghost Gamer - Nova senha</title>
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

            <h2>Defina sua nova senha</h2>
            <p class="auth-subtitulo">Escolha uma senha com pelo menos 6 caracteres.</p>

            <div id="msgNovaSenha" class="form-msg"></div>

            <form id="formNovaSenha" novalidate>
                <div class="field">
                    <label for="senha">Nova senha</label>
                    <input type="password" name="senha" id="senha" minlength="6" required>
                </div>
                <div class="field">
                    <label for="confirmar_senha">Confirme a nova senha</label>
                    <input type="password" name="confirmar_senha" id="confirmar_senha" minlength="6" required>
                </div>

                <button type="submit" class="btn btn-primary btn-block" id="btnNovaSenha">Alterar senha</button>
            </form>
        </div>
    </main>

    <?php include __DIR__ . "/parciais/footer.php"; ?>
    <script src="../ASSETS/JS/recuperarSenha.js"></script>
</body>
</html>
