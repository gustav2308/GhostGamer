<?php
/**
 * Recebe o formulário de Suporte (PAGINAS/suporte.php) via AJAX e
 * envia a mensagem por e-mail para o endereço profissional configurado
 * em config_email.php, usando o PHPMailer que já vem no /vendor.
 */

session_start();
header("Content-Type: application/json; charset=utf-8");

function responder(bool $ok, string $mensagem, array $extra = []): void
{
    echo json_encode(array_merge(["ok" => $ok, "mensagem" => $mensagem], $extra));
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    responder(false, "Método não permitido.");
}

// Honeypot: campo invisível que só um robô preencheria.
if (!empty($_POST["site_web"] ?? "")) {
    responder(true, "Mensagem enviada com sucesso!");
}

$nome      = trim($_POST["nome"] ?? "");
$email     = trim($_POST["email"] ?? "");
$assunto   = trim($_POST["assunto"] ?? "");
$categoria = trim($_POST["categoria"] ?? "Geral");
$mensagem  = trim($_POST["mensagem"] ?? "");

$erros = [];
if ($nome === "" || mb_strlen($nome) < 2) {
    $erros[] = "Informe seu nome.";
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $erros[] = "Informe um e-mail válido.";
}
if ($assunto === "") {
    $erros[] = "Informe o assunto.";
}
if (mb_strlen($mensagem) < 10) {
    $erros[] = "A mensagem precisa ter pelo menos 10 caracteres.";
}

if ($erros) {
    responder(false, implode(" ", $erros));
}

require_once __DIR__ . "/../vendor/autoload.php";
$config = require __DIR__ . "/config_email.php";

$idUsuario = $_SESSION["id"] ?? null;

$mail = new PHPMailer(true); // true = lança exceções em caso de falha (necessário para o try/catch abaixo)

try {
    $mail->isSMTP();
    $mail->Host       = $config["smtp_host"];
    $mail->Port       = $config["smtp_porta"];
    $mail->SMTPSecure = $config["smtp_seguranca"];
    $mail->SMTPAuth   = true;
    $mail->Username   = $config["smtp_usuario"];
    $mail->Password   = $config["smtp_senha"];
    $mail->CharSet    = "UTF-8";

    $mail->setFrom($config["remetente_email"], $config["remetente_nome"]);
    $mail->addAddress($config["suporte_destino"]);
    $mail->addReplyTo($email, $nome);

    $mail->isHTML(true);
    $mail->Subject = "[Suporte Ghost Gamer] " . $assunto;
    $mail->Body    = "
        <h2>Nova mensagem de suporte</h2>
        <p><strong>Nome:</strong> " . htmlspecialchars($nome) . "</p>
        <p><strong>E-mail para resposta:</strong> " . htmlspecialchars($email) . "</p>
        <p><strong>Categoria:</strong> " . htmlspecialchars($categoria) . "</p>
        <p><strong>Usuário logado (ID):</strong> " . htmlspecialchars((string) ($idUsuario ?? "não logado")) . "</p>
        <hr>
        <p style='white-space:pre-line'>" . htmlspecialchars($mensagem) . "</p>
    ";
    $mail->AltBody = "Nome: $nome\nE-mail: $email\nCategoria: $categoria\n\n$mensagem";

    $mail->send();

    responder(true, "Mensagem enviada com sucesso! Nossa equipe vai te responder em breve.");
} catch (Exception $e) {
    error_log("Falha ao enviar e-mail de suporte: " . $mail->ErrorInfo);
    responder(false, "Não foi possível enviar sua mensagem agora. Tente novamente mais tarde.");
}
