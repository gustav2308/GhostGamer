<?php

$caminhoArquivo = dirname(__DIR__) . "/ASSETS/DOWNLOADS/GhostGamer Launcher Setup 1.0.0.exe";

if (!is_file($caminhoArquivo) || !is_readable($caminhoArquivo)) {
    http_response_code(404);
    exit("Instalador indisponível.");
}

set_time_limit(0);

while (ob_get_level() > 0) {
    ob_end_clean();
}

$nomeArquivo = "GhostGamer Launcher Setup 1.0.0.exe";

header("Content-Description: File Transfer");
header("Content-Type: application/octet-stream");
header('Content-Disposition: attachment; filename="' . $nomeArquivo . '"');
header("Content-Length: " . filesize($caminhoArquivo));
header("Cache-Control: no-store");
header("X-Content-Type-Options: nosniff");

readfile($caminhoArquivo);
exit;