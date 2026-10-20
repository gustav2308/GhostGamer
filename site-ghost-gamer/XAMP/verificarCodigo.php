<?php
/** Passo 2 da recuperação de senha: confere o código de 6 dígitos. */

session_start();
header("Content-Type: application/json; charset=utf-8");
require_once __DIR__ . "/conexao.php";

function responder(bool $ok, string $mensagem): void
{
    echo json_encode(["ok" => $ok, "mensagem" => $mensagem]);
    exit;
}

if (!isset($_SESSION["email_recuperacao"])) {
    responder(false, "Sessão inválida. Peça um novo código.");
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    responder(false, "Método não permitido.");
}

$codigo = trim($_POST["codigo"] ?? "");

$pdo = conectarBanco();
$stmt = $pdo->prepare(
    "SELECT id_cliente FROM clientes
     WHERE email = :email AND token_recuperacao = :codigo AND token_expira >= NOW()"
);
$stmt->execute(["email" => $_SESSION["email_recuperacao"], "codigo" => $codigo]);

if (!$stmt->fetchColumn()) {
    responder(false, "Código incorreto ou expirado.");
}

$_SESSION["codigo_verificado"] = true;
responder(true, "Código confirmado!");
