<?php
/** Passo 3 da recuperação de senha: grava a nova senha (com hash). */

session_start();
header("Content-Type: application/json; charset=utf-8");
require_once __DIR__ . "/conexao.php";

function responder(bool $ok, string $mensagem, array $extra = []): void
{
    echo json_encode(array_merge(["ok" => $ok, "mensagem" => $mensagem], $extra));
    exit;
}

if (!isset($_SESSION["codigo_verificado"]) || !isset($_SESSION["email_recuperacao"])) {
    responder(false, "Sessão inválida. Reinicie a recuperação de senha.");
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    responder(false, "Método não permitido.");
}

$senha = (string) ($_POST["senha"] ?? "");
$confirmar = (string) ($_POST["confirmar_senha"] ?? "");

if (mb_strlen($senha) < 6) {
    responder(false, "A senha precisa ter pelo menos 6 caracteres.");
}
if ($senha !== $confirmar) {
    responder(false, "As senhas não coincidem.");
}

$pdo = conectarBanco();
$stmt = $pdo->prepare(
    "UPDATE clientes SET senha = :senha, token_recuperacao = NULL, token_expira = NULL WHERE email = :email"
);
$stmt->execute([
    "senha" => password_hash($senha, PASSWORD_DEFAULT),
    "email" => $_SESSION["email_recuperacao"],
]);

unset($_SESSION["email_recuperacao"], $_SESSION["codigo_verificado"]);

responder(true, "Senha alterada com sucesso! Faça login com a nova senha.", ["redirecionar" => "loginCliente.php"]);
