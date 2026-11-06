
<?php
/**
 * Recebe o formulário de Suporte via AJAX
 * e envia a mensagem utilizando a API do Resend.
 */

session_start();
header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . "/enviarEmail.php";

function responder(bool $ok, string $mensagem, array $extra = []): void
{
    echo json_encode(
        array_merge(["ok" => $ok, "mensagem" => $mensagem], $extra),
        JSON_UNESCAPED_UNICODE
    );
    exit;
}

// Verifica o método da requisição
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    responder(false, "Método não permitido.");
}

// Honeypot: proteção contra robôs
if (!empty($_POST["site_web"] ?? "")) {
    responder(true, "Mensagem enviada com sucesso!");
}

// Recebe os dados do formulário
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

if (mb_strlen($mensagem) > 2000) {
    $erros[] = "A mensagem ultrapassou o limite de 2000 caracteres.";
}

if ($erros) {
    responder(false, implode(" ", $erros));
}

// Identifica o usuário logado
$idUsuario = $_SESSION["id"] ?? null;

// Monta o conteúdo do e-mail
$html = "
    <h2>Nova mensagem de suporte - Ghost Gamer</h2>

    <p><strong>Nome:</strong> " . htmlspecialchars($nome, ENT_QUOTES, "UTF-8") . "</p>

    <p><strong>E-mail para resposta:</strong> " . htmlspecialchars($email, ENT_QUOTES, "UTF-8") . "</p>

    <p><strong>Categoria:</strong> " . htmlspecialchars($categoria, ENT_QUOTES, "UTF-8") . "</p>

    <p><strong>Usuário logado (ID):</strong> " . htmlspecialchars((string) ($idUsuario ?? "não logado"), ENT_QUOTES, "UTF-8") . "</p>

    <hr>

    <p><strong>Mensagem:</strong></p>

    <p style='white-space:pre-line'>" . htmlspecialchars($mensagem, ENT_QUOTES, "UTF-8") . "</p>
";

// Endereço que receberá a mensagem
$destinatario = getenv("EMAIL_SUPORTE_DESTINO");

if (!$destinatario || !filter_var($destinatario, FILTER_VALIDATE_EMAIL)) {
    error_log("Ghost Gamer: endereço de suporte não configurado corretamente.");
    responder(false, "O serviço de suporte está temporariamente indisponível.");
}

// Envia pela API do Resend
$enviado = enviarEmailResend(
    $destinatario,
    "[Suporte Ghost Gamer] " . $assunto,
    $html,
    $email
);

if ($enviado) {
    responder(true, "Mensagem enviada com sucesso! Nossa equipe vai te responder em breve.");
}

error_log("Ghost Gamer: falha no envio da mensagem de suporte.");

responder(false, "Não foi possível enviar sua mensagem agora. Tente novamente mais tarde.");
