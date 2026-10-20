<?php
/**
 * Proteção contra CSRF (Cross-Site Request Forgery).
 *
 * Sem isso, um site malicioso poderia montar um formulário escondido
 * apontando para, por exemplo, XAMP/exclui.php e, se um admin logado
 * clicasse em qualquer lugar desse site malicioso, o navegador dele
 * enviaria o cookie de sessão automaticamente e a exclusão aconteceria
 * -- mesmo sem o admin nunca ter visitado o Ghost Gamer naquele
 * momento. O token abaixo só existe dentro do formulário de verdade,
 * então um site de fora não tem como adivinhá-lo.
 *
 * Uso:
 *   No formulário:  <?= campoCsrf() ?>
 *   No handler:      exigirCsrfValido();   // encerra a requisição se inválido
 */

require_once __DIR__ . "/sessao_segura.php";

function tokenCsrf(): string
{
    iniciarSessaoSegura();
    if (empty($_SESSION["csrf_token"])) {
        $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
    }
    return $_SESSION["csrf_token"];
}

/** Imprime o campo escondido pronto para colar dentro de um <form>. */
function campoCsrf(): string
{
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(tokenCsrf()) . '">';
}

function csrfValido(?string $tokenEnviado): bool
{
    iniciarSessaoSegura();
    if (empty($_SESSION["csrf_token"]) || empty($tokenEnviado)) {
        return false;
    }
    return hash_equals($_SESSION["csrf_token"], $tokenEnviado);
}

/**
 * Chame no topo de qualquer handler POST que precise de proteção
 * CSRF. Responde com o formato certo (JSON ou redirect) e encerra a
 * requisição se o token não bater.
 */
function exigirCsrfValido(bool $comoJson = true, string $voltarPara = "../index.php"): void
{
    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        return;
    }
    if (csrfValido($_POST["csrf_token"] ?? null)) {
        return;
    }

    if ($comoJson) {
        header("Content-Type: application/json; charset=utf-8");
        http_response_code(419);
        echo json_encode(["ok" => false, "mensagem" => "Sua sessão expirou. Atualize a página e tente novamente."]);
    } else {
        header("Location: $voltarPara?erro=" . urlencode("Sua sessão expirou. Atualize a página e tente novamente."));
    }
    exit;
}
