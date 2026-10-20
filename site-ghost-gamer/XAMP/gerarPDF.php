<?php
/**
 * Gera relatórios em PDF (Empresas / Clientes / Jogos) usando mPDF.
 * Reescrito com PDO (prepared statements) -- a versão anterior montava
 * SQL concatenando texto do usuário direto na query (SQL Injection) e
 * imprimia os valores no HTML sem escapar (o PDF conseguia ser
 * "quebrado" só de cadastrar um cliente com "<" no nome, por exemplo).
 */

require_once __DIR__ . "/../PAGINAS/parciais/bootstrap.php";

if (!$logado || $tipo !== "admin") {
    http_response_code(403);
    die("Acesso restrito a administradores.");
}

require_once __DIR__ . "/../vendor/autoload.php";

$pdo = conectarBanco();

$pesquisa      = trim($_POST["pesquisa"] ?? "");
$cidade        = trim($_POST["cidade"] ?? "");
$estado        = trim($_POST["estado"] ?? "");
$pais          = trim($_POST["pais"] ?? "");
$administrador = trim($_POST["administrador"] ?? "");
$genero        = trim($_POST["genero"] ?? "");

function estiloRelatorio(): string
{
    return "<style>
        body { font-family: Arial; }
        h1 { text-align: center; font-size: 20px; }
        p { text-align: center; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; font-size: 9px; }
        th { background-color: #333333; color: white; padding: 5px; }
        td { border: 1px solid #000000; padding: 4px; }
    </style>";
}

function linhaFiltros(array $filtros): string
{
    $partes = [];
    foreach ($filtros as $rotulo => $valor) {
        if ($valor !== "") {
            $partes[] = "$rotulo: <b>" . htmlspecialchars($valor) . "</b>";
        }
    }
    return $partes ? "<p>" . implode("<br>", $partes) . "</p>" : "";
}

$html = "";

// ---------- EMPRESAS ----------
if (!empty($_POST["acao1"])) {
    $sql = "SELECT * FROM empresa WHERE 1=1";
    $params = [];

    if ($pesquisa !== "") {
    $sql .= " AND (razao_social LIKE :b1 OR nome_fantasia LIKE :b2 OR CNPJ LIKE :b3 OR email LIKE :b4 OR cidade LIKE :b5)";
    foreach (['b1', 'b2', 'b3', 'b4', 'b5'] as $k) {
        $params[$k] = "%$pesquisa%";
    }
}
    if ($cidade !== "") { $sql .= " AND cidade = :cidade"; $params["cidade"] = $cidade; }
    if ($estado !== "") { $sql .= " AND estado = :estado"; $params["estado"] = $estado; }
    if ($pais !== "")   { $sql .= " AND pais = :pais";     $params["pais"] = $pais; }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $linhas = $stmt->fetchAll();

    $html .= estiloRelatorio() . "<h1>Relatório de Empresas</h1>";
    $html .= linhaFiltros(["Pesquisa" => $pesquisa, "Cidade" => $cidade, "Estado" => $estado, "País" => $pais]);
    $html .= "<table><tr><th>Código</th><th>Razão Social</th><th>Nome Fantasia</th><th>CNPJ</th>
              <th>Data de Abertura</th><th>Telefone</th><th>Email</th><th>Rua</th><th>Número</th>
              <th>Bairro</th><th>Cidade</th><th>Estado</th><th>CEP</th><th>País</th></tr>";

    if ($linhas) {
        foreach ($linhas as $l) {
            $html .= "<tr>" .
                "<td>" . (int) $l['id_empresa'] . "</td>" .
                "<td>" . htmlspecialchars($l['razao_social']) . "</td>" .
                "<td>" . htmlspecialchars($l['nome_fantasia'] ?? '') . "</td>" .
                "<td>" . htmlspecialchars($l['CNPJ'] ?? '') . "</td>" .
                "<td>" . htmlspecialchars($l['data_abertura'] ?? '') . "</td>" .
                "<td>" . htmlspecialchars($l['telefone'] ?? '') . "</td>" .
                "<td>" . htmlspecialchars($l['email'] ?? '') . "</td>" .
                "<td>" . htmlspecialchars($l['Rua'] ?? '') . "</td>" .
                "<td>" . htmlspecialchars($l['numero'] ?? '') . "</td>" .
                "<td>" . htmlspecialchars($l['bairro'] ?? '') . "</td>" .
                "<td>" . htmlspecialchars($l['cidade'] ?? '') . "</td>" .
                "<td>" . htmlspecialchars($l['estado'] ?? '') . "</td>" .
                "<td>" . htmlspecialchars($l['cep'] ?? '') . "</td>" .
                "<td>" . htmlspecialchars($l['pais'] ?? '') . "</td>" .
            "</tr>";
        }
    } else {
        $html .= "<tr><td colspan='14' style='text-align:center'>Nenhum resultado encontrado.</td></tr>";
    }
    $html .= "</table>";
}

// ---------- CLIENTES ----------
if (!empty($_POST["acao2"])) {
    $sql = "SELECT * FROM clientes WHERE 1=1";
    $params = [];

    if ($pesquisa !== "") {
    $sql .= " AND (cli_nome LIKE :b1 OR email LIKE :b2 OR nickname LIKE :b3)";
    foreach (['b1', 'b2', 'b3'] as $k) {
        $params[$k] = "%$pesquisa%";
    }
}
    if ($administrador !== "") { $sql .= " AND administrador = :adm"; $params["adm"] = $administrador; }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $linhas = $stmt->fetchAll();

    $html .= estiloRelatorio() . "<h1>Relatório de Clientes</h1>";
    $html .= linhaFiltros(["Pesquisa" => $pesquisa, "Tipo de usuário" => $administrador]);
    $html .= "<table><tr><th>Código</th><th>Nome</th><th>Email</th><th>Nickname</th><th>Administrador</th></tr>";

    if ($linhas) {
        foreach ($linhas as $l) {
            $html .= "<tr>" .
                "<td>" . (int) $l['id_cliente'] . "</td>" .
                "<td>" . htmlspecialchars($l['cli_nome']) . "</td>" .
                "<td>" . htmlspecialchars($l['email']) . "</td>" .
                "<td>" . htmlspecialchars($l['nickname'] ?? '') . "</td>" .
                "<td>" . htmlspecialchars($l['administrador']) . "</td>" .
            "</tr>";
        }
    } else {
        $html .= "<tr><td colspan='5' style='text-align:center'>Nenhum resultado encontrado.</td></tr>";
    }
    $html .= "</table>";
}

// ---------- JOGOS ----------
if (!empty($_POST["acao3"])) {
    $sql = "SELECT * FROM jogo WHERE 1=1";
    $params = [];

   if ($pesquisa !== "") {
    $sql .= " AND (titulo LIKE :b1 OR empresa_email LIKE :b2 OR genero LIKE :b3)";
    foreach (['b1', 'b2', 'b3'] as $k) {
        $params[$k] = "%$pesquisa%";
    }
}
    if ($genero !== "") { $sql .= " AND genero = :genero"; $params["genero"] = $genero; }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $linhas = $stmt->fetchAll();

    $html .= estiloRelatorio() . "<h1>Relatório de Jogos</h1>";
    $html .= linhaFiltros(["Pesquisa" => $pesquisa, "Gênero" => $genero]);
    $html .= "<table><tr><th>Código</th><th>Título</th><th>Email Empresarial</th><th>Gênero</th>
              <th>RAM (GB)</th><th>VRAM (GB)</th><th>Armazenamento</th></tr>";

    if ($linhas) {
        foreach ($linhas as $l) {
            $html .= "<tr>" .
                "<td>" . (int) $l['id_jogo'] . "</td>" .
                "<td>" . htmlspecialchars($l['titulo']) . "</td>" .
                "<td>" . htmlspecialchars($l['empresa_email'] ?? '') . "</td>" .
                "<td>" . htmlspecialchars($l['genero'] ?? '') . "</td>" .
                "<td>" . (int) $l['ram_gb'] . "</td>" .
                "<td>" . (int) $l['vram_gb'] . "</td>" .
                "<td>" . (int) $l['armazenamento'] . "</td>" .
            "</tr>";
        }
    } else {
        $html .= "<tr><td colspan='7' style='text-align:center'>Nenhum resultado encontrado.</td></tr>";
    }
    $html .= "</table>";
}

if ($html === "") {
    die("Nenhum relatório selecionado.");
}

$mpdf = new \Mpdf\Mpdf([
    'mode'   => 'utf-8',
    'format' => 'A4-L',
]);
$mpdf->SetTitle('Relatório Ghost Gamer');
$mpdf->SetAuthor('Ghost Gamer');
$mpdf->WriteHTML($html);
$mpdf->Output('relatorio_ghost_gamer.pdf', 'I');
