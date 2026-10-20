<?php
require_once __DIR__ . "/parciais/bootstrap.php";
$base = "../";
$paginaAtual = "cadastroEmpresa.php";

if (!$logado || $tipo !== "admin") {
    header("Location: loginCliente.php");
    exit;
}

$estados = ["AC","AL","AP","AM","BA","CE","DF","ES","GO","MA","MT","MS","MG","PA","PB","PR",
            "PE","PI","RJ","RN","RS","RO","RR","SC","SP","SE","TO"];
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ghost Gamer - Cadastro de empresa</title>
    <link rel="stylesheet" href="../ASSETS/CSS/global.css">
    <link rel="stylesheet" href="../ASSETS/CSS/admin.css">
</head>
<body>

    <?php include __DIR__ . "/parciais/nav.php"; ?>

    <main class="container admin-page">
        <h1>Cadastro de empresa</h1>

        <form method="post" action="../XAMP/gravarcad.php" class="panel admin-form admin-form-grid">
            <div class="field"><label>Razão social</label><input type="text" name="razao_social" required></div>
            <div class="field"><label>Nome fantasia</label><input type="text" name="nome_fantasia"></div>
            <div class="field"><label>CNPJ</label><input type="text" name="CNPJ"></div>
            <div class="field"><label>Data de abertura</label><input type="date" name="data_abertura"></div>
            <div class="field"><label>Telefone</label><input type="text" name="telefone"></div>
            <div class="field"><label>E-mail</label><input type="email" name="email" required></div>
            <div class="field"><label>Rua</label><input type="text" name="Rua"></div>
            <div class="field"><label>Número</label><input type="text" name="numero"></div>
            <div class="field"><label>Bairro</label><input type="text" name="bairro"></div>
            <div class="field"><label>Cidade</label><input type="text" name="cidade"></div>

            <div class="field">
                <label>Estado</label>
                <select name="estado" required>
                    <option value="" disabled selected>Selecione o estado</option>
                    <?php foreach ($estados as $uf): ?>
                        <option value="<?= $uf ?>"><?= $uf ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="field"><label>CEP</label><input type="text" name="cep"></div>

            <div class="field">
                <label>País</label>
                <select name="pais" required>
                    <option value="" disabled selected>Selecione o país</option>
                    <option value="brasil">Brasil</option>
                    <option value="outros">Outros</option>
                </select>
            </div>

            <div class="admin-form-acoes admin-form-grid-full">
                <button type="submit" class="btn btn-primary">Cadastrar empresa</button>
            </div>
        </form>
    </main>

    <?php include __DIR__ . "/parciais/footer.php"; ?>
</body>
</html>
