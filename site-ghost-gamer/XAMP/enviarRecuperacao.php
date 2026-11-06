
<?php

session_start();

header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . "/conexao.php";
require_once __DIR__ . "/enviarEmail.php";

function responder(bool $ok, string $mensagem): void
{
    echo json_encode(
        ["ok" => $ok, "mensagem" => $mensagem],
        JSON_UNESCAPED_UNICODE
    );
    exit;
}

// Verifica o método da requisição
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    responder(false, "Método não permitido.");
}

// Recebe e valida o e-mail
$email = trim($_POST["email"] ?? "");

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    responder(false, "Informe um e-mail válido.");
}

// Consulta o usuário no banco
$pdo = conectarBanco();

$stmt = $pdo->prepare(
    "SELECT id_cliente, cli_nome, email
     FROM clientes
     WHERE email = :email"
);

$stmt->execute(["email" => $email]);

$usuario = $stmt->fetch();

// Não revela se o e-mail existe no cadastro
if (!$usuario) {
    responder(true, "Se este e-mail estiver cadastrado, um código foi enviado.");
}

// Gera código de recuperação de 6 dígitos
$codigo = (string) random_int(100000, 999999);

// Define validade de 15 minutos
$expira = date("Y-m-d H:i:s", time() + 15 * 60);

// Salva o código no banco
$atualiza = $pdo->prepare(
    "UPDATE clientes
     SET token_recuperacao = :codigo,
         token_expira = :expira
     WHERE id_cliente = :id"
);

$atualiza->execute([
    "codigo" => $codigo,
    "expira" => $expira,
    "id" => $usuario["id_cliente"]
]);

// Monta o conteúdo do e-mail
$nome = htmlspecialchars(
    $usuario["cli_nome"],
    ENT_QUOTES,
    "UTF-8"
);

$html = "
    <h2>Ghost Gamer</h2>

    <p>Olá, {$nome}!</p>

    <p>Recebemos uma solicitação para redefinir sua senha.
    Seu código de recuperação é:</p>

    <h1 style='letter-spacing:4px'>{$codigo}</h1>

    <p>Este código é válido por 15 minutos.</p>

    <p>Se você não solicitou essa recuperação, ignore este e-mail.</p>
";

// Envia o e-mail utilizando o Resend
$enviado = enviarEmailResend(
    $usuario["email"],
    "Ghost Gamer - Código de recuperação de senha",
    $html
);

if ($enviado) {

    $_SESSION["email_recuperacao"] = $usuario["email"];

    responder(true, "Código enviado! Verifique seu e-mail.");

} else {

    // Remove o código caso o envio falhe
    $remove = $pdo->prepare(
        "UPDATE clientes
         SET token_recuperacao = NULL,
             token_expira = NULL
         WHERE id_cliente = :id"
    );

    $remove->execute(["id" => $usuario["id_cliente"]]);

    error_log("Falha ao enviar código de recuperação pelo Resend.");

    responder(
        false,
        "Não foi possível enviar o e-mail agora. Tente novamente mais tarde."
    );
}
