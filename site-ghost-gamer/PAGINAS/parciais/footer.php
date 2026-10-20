<?php
/* Rodapé padrão do Ghost Gamer. Usa a mesma $base do header (nav.php). */
$base = $base ?? "";
?>
    <footer class="site-footer">
        <div class="footer-grade">
            <div class="footer-marca">
                <div class="footer-logo-linha">
                    <img src="<?= $base ?>ASSETS/IMG/logo.png" alt="" style="height:32px;">
                    <span>GHOST GAMER</span>
                </div>
                <p>Descubra jogos, monte sua biblioteca e confira os requisitos antes de jogar.</p>
            </div>

            <div>
                <h4>NAVEGAR</h4>
                <ul>
                    <li><a href="<?= $base ?>index.php">Home</a></li>
                    <li><a href="<?= $base ?>PAGINAS/categoria.php">Categorias</a></li>
                    <li><a href="<?= $base ?>PAGINAS/biblioteca.php">Biblioteca</a></li>
                </ul>
            </div>

            <div>
                <h4>CONTA</h4>
                <ul>
                    <?php if ($logado ?? false): ?>
                        <li><a href="<?= $base ?>PAGINAS/perfil.php">Perfil</a></li>
                        <li><a href="<?= $base ?>XAMP/logout.php">Sair</a></li>
                    <?php else: ?>
                        <li><a href="<?= $base ?>PAGINAS/loginCliente.php">Entrar</a></li>
                        <li><a href="<?= $base ?>PAGINAS/cadastroCliente.php">Criar conta</a></li>
                    <?php endif; ?>
                </ul>
            </div>

            <div>
                <h4>AJUDA</h4>
                <ul>
                    <li><a href="<?= $base ?>PAGINAS/suporte.php">Central de suporte</a></li>
                    <li><a href="<?= $base ?>PAGINAS/esqueciSenha.php">Esqueci minha senha</a></li>
                </ul>
            </div>
        </div>

        <p class="footer-copia">© <?= date("Y") ?> Ghost Gamer. Todos os direitos reservados.</p>
    </footer>

    <div id="toast-container" class="toast-container"></div>

    <script src="<?= $base ?>ASSETS/JS/global.js"></script>
