<?php
/**
 * Adiciona um jogo à biblioteca do usuário logado.
 * Responde em JSON (chamado via fetch/AJAX por telaJogo.php).
 */

session_start();
header("Content-Type: application/json; charset=utf-8");
require_once __DIR__ . "/conexao.php";

function responder(bool $ok, string $mensagem): void
{
    echo json_encode(["ok" => $ok, "mensagem" => $mensagem]);
    exit;
}

if (!isset($_SESSION["id"])) {
    http_response_code(401);
    responder(false, "Você precisa estar logado para adicionar um jogo.");
}

$idJogo = (int) ($_POST["id_jogo"] ?? 0);
if ($idJogo <= 0) {
    responder(false, "Jogo não informado.");
}

$idCliente = (int) $_SESSION["id"];
$pdo = conectarBanco();

// Confirma que o jogo existe antes de tentar inserir
$existeJogo = $pdo->prepare("SELECT 1 FROM jogo WHERE id_jogo = :id");
$existeJogo->execute(["id" => $idJogo]);
if (!$existeJogo->fetchColumn()) {
    responder(false, "Este jogo não existe mais.");
}

try {
    $insere = $pdo->prepare(
        "INSERT INTO biblioteca (id_cliente, id_jogo) VALUES (:id_cliente, :id_jogo)"
    );
    $insere->execute(["id_cliente" => $idCliente, "id_jogo" => $idJogo]);

    responder(true, "Jogo adicionado à sua biblioteca!");
} catch (PDOException $e) {
    // A tabela tem UNIQUE KEY (id_cliente, id_jogo) -- código 23000 = duplicado
    if ($e->getCode() === "23000") {
        responder(true, "Este jogo já estava na sua biblioteca.");
    }
    error_log("Erro ao adicionar à biblioteca: " . $e->getMessage());
    responder(false, "Não foi possível adicionar o jogo agora. Tente novamente.");
}
