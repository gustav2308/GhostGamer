<?php
/**
 * Grava cadastros de Cliente, Empresa ou Jogo (o mesmo endpoint atende
 * os 3 formulários, identificando pela página de origem).
 *
 * Reescrito com PDO (o antigo mysql_connect não existe mais a partir
 * do PHP 7) e com hash de senha (password_hash), que é obrigatório
 * para o login (password_verify) funcionar.
 */

require_once __DIR__ . "/sessao_segura.php";
iniciarSessaoSegura();
require_once __DIR__ . "/csrf.php";
require_once __DIR__ . "/conexao.php";
require_once __DIR__ . "/sincronizarRobo.php";

$paginaAnterior = $_SERVER['HTTP_REFERER'] ?? "../PAGINAS/cadastroCliente.php";
$ehAjax = ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest' || (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false);

function falhar(string $mensagem, bool $ehAjax, string $voltar): void
{
    if ($ehAjax) {
        header("Content-Type: application/json; charset=utf-8");
        echo json_encode(["ok" => false, "mensagem" => $mensagem]);
        exit;
    }

    header("Location: $voltar?erro=" . urlencode($mensagem));
    exit;
}

$pdo = conectarBanco();

// ---------- CADASTRO DE CLIENTE (usuário do launcher) ----------
if (str_contains($paginaAnterior, "cadastroCliente.php")) {

    $cli_nome = trim($_POST['cli_nome'] ?? "");
    $email    = trim($_POST['email'] ?? "");
    $nickname = trim($_POST['nickname'] ?? "");
    $senha    = (string) ($_POST['senha'] ?? "");

    if ($cli_nome === "" || $nickname === "") {
        falhar("Preencha nome e nickname.", $ehAjax, "../PAGINAS/cadastroCliente.php");
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        falhar("Informe um e-mail válido.", $ehAjax, "../PAGINAS/cadastroCliente.php");
    }

    if (mb_strlen($senha) < 6) {
        falhar("A senha precisa ter pelo menos 6 caracteres.", $ehAjax, "../PAGINAS/cadastroCliente.php");
    }

    $verifica = $pdo->prepare("SELECT id_cliente FROM clientes WHERE email = :email LIMIT 1");
    $verifica->execute(["email" => $email]);

    if ($verifica->fetch()) {
        falhar("Já existe uma conta com este e-mail.", $ehAjax, "../PAGINAS/cadastroCliente.php");
    }

    $senhaHash = password_hash($senha, PASSWORD_DEFAULT);

    $insere = $pdo->prepare(
        "INSERT INTO clientes (cli_nome, email, nickname, senha, administrador)
         VALUES (:nome, :email, :nickname, :senha, 'usuario')"
    );

    $insere->execute([
        "nome"     => $cli_nome,
        "email"    => $email,
        "nickname" => $nickname,
        "senha"    => $senhaHash,
    ]);

    // Loga o usuário automaticamente após o cadastro
    session_regenerate_id(true);

    $_SESSION["id"]            = $pdo->lastInsertId();
    $_SESSION["nome"]          = $cli_nome;
    $_SESSION["email"]         = $email;
    $_SESSION["nickname"]      = $nickname;
    $_SESSION["foto"]          = null;
    $_SESSION["administrador"] = "usuario";

    if ($ehAjax) {
        header("Content-Type: application/json; charset=utf-8");

        echo json_encode([
            "ok" => true,
            "mensagem" => "Conta criada com sucesso!",
            "redirecionar" => "../index.php"
        ]);

        exit;
    }

    header("Location: ../index.php");
    exit;
}

