<?php
/**
 * Autenticação do cliente. Recebe e-mail + senha via POST (AJAX) e
 * devolve JSON. Usa PDO com prepared statements (substitui o antigo
 * mysql_connect, que não existe mais no PHP moderno).
 */

session_start();
header("Content-Type: application/json; charset=utf-8");
require_once __DIR__ . "/conexao.php";

function responder(bool $ok, string $mensagem, array $extra = []): void
{
    echo json_encode(array_merge(["ok" => $ok, "mensagem" => $mensagem], $extra));
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    responder(false, "Método não permitido.");
}

$email = trim($_POST["email"] ?? "");
$senha = (string) ($_POST["senha"] ?? "");

if ($email === "" || $senha === "") {
    responder(false, "Informe e-mail e senha.");
}

$pdo = conectarBanco();

$stmt = $pdo->prepare(
    "SELECT id_cliente, cli_nome, email, nickname, foto, senha, administrador
     FROM clientes
     WHERE email = :email
     LIMIT 1"
);
$stmt->execute(["email" => $email]);
$usuario = $stmt->fetch();

if (!$usuario || empty($usuario["senha"]) || !password_verify($senha, $usuario["senha"])) {
    responder(false, "E-mail ou senha incorretos.");
}

session_regenerate_id(true);

$_SESSION["id"]            = $usuario["id_cliente"];
$_SESSION["nome"]          = $usuario["cli_nome"];
$_SESSION["email"]         = $usuario["email"];
$_SESSION["nickname"]      = $usuario["nickname"];
$_SESSION["foto"]          = $usuario["foto"];
$_SESSION["administrador"] = $usuario["administrador"];

// "Lembrar-me": só cria o cookie se a caixa foi marcada no formulário.
if (!empty($_POST["lembrar"])) {
    gerarCookieLembrarMe($pdo, (int) $usuario["id_cliente"]);
} else {
    // Login sem "lembrar-me": garante que não sobrou um cookie antigo.
    limparCookieLembrarMe($pdo, (int) $usuario["id_cliente"]);
}

responder(true, "Login realizado com sucesso!", ["redirecionar" => "../index.php"]);
