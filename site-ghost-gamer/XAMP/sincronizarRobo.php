<?php
require_once __DIR__ . "/config_hardware.php";
require_once __DIR__ . "/conexao.php";

function avisarRoboSincronizarJogo(int $idJogo): void
{
    if (HARDWARE_API_URL === "" || !function_exists("curl_init")) {
        return;
    }

    try {
        $pdo = conectarBanco();

        $stmt = $pdo->prepare(
            "SELECT
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
             WHERE id_jogo = :id
             LIMIT 1"
        );

        $stmt->execute(["id" => $idJogo]);
        $jogo = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$jogo) {
            return;
        }

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

        $ch = curl_init(
            rtrim(HARDWARE_API_URL, "/") . "/apps/sincronizar"
        );

        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                "Content-Type: application/json"
            ],
            CURLOPT_POSTFIELDS => json_encode($dados),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 2,
            CURLOPT_TIMEOUT => 5
        ]);

        @curl_exec($ch);
        curl_close($ch);

    } catch (Throwable $e) {
        // A sincronização não deve impedir o funcionamento do site.
    }
}