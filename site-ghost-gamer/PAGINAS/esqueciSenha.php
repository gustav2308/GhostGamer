<?php
require_once __DIR__ . "/parciais/bootstrap.php";
$base = "../";

if ($logado) {
    header("Location: ../index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ghost Gamer - Recuperar senha</title>
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

            <h2>Recuperar senha</h2>
            <p class="auth-subtitulo">Informe o e-mail cadastrado e enviaremos um código de verificação.</p>

            <div id="msgRecuperar" class="form-msg"></div>

            <form id="formRecuperar" novalidate>
                <div class="field">
                    <label for="email">E-mail</label>
                    <input type="email" name="email" id="email" placeholder="Digite seu e-mail" required>
                </div>

                <button type="submit" class="btn btn-primary btn-block" id="btnRecuperar">Enviar código</button>

                <p class="auth-rodape">
                    <a href="loginCliente.php">Voltar para o login</a>
                </p>
            </form>
        </div>
    </main>

    <?php include __DIR__ . "/parciais/footer.php"; ?>
    <script src="../ASSETS/JS/recuperarSenha.js"></script>
</body>
</html>
