<?php
/**
 * Inclua este arquivo no topo de qualquer página que precise saber
 * se existe um usuário logado (para o menu, o Perfil, o Suporte etc).
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . "/../../XAMP/conexao.php";
require_once __DIR__ . "/icones.php";

/**
 * Se não há sessão ativa mas existe o cookie "lembrar_usuario" (criado no
 * login quando o cliente marca "Lembrar-me neste dispositivo"), tenta
 * autenticar automaticamente -- sem pedir e-mail/senha de novo.
 *
 * O cookie guarda "id:token"; só o hash do token fica no banco (ver
 * gerarCookieLembrarMe em XAMP/conexao.php). A cada auto-login o token é
 * trocado, então um cookie antigo copiado por alguém para de funcionar
 * assim que o dono de verdade acessa o site novamente.
 */
function tentarAutoLogin(PDO $pdo): void
{
    if (isset($_SESSION["id"]) || empty($_COOKIE["lembrar_usuario"])) {
        return;
    }

    $partes = explode(":", $_COOKIE["lembrar_usuario"], 2);
    if (count($partes) !== 2 || !ctype_digit($partes[0]) || $partes[1] === "") {
        limparCookieLembrarMe();
        return;
    }
    [$idCookie, $tokenCookie] = $partes;

    $stmt = $pdo->prepare(
        "SELECT id_cliente, cli_nome, email, nickname, foto, administrador, token_lembrar, token_lembrar_expira
           FROM clientes
          WHERE id_cliente = :id
          LIMIT 1"
    );
    $stmt->execute(["id" => $idCookie]);
    $usuario = $stmt->fetch();

    $valido = $usuario
        && !empty($usuario["token_lembrar"])
        && !empty($usuario["token_lembrar_expira"])
        && strtotime($usuario["token_lembrar_expira"]) > time()
        && hash_equals($usuario["token_lembrar"], hash("sha256", $tokenCookie));

    if (!$valido) {
        // Cookie inválido, expirado, ou de um token já trocado -> descarta.
        limparCookieLembrarMe($pdo, $usuario["id_cliente"] ?? null);
        return;
    }

    session_regenerate_id(true);
    $_SESSION["id"]            = $usuario["id_cliente"];
    $_SESSION["nome"]          = $usuario["cli_nome"];
    $_SESSION["email"]         = $usuario["email"];
    $_SESSION["nickname"]      = $usuario["nickname"];
    $_SESSION["foto"]          = $usuario["foto"];
    $_SESSION["administrador"] = $usuario["administrador"];

    gerarCookieLembrarMe($pdo, (int) $usuario["id_cliente"]);
}

tentarAutoLogin(conectarBanco());

$logado  = isset($_SESSION["id"]);
$tipo    = $_SESSION["administrador"] ?? "usuario";

$usuarioLogado = null;

if ($logado) {
    $usuarioLogado = [
        "id"       => $_SESSION["id"],
        "nome"     => $_SESSION["nome"] ?? "",
        "nickname" => $_SESSION["nickname"] ?? "",
        "foto"     => $_SESSION["foto"] ?? null,
        "email"    => $_SESSION["email"] ?? "",
    ];
}

/**
 * Gera a URL da foto de perfil do usuário, ou null se ele não tiver
 * uma foto customizada (nesse caso o front-end mostra um avatar com
 * as iniciais do nome).
 */
function urlFotoPerfil(?string $foto, string $base = ""): ?string
{
    if (empty($foto)) {
        return null;
    }
    return $base . $foto;
}

/**
 * Pega as iniciais de um nome para usar como avatar de fallback.
 */
function iniciaisNome(string $nome): string
{
    $nome = trim($nome);
    if ($nome === "") {
        return "?";
    }
    $partes = preg_split('/\s+/', $nome);
    $iniciais = mb_substr($partes[0], 0, 1);
    if (count($partes) > 1) {
        $iniciais .= mb_substr(end($partes), 0, 1);
    }
    return mb_strtoupper($iniciais);
}
