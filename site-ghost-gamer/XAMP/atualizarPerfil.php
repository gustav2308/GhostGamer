<?php
/**
 * Atualiza os dados do PRÓPRIO usuário logado: nome, nickname e foto
 * de perfil. Usado pelo modal "Editar perfil" em PAGINAS/perfil.php.
 */

session_start();
header("Content-Type: application/json; charset=utf-8");
require_once __DIR__ . "/conexao.php";

function responder(bool $ok, string $mensagem, array $extra = []): void
{
    echo json_encode(array_merge(["ok" => $ok, "mensagem" => $mensagem], $extra));
    exit;
}

if (!isset($_SESSION["id"])) {
    http_response_code(401);
    responder(false, "Você precisa estar logado.");
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    responder(false, "Método não permitido.");
}

$idCliente = (int) $_SESSION["id"];
$nome      = trim($_POST["cli_nome"] ?? "");
$nickname  = trim($_POST["nickname"] ?? "");

if ($nome === "" || mb_strlen($nome) < 2) {
    responder(false, "Informe um nome válido.");
}
if ($nickname === "" || mb_strlen($nickname) < 2) {
    responder(false, "Informe um nickname válido.");
}

$pdo = conectarBanco();
$caminhoBancoFoto = null; // só é definido se uma nova foto for enviada

// -------- Upload da nova foto (opcional) --------
if (!empty($_FILES["foto"]["name"] ?? "")) {
    $foto = $_FILES["foto"];

    if ($foto["error"] !== UPLOAD_ERR_OK) {
        responder(false, "Falha ao receber a imagem enviada.");
    }

    $tamanhoMaximo = 3 * 1024 * 1024; // 3MB
    if ($foto["size"] > $tamanhoMaximo) {
        responder(false, "A imagem deve ter no máximo 3MB.");
    }

    $tiposPermitidos = [
        "image/jpeg" => "jpg",
        "image/png"  => "png",
        "image/webp" => "webp",
    ];

    $mime = mime_content_type($foto["tmp_name"]);
    if (!isset($tiposPermitidos[$mime])) {
        responder(false, "Envie uma imagem em JPG, PNG ou WEBP.");
    }

    $extensao = $tiposPermitidos[$mime];
    $nomeArquivo = "cliente_{$idCliente}_" . uniqid() . "." . $extensao;
    $pastaDestino = dirname(__DIR__) . "/ASSETS/IMG/perfil/";

    if (!is_dir($pastaDestino)) {
        mkdir($pastaDestino, 0755, true);
    }

    if (!move_uploaded_file($foto["tmp_name"], $pastaDestino . $nomeArquivo)) {
        responder(false, "Não foi possível salvar a imagem no servidor.");
    }

    // Remove a foto antiga (se for uma foto de perfil enviada anteriormente)
    $stmtAntiga = $pdo->prepare("SELECT foto FROM clientes WHERE id_cliente = :id");
    $stmtAntiga->execute(["id" => $idCliente]);
    $fotoAntiga = $stmtAntiga->fetchColumn();
    if ($fotoAntiga && str_contains($fotoAntiga, "ASSETS/IMG/perfil/")) {
        $caminhoAntigo = dirname(__DIR__) . "/" . $fotoAntiga;
        if (is_file($caminhoAntigo)) {
            @unlink($caminhoAntigo);
        }
    }

    $caminhoBancoFoto = "ASSETS/IMG/perfil/" . $nomeArquivo;
}

// -------- Verifica se o nickname já pertence a outra pessoa --------
$verificaNick = $pdo->prepare("SELECT id_cliente FROM clientes WHERE nickname = :nickname AND id_cliente <> :id LIMIT 1");
$verificaNick->execute(["nickname" => $nickname, "id" => $idCliente]);
if ($verificaNick->fetch()) {
    responder(false, "Esse nickname já está em uso.");
}

// -------- Atualiza no banco --------
if ($caminhoBancoFoto !== null) {
    $atualiza = $pdo->prepare(
        "UPDATE clientes SET cli_nome = :nome, nickname = :nickname, foto = :foto WHERE id_cliente = :id"
    );
    $atualiza->execute([
        "nome"     => $nome,
        "nickname" => $nickname,
        "foto"     => $caminhoBancoFoto,
        "id"       => $idCliente,
    ]);
    $_SESSION["foto"] = $caminhoBancoFoto;
} else {
    $atualiza = $pdo->prepare(
        "UPDATE clientes SET cli_nome = :nome, nickname = :nickname WHERE id_cliente = :id"
    );
    $atualiza->execute([
        "nome"     => $nome,
        "nickname" => $nickname,
        "id"       => $idCliente,
    ]);
}

$_SESSION["nome"]     = $nome;
$_SESSION["nickname"] = $nickname;

responder(true, "Perfil atualizado com sucesso!", [
    "nome"     => $nome,
    "nickname" => $nickname,
    "foto"     => $_SESSION["foto"] ?? null,
]);