// ---------- CADASTRO DE EMPRESA (painel admin) ----------
if (str_contains($paginaAnterior, "cadastroEmpresa.php")) {

    if (($_SESSION["administrador"] ?? "") !== "admin") {
        falhar(
            "Apenas administradores podem cadastrar empresas.",
            $ehAjax,
            "../PAGINAS/loginCliente.php"
        );
    }

    $campos = [
        'razao_social',
        'nome_fantasia',
        'CNPJ',
        'data_abertura',
        'telefone',
        'email',
        'Rua',
        'numero',
        'bairro',
        'cidade',
        'estado',
        'cep',
        'pais'
    ];

    $valores = [];

    foreach ($campos as $campo) {
        $valores[$campo] = trim($_POST[$campo] ?? "");
    }

    $insere = $pdo->prepare(
        "INSERT INTO empresa
            (razao_social, nome_fantasia, CNPJ, data_abertura, telefone, email, Rua, numero, bairro, cidade, estado, cep, pais)
         VALUES
            (:razao_social, :nome_fantasia, :CNPJ, :data_abertura, :telefone, :email, :Rua, :numero, :bairro, :cidade, :estado, :cep, :pais)"
    );

    $insere->execute($valores);

    header("Location: $paginaAnterior");
    exit;
}

// ---------- CADASTRO DE JOGO (painel admin) ----------
if (str_contains($paginaAnterior, "cadastroJogos.php")) {

    if (($_SESSION["administrador"] ?? "") !== "admin") {
        falhar(
            "Apenas administradores podem cadastrar jogos.",
            $ehAjax,
            "../PAGINAS/loginCliente.php"
        );
    }

    $titulo        = trim($_POST['titulo'] ?? "");
    $empresa_email = trim($_POST['empresa_email'] ?? "");
    $genero        = trim($_POST['genero'] ?? "");
    $nucleos       = $_POST['nucleos'] ?? null;
    $threads       = $_POST['threads'] ?? null;
    $frequencia    = $_POST['frequencia'] ?? null;
    $ram_gb        = $_POST['ram_gb'] ?? null;
    $vram_gb       = $_POST['vram_gb'] ?? null;
    $armazenamento = $_POST['armazenamento'] ?? null;

    $caminhoBanco = null;

    // -------- Imagem do jogo --------
    if (!empty($_FILES["foto"]["name"] ?? "")) {

        $foto = $_FILES["foto"];

        $extensao = strtolower(
            pathinfo($foto["name"], PATHINFO_EXTENSION)
        );

        $extensoesPermitidas = [
            "jpg",
            "jpeg",
            "png",
            "webp"
        ];

        if (!in_array($extensao, $extensoesPermitidas, true)) {
            falhar(
                "Formato de imagem não permitido.",
                $ehAjax,
                "../PAGINAS/cadastroJogos.php"
            );
        }

        $nomeJogo = preg_replace(
            "/[^a-zA-Z0-9]/",
            "_",
            $titulo
        );

        $nomeFoto = $nomeJogo . "_" . uniqid() . "." . $extensao;

        $pastaImg = dirname(__DIR__) . "/ASSETS/IMG/";

        if (!is_dir($pastaImg)) {
            falhar(
                "A pasta ASSETS/IMG não foi encontrada.",
                $ehAjax,
                "../PAGINAS/cadastroJogos.php"
            );
        }

        if (!move_uploaded_file(
            $foto["tmp_name"],
            $pastaImg . $nomeFoto
        )) {
            falhar(
                "Não foi possível salvar a imagem.",
                $ehAjax,
                "../PAGINAS/cadastroJogos.php"
            );
        }

        $caminhoBanco = "ASSETS/IMG/" . $nomeFoto;
    }

    // -------- Arquivo do jogo (.zip ou .exe) para download --------

    $arquivoCaminhoBanco = null;
    $arquivoNomeOriginal = null;
    $arquivoTamanho      = null;

    $erroUpload = $_FILES["arquivo_jogo"]["error"] ?? UPLOAD_ERR_NO_FILE;

    if (
        $erroUpload === UPLOAD_ERR_INI_SIZE ||
        $erroUpload === UPLOAD_ERR_FORM_SIZE
    ) {
        falhar(
            "O arquivo é maior do que o limite configurado no servidor (upload_max_filesize = "
            . ini_get("upload_max_filesize")
            . ", post_max_size = "
            . ini_get("post_max_size")
            . "). Aumente esses valores no php.ini e reinicie o Apache -- veja o LEIA-ME.",
            $ehAjax,
            "../PAGINAS/cadastroJogos.php"
        );
    }

    if ($erroUpload !== UPLOAD_ERR_OK) {
        falhar(
            "Envie o arquivo do jogo (.zip ou .exe).",
            $ehAjax,
            "../PAGINAS/cadastroJogos.php"
        );
    }

    $arquivo = $_FILES["arquivo_jogo"];

    $extensaoArquivo = strtolower(
        pathinfo($arquivo["name"], PATHINFO_EXTENSION)
    );

    if (!in_array($extensaoArquivo, ["zip", "exe"], true)) {
        falhar(
            "O arquivo do jogo precisa ser .zip ou .exe.",
            $ehAjax,
            "../PAGINAS/cadastroJogos.php"
        );
    }

    $nomeJogoArquivo = preg_replace(
        "/[^a-zA-Z0-9]/",
        "_",
        $titulo
    );

    $nomeArquivoFinal =
        $nomeJogoArquivo . "_" . uniqid() . "." . $extensaoArquivo;

    $pastaDownloads =
        dirname(__DIR__) . "/ASSETS/DOWNLOADS/";

    if (!is_dir($pastaDownloads)) {
        mkdir($pastaDownloads, 0755, true);
    }

    if (!move_uploaded_file(
        $arquivo["tmp_name"],
        $pastaDownloads . $nomeArquivoFinal
    )) {
        falhar(
            "Não foi possível salvar o arquivo do jogo no servidor (verifique espaço em disco e permissões da pasta ASSETS/DOWNLOADS).",
            $ehAjax,
            "../PAGINAS/cadastroJogos.php"
        );
    }

    $arquivoCaminhoBanco =
        "ASSETS/DOWNLOADS/" . $nomeArquivoFinal;

    $arquivoNomeOriginal =
        $arquivo["name"];

    $arquivoTamanho =
        filesize($pastaDownloads . $nomeArquivoFinal);

    // -------- Salva o jogo no banco --------

    $insere = $pdo->prepare(
        "INSERT INTO jogo
            (
                titulo,
                empresa_email,
                genero,
                nucleos,
                threads,
                frequencia,
                ram_gb,
                vram_gb,
                armazenamento,
                foto,
                arquivo_jogo,
                arquivo_nome_original,
                arquivo_tamanho_bytes,
                downloads
            )
         VALUES
            (
                :titulo,
                :empresa_email,
                :genero,
                :nucleos,
                :threads,
                :frequencia,
                :ram_gb,
                :vram_gb,
                :armazenamento,
                :foto,
                :arquivo_jogo,
                :arquivo_nome_original,
                :arquivo_tamanho_bytes,
                0
            )"
    );

    $insere->execute([
        "titulo"                => $titulo,
        "empresa_email"         => $empresa_email,
        "genero"                => $genero,
        "nucleos"               => $nucleos,
        "threads"               => $threads,
        "frequencia"            => $frequencia,
        "ram_gb"                => $ram_gb,
        "vram_gb"               => $vram_gb,
        "armazenamento"         => $armazenamento,
        "foto"                  => $caminhoBanco,
        "arquivo_jogo"          => $arquivoCaminhoBanco,
        "arquivo_nome_original" => $arquivoNomeOriginal,
        "arquivo_tamanho_bytes" => $arquivoTamanho
    ]);

    $idNovoJogo = (int) $pdo->lastInsertId();
    avisarRoboSincronizarJogo($idNovoJogo);

    header("Location: $paginaAnterior");
    exit;
}

falhar(
    "Origem do formulário não reconhecida.",
    $ehAjax,
    "../index.php"
);