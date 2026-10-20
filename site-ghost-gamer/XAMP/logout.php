<?php
session_start();
require_once __DIR__ . "/conexao.php";

// Invalida o "lembrar-me" (cookie + token no banco) além de encerrar a
// sessão -- assim um cookie copiado antes do logout deixa de funcionar.
if (isset($_SESSION["id"])) {
    limparCookieLembrarMe(conectarBanco(), (int) $_SESSION["id"]);
} else {
    limparCookieLembrarMe();
}

session_destroy();

header("Location: ../index.php");
exit;
