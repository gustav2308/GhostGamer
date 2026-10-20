<?php
/**
 * Passo 1 da recuperação de senha: gera um código de 6 dígitos, salva
 * no banco (válido por 15 min) e envia por e-mail via PHPMailer,
 * usando a mesma configuração central do Suporte (config_email.php).
 *
 * IMPORTANTE: a versão anterior deste arquivo tinha usuário e senha de
 * um e-mail do Gmail escritos direto no código-fonte. Isso é uma falha
 * grave de segurança -- qualquer pessoa com acesso ao código (ou a um
 * repositório Git) conseguia enviar e-mails por aquela conta. Revogue
 * essa senha de app em https://myaccount.google.com/apppasswords o
 * quanto antes e gere uma nova, configurada apenas em config_email.php
 * (que não deve ir para um repositório público).
 */

session_start();
header("Content-Type: application/json; charset=utf-8");
require_once __DIR__ . "/conexao.php";

function responder(bool $ok, string $mensagem): void
{
    echo json_encode(["ok" => $ok, "mensagem" => $mensagem]);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    responder(false, "Método não permitido.");
}

$email = trim($_POST["email"] ?? "");
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    responder(false, "Informe um e-mail válido.");
}

$pdo = conectarBanco();
$stmt = $pdo->prepare("SELECT id_cliente, cli_nome, email FROM clientes WHERE email = :email");
$stmt->execute(["email" => $email]);
$usuario = $stmt->fetch();

// Não revela se o e-mail existe ou não (evita que alguém use este
// formulário para descobrir quais e-mails têm conta no site).
if (!$usuario) {
    responder(true, "Se este e-mail estiver cadastrado, um código foi enviado.");
}

$codigo = (string) random_int(100000, 999999);
$expira = date("Y-m-d H:i:s", time() + 15 * 60);

$atualiza = $pdo->prepare(
    "UPDATE clientes SET token_recuperacao = :codigo, token_expira = :expira WHERE id_cliente = :id"
);
$atualiza->execute(["codigo" => $codigo, "expira" => $expira, "id" => $usuario["id_cliente"]]);

require_once __DIR__ . "/../vendor/autoload.php";
$config = require __DIR__ . "/config_email.php";

try {
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host       = $config["smtp_host"];
    $mail->Port       = $config["smtp_porta"];
    $mail->SMTPSecure = $config["smtp_seguranca"];
    $mail->SMTPAuth   = true;
    $mail->Username   = $config["smtp_usuario"];
    $mail->Password   = $config["smtp_senha"];
    $mail->CharSet    = "UTF-8";

    $mail->setFrom($config["remetente_email"], $config["remetente_nome"]);
    $mail->addAddress($usuario["email"], $usuario["cli_nome"]);

    $mail->isHTML(true);
    $mail->Subject = "Ghost Gamer - Código de recuperação de senha";
    $mail->Body = "
        <h2>Ghost Gamer</h2>
        <p>Olá, " . htmlspecialchars($usuario["cli_nome"]) . "!</p>
        <p>Recebemos uma solicitação para redefinir sua senha. Seu código é:</p>
        <h1 style='letter-spacing:4px'>$codigo</h1>
        <p>Este código é válido por 15 minutos. Se você não solicitou isso, ignore este e-mail.</p>
    ";

    $mail->send();

    $_SESSION["email_recuperacao"] = $usuario["email"];
    responder(true, "Código enviado! Verifique seu e-mail.");
} catch (Exception $e) {
    error_log("Falha ao enviar e-mail de recuperação: " . $mail->ErrorInfo);
    responder(false, "Não foi possível enviar o e-mail agora. Tente novamente mais tarde.");
}
