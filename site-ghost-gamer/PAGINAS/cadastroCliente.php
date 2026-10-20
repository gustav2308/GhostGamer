<?php
require_once __DIR__ . "/parciais/bootstrap.php";
$base = "../";
$paginaAtual = "cadastroCliente.php";

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
    <title>Ghost Gamer - Cadastro</title>
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
                <a href="loginCliente.php">Entrar</a>
                <a href="cadastroCliente.php" class="ativo">Cadastrar</a>
            </div>

            <h2>Crie sua conta</h2>
            <p class="auth-subtitulo">Cadastre-se para salvar sua biblioteca e seu perfil.</p>

            <div id="msgCadastro" class="form-msg"></div>

            <form id="formCadastro" novalidate>
                <div class="field">
                    <label for="cli_nome">Nome completo</label>
                    <input type="text" name="cli_nome" id="cli_nome" placeholder="Digite seu nome completo" required>
                </div>

                <div class="field">
                    <label for="email">E-mail</label>
                    <input type="email" name="email" id="email" placeholder="Digite seu email" required>
                </div>

                <div class="field">
                    <label for="nickname">Nickname</label>
                    <input type="text" name="nickname" id="nickname" placeholder="Como quer ser chamado no launcher" required>
                </div>

                <div class="field">
                    <label for="senha">Senha</label>
                    <input type="password" name="senha" id="senha" placeholder="Mínimo 6 caracteres" required minlength="6">
                </div>

                <button type="submit" class="btn btn-primary btn-block" id="btnCadastro">Criar conta</button>

                <p class="auth-rodape">
                    Já tem uma conta? <a href="loginCliente.php">Faça login</a>
                </p>
            </form>
        </div>
    </main>

    <?php include __DIR__ . "/parciais/footer.php"; ?>
    <script src="../ASSETS/JS/auth.js"></script>
</body>
</html>
