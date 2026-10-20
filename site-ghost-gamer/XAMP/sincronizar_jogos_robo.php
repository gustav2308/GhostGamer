<?php

require_once __DIR__ . "/conexao.php";
require_once __DIR__ . "/config_hardware.php";

$pdo = conectarBanco();

if (HARDWARE_API_URL === "") {
    die("HARDWARE_API_URL não configurada.");
}

if (!function_exists("curl_init")) {
    die("A extensão cURL do PHP não está habilitada.");
}

$stmt = $pdo->query(
    "SELECT
        id_jogo,
        titulo,
        genero,
        empresa_email,
        nucleos,
        threads,
        frequencia,
        ram_gb,
        vram_gb,
        armazenamento
     FROM jogo
     ORDER BY id_jogo"
);

$jogos = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$jogos) {
    die("Nenhum jogo encontrado no banco de dados.");
}

$url = rtrim(HARDWARE_API_URL, "/") . "/apps/sincronizar";

$sucessos = 0;
$erros = 0;

foreach ($jogos as $jogo) {

    $dados = [
        "titulo" => $jogo["titulo"],
        "genero" => $jogo["genero"],
        "empresa_email" => $jogo["empresa_email"],
        "nucleos" => $jogo["nucleos"] !== null ? (int) $jogo["nucleos"] : null,
        "threads" => $jogo["threads"] !== null ? (int) $jogo["threads"] : null,
        "frequencia" => $jogo["frequencia"] !== null ? (float) $jogo["frequencia"] : null,
        "ram_gb" => $jogo["ram_gb"] !== null ? (int) $jogo["ram_gb"] : null,
        "vram_gb" => $jogo["vram_gb"] !== null ? (int) $jogo["vram_gb"] : null,
        "armazenamento" => $jogo["armazenamento"] !== null ? (int) $jogo["armazenamento"] : null
    ];

    $ch = curl_init($url);

    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            "Content-Type: application/json"
        ],
        CURLOPT_POSTFIELDS => json_encode($dados),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 3,
        CURLOPT_TIMEOUT => 10
    ]);

    $resposta = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $erroCurl = curl_error($ch);

    curl_close($ch);

    if ($erroCurl === "" && $httpCode >= 200 && $httpCode < 300) {
        $sucessos++;
        echo "OK - " . htmlspecialchars($jogo["titulo"]) . "<br>";
    } else {
        $erros++;
        echo "ERRO - " . htmlspecialchars($jogo["titulo"]);

        if ($erroCurl !== "") {
            echo " - " . htmlspecialchars($erroCurl);
        } else {
            echo " - HTTP " . (int) $httpCode;
        }

        echo "<br>";
    }

    flush();
}

echo "<hr>";
echo "Sincronização concluída.<br>";
echo "Sucessos: " . $sucessos . "<br>";
echo "Erros: " . $erros . "<br>";
echo "Total de jogos: " . count($jogos) . "<br>";