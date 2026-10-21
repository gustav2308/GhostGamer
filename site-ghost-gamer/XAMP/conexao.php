<?php
/**
 * Conexão central com o banco de dados (PDO).
 *
 * Substitui as antigas chamadas mysql_connect()/mysql_query(), que não
 * existem mais a partir do PHP 7. Todo o sistema (login, cadastro,
 * perfil e suporte) usa este arquivo para falar com o MySQL de forma
 * segura (prepared statements => sem SQL Injection).
 *
 * Ajuste as constantes abaixo conforme o seu ambiente (XAMPP local,
 * servidor de produção, etc).
 */

const DB_HOST = "mysql.railway.internal";
const DB_NOME = "ghost_gamer.sql";
const DB_USUARIO = "root";
const DB_SENHA = "ZEemhcQhdvYTRtOZXZyYUsJejVgcyFBg";
const DB_CHARSET = "utf8mb4";

function conectarBanco(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $porta = "3306";

    $dsn = "mysql:host=" . DB_HOST .
           ";port=" . $porta .
           ";dbname=" . DB_NOME .
           ";charset=" . DB_CHARSET;

    try {
        $pdo = new PDO($dsn, DB_USUARIO, DB_SENHA, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_PERSISTENT         => true,
        ]);
    } catch (PDOException $e) {
        error_log("Erro de conexão MySQL: " . $e->getMessage());
        http_response_code(500);
        die("Não foi possível conectar ao banco de dados.");
    }

    return $pdo;
}

/**
 * "Lembrar-me": cria (ou renova) um login automático por cookie.
 *
 * Adaptação segura da ideia de "lembrar_usuario" do protótipo de design:
 * lá o cookie guardava o id do cliente em texto puro (qualquer pessoa
 * podia forjar o cookie e entrar como outro usuário). Aqui o cookie
 * carrega um token aleatório de 32 bytes; só o HASH (sha256) desse
 * token fica salvo no banco, então nem um vazamento do banco de dados
 * expõe um token utilizável. O token é trocado a cada uso (login normal
 * ou auto-login), então um cookie antigo copiado por alguém deixa de
 * funcionar assim que o dono usa o site de novo.
 *
 * Requer as colunas `token_lembrar` / `token_lembrar_expira` em
 * `clientes` -- ver database/atualizacao_lembrar_me.sql.
 */
function gerarCookieLembrarMe(PDO $pdo, int $idCliente): void
{
    $tokenBruto = bin2hex(random_bytes(32));
    $duracao    = 60 * 60 * 24 * 30; // 30 dias

    $stmt = $pdo->prepare(
        "UPDATE clientes
            SET token_lembrar = :token, token_lembrar_expira = :expira
          WHERE id_cliente = :id"
    );
    $stmt->execute([
        "token"  => hash("sha256", $tokenBruto),
        "expira" => date("Y-m-d H:i:s", time() + $duracao),
        "id"     => $idCliente,
    ]);

    setcookie("lembrar_usuario", $idCliente . ":" . $tokenBruto, [
        "expires"  => time() + $duracao,
        "path"     => "/",
        "secure"   => !empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off",
        "httponly" => true,
        "samesite" => "Lax",
    ]);
}

/**
 * Apaga o "lembrar-me": remove o cookie do navegador e, se o cliente foi
 * informado, também invalida o token no banco (assim um cookie copiado
 * antes do logout para de funcionar).
 */
function limparCookieLembrarMe(?PDO $pdo = null, ?int $idCliente = null): void
{
    if ($pdo && $idCliente) {
        $stmt = $pdo->prepare(
            "UPDATE clientes SET token_lembrar = NULL, token_lembrar_expira = NULL WHERE id_cliente = :id"
        );
        $stmt->execute(["id" => $idCliente]);
    }

    setcookie("lembrar_usuario", "", [
        "expires"  => time() - 3600,
        "path"     => "/",
        "httponly" => true,
        "samesite" => "Lax",
    ]);
}
