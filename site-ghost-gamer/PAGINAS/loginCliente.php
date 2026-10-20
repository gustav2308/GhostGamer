<?php
require_once __DIR__ . "/parciais/bootstrap.php";
$base = "../";
$paginaAtual = "loginCliente.php";

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
    <title>Ghost Gamer - Login</title>
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

            <div class="auth-tabs">
                <a href="loginCliente.php" class="ativo">Entrar</a>
                <a href="cadastroCliente.php">Cadastrar</a>
            </div>

            <h2>Bem-vindo de volta</h2>
            <p class="auth-subtitulo">Entre para acessar sua biblioteca e seu perfil.</p>

            <div id="msgLogin" class="form-msg"></div>

            <form id="formLogin" novalidate>
                <div class="field">
                    <label for="email">E-mail</label>
                    <input type="email" name="email" id="email" placeholder="Digite seu email" required>
                </div>

                <div class="field">
                    <label for="senha">Senha</label>
                    <input type="password" name="senha" id="senha" placeholder="Digite sua senha" required>
                </div>

                <label class="campo-checkbox">
                    <input type="checkbox" name="lembrar" value="1">
                    <span>Lembrar-me neste dispositivo</span>
                </label>

                <button type="submit" class="btn btn-primary btn-block" id="btnLogin">Entrar</button>

                <a href="esqueciSenha.php" class="auth-link-secundario">Esqueci minha senha</a>

                <p class="auth-rodape">
                    Não tem uma conta? <a href="cadastroCliente.php">Cadastre-se</a>
                </p>
            </form>
        </div>
    </main>

    <?php include __DIR__ . "/parciais/footer.php"; ?>
    <script src="../ASSETS/JS/auth.js"></script>
</body>
</html>
