<?php
/**
 * Inicia a sessão com cookies mais seguros: httponly (JavaScript não
 * consegue ler, reduz impacto de um XSS) e samesite=Lax (o navegador
 * não envia o cookie em requisições disparadas por outros sites,
 * reduzindo CSRF). Use no lugar de session_start() puro em qualquer
 * arquivo que não passe primeiro por parciais/bootstrap.php.
 */
function iniciarSessaoSegura(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params([
            "lifetime" => 0,
            "path"     => "/",
            "httponly" => true,
            "samesite" => "Lax",
        ]);
        session_start();
    }
}
