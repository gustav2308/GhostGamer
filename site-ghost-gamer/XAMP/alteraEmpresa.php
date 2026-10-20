<?php
require_once __DIR__ . "/../PAGINAS/parciais/bootstrap.php";
$base = "../";

if (!$logado || $tipo !== "admin") {
    header("Location: ../PAGINAS/loginCliente.php");
    exit;
}

$pdo = conectarBanco();
$id = (int) ($_GET["id"] ?? $_POST["id_empresa"] ?? 0);

$campos = ['razao_social', 'nome_fantasia', 'CNPJ', 'data_abertura', 'telefone', 'email',
           'Rua', 'numero', 'bairro', 'cidade', 'estado', 'cep', 'pais'];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $valores = ["id" => $id];
    foreach ($campos as $campo) {
        $valores[$campo] = trim($_POST[$campo] ?? "");
    }

    $sets = implode(", ", array_map(fn($c) => "`$c` = :$c", $campos));
    $stmt = $pdo->prepare("UPDATE empresa SET $sets WHERE id_empresa = :id");
    $stmt->execute($valores);

    header("Location: consulta.php?aba=empresas&sucesso=1");
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM empresa WHERE id_empresa = :id");
$stmt->execute(["id" => $id]);
$empresa = $stmt->fetch();

if (!$empresa) {
    header("Location: consulta.php?aba=empresas");
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ghost Gamer - Editar empresa</title>
    <link rel="stylesheet" href="../ASSETS/CSS/global.css">
    <link rel="stylesheet" href="../ASSETS/CSS/admin.css">
</head>
<body>

    <?php include __DIR__ . "/../PAGINAS/parciais/nav.php"; ?>

    <main class="container admin-page">
        <h1>Editar empresa</h1>

        <form method="post" class="panel admin-form admin-form-grid">
            <input type="hidden" name="id_empresa" value="<?= (int) $empresa["id_empresa"] ?>">

            <div class="field"><label>Razão social</label><input type="text" name="razao_social" value="<?= htmlspecialchars($empresa["razao_social"]) ?>" required></div>
            <div class="field"><label>Nome fantasia</label><input type="text" name="nome_fantasia" value="<?= htmlspecialchars($empresa["nome_fantasia"] ?? "") ?>"></div>
            <div class="field"><label>CNPJ</label><input type="text" name="CNPJ" value="<?= htmlspecialchars($empresa["CNPJ"] ?? "") ?>"></div>
            <div class="field"><label>Data de abertura</label><input type="date" name="data_abertura" value="<?= htmlspecialchars($empresa["data_abertura"] ?? "") ?>"></div>
            <div class="field"><label>Telefone</label><input type="text" name="telefone" value="<?= htmlspecialchars($empresa["telefone"] ?? "") ?>"></div>
            <div class="field"><label>E-mail</label><input type="email" name="email" value="<?= htmlspecialchars($empresa["email"] ?? "") ?>"></div>
            <div class="field"><label>Rua</label><input type="text" name="Rua" value="<?= htmlspecialchars($empresa["Rua"] ?? "") ?>"></div>
            <div class="field"><label>Número</label><input type="text" name="numero" value="<?= htmlspecialchars($empresa["numero"] ?? "") ?>"></div>
            <div class="field"><label>Bairro</label><input type="text" name="bairro" value="<?= htmlspecialchars($empresa["bairro"] ?? "") ?>"></div>
            <div class="field"><label>Cidade</label><input type="text" name="cidade" value="<?= htmlspecialchars($empresa["cidade"] ?? "") ?>"></div>
            <div class="field"><label>Estado</label><input type="text" name="estado" value="<?= htmlspecialchars($empresa["estado"] ?? "") ?>"></div>
            <div class="field"><label>CEP</label><input type="text" name="cep" value="<?= htmlspecialchars($empresa["cep"] ?? "") ?>"></div>
            <div class="field"><label>País</label><input type="text" name="pais" value="<?= htmlspecialchars($empresa["pais"] ?? "") ?>"></div>

            <div class="admin-form-acoes admin-form-grid-full">
                <a href="consulta.php?aba=empresas" class="btn btn-outline">Cancelar</a>
                <button type="submit" class="btn btn-primary">Salvar alterações</button>
            </div>
        </form>
    </main>

    <?php include __DIR__ . "/../PAGINAS/parciais/footer.php"; ?>
</body>
</html>
