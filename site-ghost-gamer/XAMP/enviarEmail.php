
<?php

/**
 * Envia e-mails utilizando a API do Resend.
 * As configurações são obtidas das variáveis de ambiente do Railway.
 */

function enviarEmailResend($destinatario, $assunto, $html, $replyTo = null)
{
    $apiKey = getenv("RESEND_API_KEY");
    $emailRemetente = getenv("RESEND_FROM_EMAIL");
    $nomeRemetente = getenv("RESEND_FROM_NAME") ?: "Ghost Gamer";

    if (!$apiKey || !$emailRemetente) {
        error_log("Resend: variáveis de ambiente não configuradas.");
        return false;
    }

    $dados = [
        "from" => $nomeRemetente . " <" . $emailRemetente . ">",
        "to" => [$destinatario],
        "subject" => $assunto,
        "html" => $html
    ];

    if ($replyTo) {
        $dados["reply_to"] = $replyTo;
    }

    $opcoes = [
        "http" => [
            "method" => "POST",
            "header" =>
                "Authorization: Bearer " . $apiKey . "\r\n" .
                "Content-Type: application/json\r\n",
            "content" => json_encode($dados),
            "ignore_errors" => true,
            "timeout" => 20
        ]
    ];

    $contexto = stream_context_create($opcoes);

    $resposta = @file_get_contents(
        "https://api.resend.com/emails",
        false,
        $contexto
    );

    if ($resposta === false) {
        error_log("Resend: não foi possível conectar à API.");
        return false;
    }

    $status = $http_response_header[0] ?? "";

    if (strpos($status, "200") === false) {
        error_log("Resend: falha no envio. Resposta: " . $resposta);
        return false;
    }

    return true;
}
