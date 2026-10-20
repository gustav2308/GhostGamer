<?php
require_once __DIR__ . "/../PAGINAS/parciais/bootstrap.php";
$base = "../";
$paginaAtual = "consulta.php";

if (!$logado || $tipo !== "admin") {
    header("Location: ../PAGINAS/loginCliente.php");
    exit;
}

$pdo = conectarBanco();
$aba = $_GET["aba"] ?? "clientes";
$pesquisa = trim($_GET["pesquisa"] ?? "");

$clientes = [];
$empresas = [];
$jogos = [];

if ($aba === "clientes") {
    if ($pesquisa !== "") {
        $stmt = $pdo->prepare(
            "SELECT * FROM clientes WHERE cli_nome LIKE :nome OR email LIKE :email OR nickname LIKE :nickname ORDER BY cli_nome"
        );
        $busca = "%$pesquisa%";
        $stmt->execute([
            "nome" => $busca,
            "email" => $busca,
            "nickname" => $busca
        ]);
    } else {
        $stmt = $pdo->query("SELECT * FROM clientes ORDER BY cli_nome");
    }
    $clientes = $stmt->fetchAll();
} elseif ($aba === "empresas") {
    if ($pesquisa !== "") {
        $stmt = $pdo->prepare(
            "SELECT * FROM empresa WHERE razao_social LIKE :razao_social OR nome_fantasia LIKE :nome_fantasia OR CNPJ LIKE :cnpj ORDER BY razao_social"
        );
        $busca = "%$pesquisa%";
        $stmt->execute([
            "razao_social" => $busca,
            "nome_fantasia" => $busca,
            "cnpj" => $busca
        ]);
    } else {
        $stmt = $pdo->query("SELECT * FROM empresa ORDER BY razao_social");
    }
    $empresas = $stmt->fetchAll();
} else {
    $aba = "jogos";
    if ($pesquisa !== "") {
        $stmt = $pdo->prepare(
            "SELECT * FROM jogo WHERE titulo LIKE :titulo OR genero LIKE :genero OR empresa_email LIKE :empresa ORDER BY titulo"
        );
        $busca = "%$pesquisa%";
        $stmt->execute([
            "titulo" => $busca,
            "genero" => $busca,
            "empresa" => $busca
        ]);
    } else {
        $stmt = $pdo->query("SELECT * FROM jogo ORDER BY titulo");
    }
    $jogos = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ghost Gamer - Consulta</title>
    <link rel="stylesheet" href="../ASSETS/CSS/global.css">
    <link rel="stylesheet" href="../ASSETS/CSS/admin.css">
</head>
<body>

    <?php include __DIR__ . "/../PAGINAS/parciais/nav.php"; ?>

    <main class="container admin-page">
        <h1>Consulta administrativa</h1>

        <nav class="admin-abas">
            <a href="?aba=clientes" class="<?= $aba === 'clientes' ? 'ativo' : '' ?>">Clientes</a>
            <a href="?aba=empresas" class="<?= $aba === 'empresas' ? 'ativo' : '' ?>">Empresas</a>
            <a href="?aba=jogos" class="<?= $aba === 'jogos' ? 'ativo' : '' ?>">Jogos</a>
        </nav>

        <form class="admin-busca" method="get">
            <input type="hidden" name="aba" value="<?= htmlspecialchars($aba) ?>">
            <input type="text" name="pesquisa" placeholder="Pesquisar..." value="<?= htmlspecialchars($pesquisa) ?>">
            <button type="submit" class="btn btn-primary btn-sm"><?= icone('buscar', 16) ?> Buscar</button>
        </form>

        <form action="gerarPDF.php" method="post" target="_blank" class="admin-pdf-form">
            <input type="hidden" name="pesquisa" value="<?= htmlspecialchars($pesquisa) ?>">
            <input type="hidden" name="acao<?= $aba === 'clientes' ? '2' : ($aba === 'empresas' ? '1' : '3') ?>" value="1">
            <button type="submit" class="btn btn-outline btn-sm"><?= icone('download', 16) ?> Baixar PDF desta lista</button>
        </form>

        <?php if ($aba === "clientes"): ?>
            <div class="admin-tabela-wrap">
                <table class="admin-tabela">
                    <thead>
                        <tr>
                            <th>ID</th><th>Nome</th><th>E-mail</th><th>Nickname</th><th>Tipo</th><th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($clientes)): ?>
                            <tr><td colspan="6" class="admin-vazio">Nenhum cliente encontrado.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($clientes as $c): ?>
                            <tr>
                                <td><?= (int) $c["id_cliente"] ?></td>
                                <td><?= htmlspecialchars($c["cli_nome"]) ?></td>
                                <td><?= htmlspecialchars($c["email"]) ?></td>
                                <td><?= htmlspecialchars($c["nickname"]) ?></td>
                                <td><span class="badge <?= $c["administrador"] === "admin" ? "badge-admin" : "badge-usuario" ?>"><?= htmlspecialchars($c["administrador"]) ?></span></td>
                                <td class="admin-acoes">
                                    <a href="alteraCliente.php?id=<?= (int) $c["id_cliente"] ?>"><?= icone('editar', 16) ?></a>
                                    <form method="post" action="exclui.php" onsubmit="return confirm('Excluir este cliente? Esta ação não pode ser desfeita.');">
                                        <input type="hidden" name="tabela" value="clientes">
                                        <input type="hidden" name="id" value="<?= (int) $c["id_cliente"] ?>">
                                        <button type="submit" class="admin-btn-excluir"><?= icone('fechar', 16) ?></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php elseif ($aba === "empresas"): ?>
            <div class="admin-tabela-wrap">
                <table class="admin-tabela">
                    <thead>
                        <tr><th>ID</th><th>Razão social</th><th>CNPJ</th><th>Cidade</th><th>Ações</th></tr>
                    </thead>
                    <tbody>
                        <?php if (empty($empresas)): ?>
                            <tr><td colspan="5" class="admin-vazio">Nenhuma empresa encontrada.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($empresas as $e): ?>
                            <tr>
                                <td><?= (int) $e["id_empresa"] ?></td>
                                <td><?= htmlspecialchars($e["razao_social"]) ?></td>
                                <td><?= htmlspecialchars($e["CNPJ"]) ?></td>
                                <td><?= htmlspecialchars($e["cidade"]) ?></td>
                                <td class="admin-acoes">
                                    <a href="alteraEmpresa.php?id=<?= (int) $e["id_empresa"] ?>"><?= icone('editar', 16) ?></a>
                                    <form method="post" action="exclui.php" onsubmit="return confirm('Excluir esta empresa?');">
                                        <input type="hidden" name="tabela" value="empresa">
                                        <input type="hidden" name="id" value="<?= (int) $e["id_empresa"] ?>">
                                        <button type="submit" class="admin-btn-excluir"><?= icone('fechar', 16) ?></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="admin-tabela-wrap">
                <table class="admin-tabela">
                    <thead>
                        <tr><th>ID</th><th>Título</th><th>Gênero</th><th>Empresa</th><th>Ações</th></tr>
                    </thead>
                    <tbody>
                        <?php if (empty($jogos)): ?>
                            <tr><td colspan="5" class="admin-vazio">Nenhum jogo encontrado.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($jogos as $j): ?>
                            <tr>
                                <td><?= (int) $j["id_jogo"] ?></td>
                                <td><?= htmlspecialchars($j["titulo"]) ?></td>
                                <td><?= htmlspecialchars($j["genero"] ?: "-") ?></td>
                                <td><?= htmlspecialchars($j["empresa_email"] ?: "-") ?></td>
                                <td class="admin-acoes">
                                    <a href="alteraJogo.php?id=<?= (int) $j["id_jogo"] ?>"><?= icone('editar', 16) ?></a>
                                    <form method="post" action="exclui.php" onsubmit="return confirm('Excluir este jogo?');">
                                        <input type="hidden" name="tabela" value="jogo">
                                        <input type="hidden" name="id" value="<?= (int) $j["id_jogo"] ?>">
                                        <button type="submit" class="admin-btn-excluir"><?= icone('fechar', 16) ?></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </main>

    <?php include __DIR__ . "/../PAGINAS/parciais/footer.php"; ?>
</body>
</html>