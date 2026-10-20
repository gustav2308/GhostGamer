# Ghost Gamer — Guia de instalação e do que foi corrigido

## Requisitos
- **PHP 8.0 ou superior** (o código antigo usava `mysql_connect`, removido
  do PHP desde a versão 7 — por isso mais da metade do site simplesmente
  não rodava). Se usa XAMPP, baixe uma versão recente que já vem com PHP 8.x.
- Extensão `pdo_mysql` habilitada (padrão no XAMPP).
- Extensão `fileinfo` habilitada (valida a foto de perfil enviada).

## 1) Banco de dados
1. Importe o `XAMP/ghost_gamer (1).sql` normalmente.
2. Rode **`database/atualizacao_perfil.sql`** no phpMyAdmin — adiciona a
   coluna `foto` e as colunas `token_recuperacao`/`token_expira`
   (necessárias para a foto de perfil e a recuperação de senha).
3. Rode **`database/atualizacao_otimizacao.sql`** — cria índices para
   consultas mais rápidas, converte tabelas para InnoDB e adiciona
   chaves estrangeiras entre biblioteca/clientes/jogo.
4. Se o seu usuário do MySQL não for `root` sem senha, ajuste em
   `XAMP/conexao.php`.

## 2) E-mail (Suporte + Recuperação de senha)
Abra `XAMP/config_email.php` e preencha com um e-mail profissional:

```php
"smtp_host"       => "smtp.gmail.com",
"smtp_usuario"    => "seuemail@gmail.com",
"smtp_senha"      => "sua-senha-de-app-aqui",
"remetente_email" => "seuemail@gmail.com",
"suporte_destino" => "suporte@ghostgamer.com",
```

**Aviso de segurança**: o arquivo original de recuperação de senha
tinha um e-mail e senha do Gmail escritos direto no código-fonte
(`ghostsuporte143@gmail.com`). Revogue essa senha de app agora em
https://myaccount.google.com/apppasswords e gere uma nova — ela ficou
exposta em texto puro em todo o histórico do projeto.

Se usar Gmail, ative a verificação em 2 etapas e gere uma "senha de
app" — a senha normal da conta não funciona para SMTP.

## 3) Robô de compatibilidade de hardware (opcional)
O site já tem um verificador de compatibilidade funcional em
`telaJogo.php` que **não depende de nada externo** — compara os dados
que o jogador digita com os requisitos já cadastrados no jogo.

Se você também subir o robô (pasta `robo-hardware/` desta entrega, um
serviço Python/PostgreSQL separado), configure em
`XAMP/config_hardware.php`:

```php
define("HARDWARE_PAINEL_URL", "http://localhost:8080");
```

Isso ativa um link extra de "Verificação avançada pelo robô oficial"
na página do jogo. Veja `robo-hardware/LEIA-ME.md` para subir o robô.

## O que foi corrigido (resumo completo)

**Bugs que quebravam o site por completo:**
- 17 arquivos usavam `mysql_*` (removido do PHP 7+): login, cadastro,
  **biblioteca, categoria, tela do jogo**, todo o painel admin e a
  recuperação de senha estavam completamente fora do ar.
- Erro de sintaxe SQL literal em `biblioteca.php` (faltava um espaço:
  `'$id'ORDER BY`).
- Link morto para `jogo.php` (o arquivo certo é `telaJogo.php`).
- Redirecionamento com letra errada (`telajogo.php`) — funciona no
  Windows/XAMPP, quebra em qualquer servidor Linux (case-sensitive).
- Cadastro de cliente gravava a senha **sem hash** — o login nunca
  reconheceria essas contas.
- Edição de cliente pelo admin **apagava a senha da pessoa sem
  querer** (o formulário de edição não tem campo de senha, mas o
  código antigo sempre regravava esse campo com valor vazio).
- Recuperação de senha usava duas colunas que não existiam no banco
  (`token_recuperacao`, `token_expira`).
- `telaJogo.php` não tratava jogo inexistente (a página quebrava com
  um erro do PHP em vez de redirecionar).
- JS duplicava o elemento do menu lateral (dois `overlay` na mesma
  página).

**Falhas de segurança:**
- SQL Injection em `consulta.php`, `gerarPDF.php`, `alteraCliente.php`
  e outros (concatenação direta de `$_POST`/`$_GET` na query).
- XSS nos relatórios em PDF (dados exibidos sem escapar).
- `CadAdmin.php` permitia qualquer visitante — **sem estar logado** —
  criar uma conta de administrador.
- Cadastro de empresa e de jogo não verificava se quem enviava era
  administrador.
- Senha de e-mail do Gmail codificada direto no código-fonte (veja
  aviso acima).
- Exclusão (`exclui.php`) sem lista de tabelas permitidas — um
  parâmetro malicioso podia apagar registros de qualquer tabela.

**Otimização de performance:**
- Índices novos: `jogo.genero`, `jogo.empresa_email`,
  `clientes.nickname`, `biblioteca.data_adicionado`.
- Tabelas convertidas de MyISAM para InnoDB (evita travar a tabela
  inteira durante escrita, permite chave estrangeira).
- Chaves estrangeiras reais entre `biblioteca` → `clientes`/`jogo`.
- Conexão PDO com `ATTR_PERSISTENT` (reaproveita conexão entre
  requisições — login e consultas respondem mais rápido).
- Busca de jogos na biblioteca filtra no navegador, sem nova consulta
  ao banco a cada letra digitada.

**Funcionalidades novas/corrigidas:**
- Suporte por e-mail de verdade (PHPMailer + SMTP configurável).
- Perfil completo: login funcional, foto, edição de nome/nickname/
  foto, biblioteca real do usuário.
- "Adicionar à biblioteca" e recuperação de senha inteiros por AJAX
  (sem recarregar a página).
- Verificador de compatibilidade de hardware em `telaJogo.php`.
- Painel administrativo inteiro reescrito (consulta, edição e exclusão
  de clientes/empresas/jogos, geração de PDF).

**Visual:**
- Tema escuro profissional aplicado em **100% das páginas do site**
  (conferido arquivo por arquivo) — sem nenhum emoji, ícones próprios
  em SVG, cabeçalho e menu lateral consistentes em todo o site.

## O que ainda não foi feito
- Não existe sincronização automática entre a tabela `jogo` (MySQL, o
  site) e a tabela `aplicativos` (PostgreSQL, o robô) — hoje é preciso
  cadastrar o jogo nos dois lugares com o mesmo nome para a
  "verificação avançada" encontrar o jogo.
- O robô é um serviço à parte (Docker); ele não roda "dentro" do
  hosting PHP — precisa de um servidor próprio (mesmo que seja o
  mesmo servidor, mas outro processo).
