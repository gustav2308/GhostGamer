<?php
require_once __DIR__ . "/parciais/bootstrap.php";
$base = "../";
$paginaAtual = "perfil.php";

// Só pode ver o próprio perfil se estiver logado
if (!$logado) {
    header("Location: loginCliente.php?redirect=perfil.php");
    exit;
}

// Busca dados atualizados do usuário direto no banco (fonte da verdade)
$stmt = conectarBanco()->prepare("SELECT * FROM clientes WHERE id_cliente = :id");
$stmt->execute(["id" => $usuarioLogado["id"]]);
$cliente = $stmt->fetch();

if (!$cliente) {
    header("Location: ../XAMP/logout.php");
    exit;
}

$fotoPerfil = urlFotoPerfil($cliente["foto"], $base);

// Jogos da biblioteca do usuário (dados reais, não mockados)
$stmtBiblioteca = conectarBanco()->prepare(
    "SELECT jogo.id_jogo, jogo.titulo, jogo.foto, jogo.genero, biblioteca.data_adicionado
     FROM biblioteca
     INNER JOIN jogo ON jogo.id_jogo = biblioteca.id_jogo
     WHERE biblioteca.id_cliente = :id
     ORDER BY biblioteca.data_adicionado DESC"
);
$stmtBiblioteca->execute(["id" => $cliente["id_cliente"]]);
$jogosBiblioteca = $stmtBiblioteca->fetchAll();
$totalJogos = count($jogosBiblioteca);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ghost Gamer - Perfil</title>
    <link rel="stylesheet" href="../ASSETS/CSS/global.css">
    <link rel="stylesheet" href="../ASSETS/CSS/perfil.css">
</head>
<body>

    <?php $usuarioLogado["foto"] = $cliente["foto"]; include __DIR__ . "/parciais/nav.php"; ?>

    <main class="container perfil-page">

        <section class="perfil-header panel">
            <div class="perfil-header-info">
                <div class="perfil-avatar-wrap">
                    <?php if ($fotoPerfil): ?>
                        <img src="<?= htmlspecialchars($fotoPerfil) ?>" class="perfil-avatar" alt="Foto de perfil" id="previewAvatarAtual">
                    <?php else: ?>
                        <div class="perfil-avatar perfil-avatar--iniciais" id="previewAvatarAtual"><?= htmlspecialchars(iniciaisNome($cliente["cli_nome"])) ?></div>
                    <?php endif; ?>
                </div>

                <div>
                    <span class="badge <?= $cliente["administrador"] === "admin" ? "badge-admin" : "badge-usuario" ?>">
                        <?= $cliente["administrador"] === "admin" ? "Administrador" : "Jogador" ?>
                    </span>
                    <h1 id="nomeExibido"><?= htmlspecialchars($cliente["nickname"] ?: $cliente["cli_nome"]) ?></h1>
                    <p class="perfil-subtitulo"><?= htmlspecialchars($cliente["cli_nome"]) ?> · <?= htmlspecialchars($cliente["email"]) ?></p>
                    <p class="status"><span class="ponto-online"></span> Online</p>
                </div>
            </div>

            <button class="btn btn-outline" id="btnAbrirEdicao"><?= icone('editar', 16) ?> Editar perfil</button>
        </section>

        <div class="perfil-content">

            <div class="activity">
                <div class="activity-header">
                    <h3>Sua biblioteca</h3>
                </div>

                <?php if ($totalJogos === 0): ?>
                    <div class="panel perfil-vazio">
                        <p>Você ainda não tem jogos na biblioteca.</p>
                        <a href="biblioteca.php" class="btn btn-primary">Explorar jogos</a>
                    </div>
                <?php else: ?>
                    <?php foreach ($jogosBiblioteca as $jogo): ?>
                        <div class="activity-card">
                            <div class="game-thumb"
                                <?php if ($jogo["foto"]): ?>
                                    style="background-image:url('<?= htmlspecialchars($base . $jogo["foto"]) ?>')"
                                <?php endif; ?>
                            ></div>
                            <div class="activity-info">
                                <h4><?= htmlspecialchars($jogo["titulo"]) ?></h4>
                                <span class="activity-genero"><?= htmlspecialchars($jogo["genero"] ?: "Sem gênero") ?></span>
                            </div>
                            <a href="telaJogo.php?id=<?= (int) $jogo["id_jogo"] ?>" class="btn btn-primary btn-sm">JOGAR</a>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="profile-side">
                <div class="side-box">
                    <p><?= icone('biblioteca', 16) ?> Jogos na biblioteca</p>
                    <span><?= $totalJogos ?></span>
                </div>
                <div class="side-box">
                    <p><?= icone('escudo-check', 16) ?> Tipo de conta</p>
                    <span><?= $cliente["administrador"] === "admin" ? "Admin" : "Usuário" ?></span>
                </div>
            </div>

        </div>
    </main>

    <!-- MODAL EDITAR PERFIL -->
    <div id="modalEditarPerfil" class="modal-overlay">
        <div class="modal-box panel">
            <div class="modal-header">
                <h3>Editar perfil</h3>
                <button type="button" id="btnFecharEdicao" class="modal-fechar"><?= icone('fechar', 16) ?></button>
            </div>

            <form id="formEditarPerfil" enctype="multipart/form-data" novalidate>
                <div id="msgEditarPerfil" class="form-msg"></div>

                <div class="modal-avatar-picker">
                    <?php if ($fotoPerfil): ?>
                        <img src="<?= htmlspecialchars($fotoPerfil) ?>" class="perfil-avatar" id="previewNovaFoto" alt="">
                    <?php else: ?>
                        <div class="perfil-avatar perfil-avatar--iniciais" id="previewNovaFoto"><?= htmlspecialchars(iniciaisNome($cliente["cli_nome"])) ?></div>
                    <?php endif; ?>

                    <label for="inputFoto" class="btn btn-outline btn-sm">Trocar foto</label>
                    <input type="file" id="inputFoto" name="foto" accept="image/png, image/jpeg, image/webp" hidden>
                    <p class="field-hint">JPG, PNG ou WEBP · até 3MB</p>
                </div>

                <div class="field">
                    <label for="inputNome">Nome completo</label>
                    <input type="text" id="inputNome" name="cli_nome" value="<?= htmlspecialchars($cliente["cli_nome"]) ?>" required>
                </div>

                <div class="field">
                    <label for="inputNickname">Nickname</label>
                    <input type="text" id="inputNickname" name="nickname" value="<?= htmlspecialchars($cliente["nickname"]) ?>" required>
                </div>

                <button type="submit" class="btn btn-primary btn-block" id="btnSalvarPerfil">Salvar alterações</button>
            </form>
        </div>
    </div>

    <?php include __DIR__ . "/parciais/footer.php"; ?>
    <script src="../ASSETS/JS/perfil.js"></script>
</body>
</html>
