<?php
require_once __DIR__ . "/parciais/bootstrap.php";
$base = "../";
$paginaAtual = "biblioteca.php";

if (!$logado) {
    header("Location: loginCliente.php?redirect=biblioteca.php");
    exit;
}

// Jogos da biblioteca do usuário, do mais recente para o mais antigo
$stmt = conectarBanco()->prepare(
    "SELECT jogo.id_jogo, jogo.titulo, jogo.foto, jogo.genero, biblioteca.data_adicionado
     FROM biblioteca
     INNER JOIN jogo ON jogo.id_jogo = biblioteca.id_jogo
     WHERE biblioteca.id_cliente = :id
     ORDER BY biblioteca.data_adicionado DESC"
);
$stmt->execute(["id" => $usuarioLogado["id"]]);
$jogos = $stmt->fetchAll();

$jogoRecente = $jogos[0] ?? null;
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ghost Gamer - Biblioteca</title>
    <link rel="stylesheet" href="../ASSETS/CSS/global.css">
    <link rel="stylesheet" href="../ASSETS/CSS/biblioteca.css">
</head>
<body>

    <?php include __DIR__ . "/parciais/nav.php"; ?>

    <main class="container biblioteca-page">

        <section class="titulo-biblioteca">
            <h1>Biblioteca de jogos</h1>
            <p>Todos os jogos que você já adicionou à sua conta.</p>
        </section>

        <?php if ($jogoRecente): ?>
            <section class="recentes">
                <h2>Jogado recentemente</h2>
                <a href="telaJogo.php?id=<?= (int) $jogoRecente["id_jogo"] ?>" class="card-recente">
                    <div class="card-recente-thumb"
                        <?php if ($jogoRecente["foto"]): ?>
                            style="background-image:url('<?= htmlspecialchars($base . $jogoRecente["foto"]) ?>')"
                        <?php endif; ?>
                    ></div>
                    <div>
                        <strong><?= htmlspecialchars($jogoRecente["titulo"]) ?></strong>
                        <span><?= htmlspecialchars($jogoRecente["genero"] ?: "Sem gênero") ?></span>
                    </div>
                    <span class="card-recente-acao"><?= icone('seta-direita', 18) ?></span>
                </a>
            </section>
        <?php endif; ?>

        <section class="titulo-jogos">
            <h2>Todos os jogos</h2>
            <span class="contador-jogos"><?= count($jogos) ?> jogo(s)</span>
        </section>

        <p id="buscaVazia" class="busca-vazia">Nenhum jogo encontrado para essa busca.</p>

        <section class="grid-jogos">
            <?php if (empty($jogos)): ?>
                <div class="panel biblioteca-vazia">
                    <p>Você ainda não possui jogos na biblioteca.</p>
                    <a href="categoria.php" class="btn btn-primary">Explorar categorias</a>
                </div>
            <?php else: ?>
                <?php foreach ($jogos as $jogo): ?>
                    <a href="telaJogo.php?id=<?= (int) $jogo["id_jogo"] ?>" class="card" data-titulo="<?= htmlspecialchars($jogo["titulo"]) ?>">
                        <div class="imagem-card"
                            <?php if ($jogo["foto"]): ?>
                                style="background-image:url('<?= htmlspecialchars($base . $jogo["foto"]) ?>')"
                            <?php endif; ?>
                        >
                            <?php if ($jogo["genero"]): ?><span class="badge"><?= htmlspecialchars($jogo["genero"]) ?></span><?php endif; ?>
                        </div>
                        <div class="informacoes-card">
                            <h3><?= htmlspecialchars($jogo["titulo"]) ?></h3>
                            <p><?= htmlspecialchars($jogo["genero"] ?: "Sem gênero") ?></p>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>

    </main>

    <?php include __DIR__ . "/parciais/footer.php"; ?>
</body>
</html>
