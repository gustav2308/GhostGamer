<?php
/**
 * Partial de navegação (header + menu).
 *
 * Espera que a página que faz o include já tenha definido:
 *   $base          -> "" se a página está na raiz (index.php)
 *                      ou "../" se está dentro de /PAGINAS ou /XAMP
 *   $paginaAtual   -> nome do arquivo atual, ex: "suporte.php" (opcional, para destacar o link ativo)
 *   $mostrarBusca  -> true para mostrar a barra de busca (opcional; padrão: true)
 *
 * E que já tenha rodado parciais/bootstrap.php (fornece $logado, $tipo, $usuarioLogado).
 *
 * Ids/classes usados pelo JavaScript (ASSETS/JS/global.js) e pelo CSS
 * (ASSETS/CSS/global.css) — não renomear nem remover:
 *   #menu-btn (JS) + .menu-btn (CSS), #menu + .menu (CSS), .ativo,
 *   #overlay + .overlay (CSS), #searchInput
 */
$base         = $base ?? "";
$paginaAtual  = $paginaAtual ?? "";
$mostrarBusca = $mostrarBusca ?? true;

$fotoPerfil = $logado ? urlFotoPerfil($usuarioLogado["foto"], $base) : null;
$nomeExib   = $logado ? ($usuarioLogado["nickname"] ?: $usuarioLogado["nome"]) : "Visitante";
?>
<header class="topbar<?= $mostrarBusca ? '' : ' sem-busca' ?>">
    <button id="menu-btn" class="menu-btn" type="button" aria-label="Abrir menu" aria-haspopup="true" aria-controls="menu"><?= icone('menu', 20) ?></button>

    <a href="<?= $base ?>index.php" class="logo-aba" aria-label="Ghost Gamer - ir para a Home">
        <img src="<?= $base ?>ASSETS/IMG/logo.png" class="logo-img" alt="">
        <span class="logo-text">GHOST GAMER</span>
    </a>

    <nav id="menu" class="menu" aria-label="Menu principal">
        <ul class="menu-principal">
            <li><a class="<?= $paginaAtual === 'index.php' ? 'ativo' : '' ?>" href="<?= $base ?>index.php"><?= icone('home', 18) ?><span>Home</span></a></li>
            <li><a class="<?= $paginaAtual === 'categoria.php' ? 'ativo' : '' ?>" href="<?= $base ?>PAGINAS/categoria.php"><?= icone('categoria', 18) ?><span>Categorias</span></a></li>
            <li><a class="<?= $paginaAtual === 'biblioteca.php' ? 'ativo' : '' ?>" href="<?= $base ?>PAGINAS/biblioteca.php"><?= icone('biblioteca', 18) ?><span>Biblioteca</span></a></li>
            <li><a class="<?= $paginaAtual === 'suporte.php' ? 'ativo' : '' ?>" href="<?= $base ?>PAGINAS/suporte.php"><?= icone('suporte', 18) ?><span>Suporte</span></a></li>
        </ul>

        <ul class="menu-conta">
            <?php if ($tipo === "admin"): ?>
                <li>
                    <details class="menu-admin">
                        <summary title="Administração"><?= icone('painel', 18) ?><span class="rotulo">Administração</span><span class="seta"><?= icone('seta-baixo', 14) ?></span></summary>
                        <ul>
                            <li><a href="<?= $base ?>XAMP/consulta.php"><?= icone('escudo-check', 16) ?> Consulta</a></li>
                            <li><a href="<?= $base ?>PAGINAS/cadastroEmpresa.php"><?= icone('empresa', 16) ?> Cadastro de Empresa</a></li>
                            <li><a href="<?= $base ?>PAGINAS/cadastroJogos.php"><?= icone('jogo', 16) ?> Cadastro de Jogos</a></li>
                        </ul>
                    </details>
                </li>
            <?php endif; ?>

            <?php if ($logado): ?>
                <li>
                    <a class="<?= $paginaAtual === 'perfil.php' ? 'ativo' : '' ?>" href="<?= $base ?>PAGINAS/perfil.php" title="Perfil">
                        <?php if ($fotoPerfil): ?>
                            <img src="<?= htmlspecialchars($fotoPerfil) ?>" alt="" style="width:20px;height:20px;border-radius:50%;object-fit:cover;">
                        <?php else: ?>
                            <?= icone('perfil', 18) ?>
                        <?php endif; ?>
                        <span class="rotulo"><?= htmlspecialchars($nomeExib) ?></span>
                    </a>
                </li>
                <li><a href="<?= $base ?>XAMP/logout.php" class="menu-sair" title="Sair"><?= icone('sair', 18) ?><span class="rotulo">Sair</span></a></li>
            <?php else: ?>
                <li><a href="<?= $base ?>PAGINAS/cadastroCliente.php" class="<?= $paginaAtual === 'cadastroCliente.php' ? 'ativo' : '' ?>"><?= icone('cadastro', 18) ?> Cadastro</a></li>
                <li><a href="<?= $base ?>PAGINAS/loginCliente.php" class="menu-entrar"><?= icone('login', 18) ?>Entrar</a></li>
            <?php endif; ?>
        </ul>
    </nav>

    <?php if ($mostrarBusca): ?>
        <div class="busca">
            <span class="icone-busca"><?= icone('buscar', 16) ?></span>
            <input type="search" id="searchInput" placeholder="Buscar jogos..." aria-label="Buscar jogos" autocomplete="off">
        </div>
    <?php endif; ?>
</header>
<div class="overlay" id="overlay"></div>
