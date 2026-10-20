<?php
require_once __DIR__ . "/../PAGINAS/parciais/bootstrap.php";

if (!$logado || $tipo !== "admin") {
    header("Location: ../PAGINAS/loginCliente.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: consulta.php");
    exit;
}

$tabelasPermitidas = [
    "clientes" => ["coluna" => "id_cliente", "aba" => "clientes"],
    "empresa"  => ["coluna" => "id_empresa", "aba" => "empresas"],
    "jogo"     => ["coluna" => "id_jogo",    "aba" => "jogos"],
];

$tabela = $_POST["tabela"] ?? "";
$id     = (int) ($_POST["id"] ?? 0);

if (!isset($tabelasPermitidas[$tabela]) || $id <= 0) {
    header("Location: consulta.php");
    exit;
}

$info = $tabelasPermitidas[$tabela];

// Não deixa o admin excluir a própria conta por engano
if ($tabela === "clientes" && $id === (int) $_SESSION["id"]) {
    header("Location: consulta.php?aba=clientes&erro=auto_exclusao");
    exit;
}

$pdo = conectarBanco();
$stmt = $pdo->prepare("DELETE FROM `{$tabela}` WHERE `{$info['coluna']}` = :id");
$stmt->execute(["id" => $id]);

header("Location: consulta.php?aba={$info['aba']}&excluido=1");
