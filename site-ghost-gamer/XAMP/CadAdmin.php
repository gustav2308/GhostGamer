<?php
/**
 * Criação de conta administradora.
 *
 * ANTES: qualquer pessoa podia acessar esta página e criar uma conta
 * admin sem estar logada (falha grave de segurança). AGORA só é
 * permitido se: (a) já existe um admin logado, ou (b) ainda não existe
 * NENHUM admin cadastrado no sistema (permite configurar o primeiro
 * admin ao instalar o site). Depois que o primeiro admin existir, esta
 * página exige login de admin para criar outros.
 */

require_once __DIR__ . "/../PAGINAS/parciais/bootstrap.php";
$base = "../";

$pdo = conectarBanco();
$totalAdmins = (int) $pdo->query("SELECT COUNT(*) FROM clientes WHERE administrador = 'admin'")->fetchColumn();

$podeAcessar = ($logado && $tipo === "admin") || $totalAdmins === 0;

if (!$podeAcessar) {
    header("Location: ../PAGINAS/loginCliente.php");
    exit;
}

$erro = null;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nome  = trim($_POST["cli_nome"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $senha = (string) ($_POST["senha"] ?? "");
    $nick  = trim($_POST["nickname"] ?? "");

    if ($nome === "" || $nick === "" || !filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($senha) < 6) {
        $erro = "Preencha todos os campos corretamente (senha com pelo menos 6 caracteres).";
    } else {
        $existe = $pdo->prepare("SELECT 1 FROM clientes WHERE email = :email");
        $existe->execute(["email" => $email]);
        if ($existe->fetchColumn()) {
            $erro = "Já existe uma conta com este e-mail.";
        } else {
            $insere = $pdo->prepare(
                "INSERT INTO clientes (cli_nome, email, senha, nickname, administrador) VALUES (:nome, :email, :senha, :nick, 'admin')"
            );
            $insere->execute([
                "nome" => $nome, "email" => $email,
                "senha" => password_hash($senha, PASSWORD_DEFAULT), "nick" => $nick,
            ]);
            header("Location: " . ($logado ? "consulta.php?aba=clientes" : "../PAGINAS/loginCliente.php"));
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ghost Gamer - Novo administrador</title>
    <link rel="stylesheet" href="../ASSETS/CSS/global.css">
    <link rel="stylesheet" href="../ASSETS/CSS/auth.css">
</head>
<body>

    <main class="auth-page">
        <div class="panel auth-box">
            <div class="auth-logo">
                <img src="../ASSETS/IMG/logo.png" alt="Ghost Gamer">
                <span>GHOST GAMER</span>
            </div>

            <h2>Nova conta administradora</h2>
            <p class="auth-subtitulo"><?= $totalAdmins === 0 ? "Configuração inicial: crie o primeiro administrador do sistema." : "Criar um novo administrador." ?></p>

            <?php if ($erro): ?><div class="form-msg erro"><?= htmlspecialchars($erro) ?></div><?php endif; ?>

            <form method="post">
                <div class="field"><label>Nome completo</label><input type="text" name="cli_nome" required></div>
                <div class="field"><label>E-mail</label><input type="email" name="email" required></div>
                <div class="field"><label>Nickname</label><input type="text" name="nickname" required></div>
                <div class="field"><label>Senha</label><input type="password" name="senha" minlength="6" required></div>
                <button type="submit" class="btn btn-primary btn-block">Criar conta admin</button>
            </form>
        </div>
    </main>
</body>
</html>
