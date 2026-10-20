<?php
require_once __DIR__ . "/../PAGINAS/parciais/bootstrap.php";
$base = "../";

if (!$logado || $tipo !== "admin") {
    header("Location: ../PAGINAS/loginCliente.php");
    exit;
}

$pdo = conectarBanco();
$id = (int) ($_GET["id"] ?? $_POST["id_cliente"] ?? 0);
$erro = null;
$sucesso = null;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nome     = trim($_POST["cli_nome"] ?? "");
    $email    = trim($_POST["email"] ?? "");
    $nickname = trim($_POST["nickname"] ?? "");
    $novaSenha = trim($_POST["senha"] ?? "");
    $administrador = ($_POST["administrador"] ?? "usuario") === "admin" ? "admin" : "usuario";

    if ($nome === "" || !filter_var($email, FILTER_VALIDATE_EMAIL) || $nickname === "") {
        $erro = "Preencha nome, e-mail válido e nickname.";
    } else {
        if ($novaSenha !== "") {
            // Só atualiza a senha se o admin realmente digitou uma nova
            $stmt = $pdo->prepare(
                "UPDATE clientes SET cli_nome=:nome, email=:email, nickname=:nickname, administrador=:adm, senha=:senha WHERE id_cliente=:id"
            );
            $stmt->execute([
                "nome" => $nome, "email" => $email, "nickname" => $nickname,
                "adm" => $administrador, "senha" => password_hash($novaSenha, PASSWORD_DEFAULT), "id" => $id,
            ]);
        } else {
            $stmt = $pdo->prepare(
                "UPDATE clientes SET cli_nome=:nome, email=:email, nickname=:nickname, administrador=:adm WHERE id_cliente=:id"
            );
            $stmt->execute(["nome" => $nome, "email" => $email, "nickname" => $nickname, "adm" => $administrador, "id" => $id]);
        }
        header("Location: consulta.php?aba=clientes&sucesso=1");
        exit;
    }
}

$stmt = $pdo->prepare("SELECT * FROM clientes WHERE id_cliente = :id");
$stmt->execute(["id" => $id]);
$cliente = $stmt->fetch();

if (!$cliente) {
    header("Location: consulta.php?aba=clientes");
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ghost Gamer - Editar cliente</title>
    <link rel="stylesheet" href="../ASSETS/CSS/global.css">
    <link rel="stylesheet" href="../ASSETS/CSS/admin.css">
</head>
<body>

    <?php include __DIR__ . "/../PAGINAS/parciais/nav.php"; ?>

    <main class="container admin-page">
        <h1>Editar cliente</h1>

        <?php if ($erro): ?><div class="form-msg erro"><?= htmlspecialchars($erro) ?></div><?php endif; ?>

        <form method="post" class="panel admin-form">
            <input type="hidden" name="id_cliente" value="<?= (int) $cliente["id_cliente"] ?>">

            <div class="field">
                <label>Nome completo</label>
                <input type="text" name="cli_nome" value="<?= htmlspecialchars($cliente["cli_nome"]) ?>" required>
            </div>
            <div class="field">
                <label>E-mail</label>
                <input type="email" name="email" value="<?= htmlspecialchars($cliente["email"]) ?>" required>
            </div>
            <div class="field">
                <label>Nickname</label>
                <input type="text" name="nickname" value="<?= htmlspecialchars($cliente["nickname"]) ?>" required>
            </div>
            <div class="field">
                <label>Tipo de conta</label>
                <select name="administrador">
                    <option value="usuario" <?= $cliente["administrador"] === "usuario" ? "selected" : "" ?>>Usuário</option>
                    <option value="admin" <?= $cliente["administrador"] === "admin" ? "selected" : "" ?>>Administrador</option>
                </select>
            </div>
            <div class="field">
                <label>Nova senha</label>
                <input type="password" name="senha" placeholder="Deixe em branco para manter a senha atual">
            </div>

            <div class="admin-form-acoes">
                <a href="consulta.php?aba=clientes" class="btn btn-outline">Cancelar</a>
                <button type="submit" class="btn btn-primary">Salvar alterações</button>
            </div>
        </form>
    </main>

    <?php include __DIR__ . "/../PAGINAS/parciais/footer.php"; ?>
</body>
</html>
