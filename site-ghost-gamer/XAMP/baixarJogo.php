<?php
/**
 * Envia o arquivo do jogo (.zip/.exe) para download e soma +1 no
 * contador de downloads. Os arquivos ficam em ASSETS/DOWNLOADS/, que
 * tem um .htaccess bloqueando acesso direto -- é só por aqui que dá
 * pra baixar, o que também é o que permite contar os downloads.
 */

require_once __DIR__ . "/conexao.php";

$idJogo = (int) ($_GET["id"] ?? 0);
if ($idJogo <= 0) {
    http_response_code(400);
    die("Jogo inválido.");
}

$pdo = conectarBanco();
$stmt = $pdo->prepare("SELECT titulo, arquivo_jogo, arquivo_nome_original FROM jogo WHERE id_jogo = :id");
$stmt->execute(["id" => $idJogo]);
$jogo = $stmt->fetch();

if (!$jogo || empty($jogo["arquivo_jogo"])) {
    http_response_code(404);
    die("Este jogo ainda não tem um arquivo para download.");
}

$caminhoArquivo = dirname(__DIR__) . "/" . $jogo["arquivo_jogo"];

// Garante que o caminho final continua dentro de ASSETS/DOWNLOADS/
// (defesa extra contra path traversal, mesmo o valor vindo do banco).
$pastaPermitida = realpath(dirname(__DIR__) . "/ASSETS/DOWNLOADS");
$caminhoReal = realpath($caminhoArquivo);

if ($caminhoReal === false || !str_starts_with($caminhoReal, $pastaPermitida)) {
    http_response_code(404);
    die("Arquivo não encontrado.");
}

$pdo->prepare("UPDATE jogo SET downloads = downloads + 1 WHERE id_jogo = :id")->execute(["id" => $idJogo]);

$nomeParaBaixar = $jogo["arquivo_nome_original"] ?: basename($caminhoReal);

// Downloads podem ser grandes (jogos) -- evita estourar tempo/memória.
set_time_limit(0);
while (ob_get_level() > 0) {
    ob_end_clean();
}

header("Content-Description: File Transfer");
header("Content-Type: application/octet-stream");
header('Content-Disposition: attachment; filename="' . str_replace('"', "", $nomeParaBaixar) . '"');
header("Content-Length: " . filesize($caminhoReal));
header("Cache-Control: must-revalidate");
header("Pragma: public");

readfile($caminhoReal);
exit;
