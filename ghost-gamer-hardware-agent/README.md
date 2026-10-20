# Ghost Gamer Hardware Agent

Programa pequeno que roda **no Windows do usuário** (fora do Docker) e expõe,
em `http://127.0.0.1:8765`, o hardware real do computador -- para que a
página do jogo no Ghost Gamer possa clicar em **"Verificar meu PC"** e obter
automaticamente CPU, GPU, RAM, armazenamento etc., sem o usuário precisar
digitar nada.

## Por que ele existe (e por que não roda no Docker)

O robô de hardware (`robo-hardware/`) roda dentro de contêineres Docker.
Um scanner executado *dentro* de um contêiner Linux enxerga o hardware
*virtualizado* do contêiner/WSL2 -- não a CPU, GPU e RAM físicas da máquina
Windows do usuário. Por isso o Agent é um programa separado, independente
do Docker, que roda diretamente no Windows do usuário.

## 1. Rodando em Python (desenvolvimento/teste)

Pré-requisito: Python 3.10+ instalado (https://www.python.org/downloads/).

```bat
cd ghost-gamer-hardware-agent
python agent.py
```

Você verá no console:

```
========================================================
 Ghost Gamer Hardware Agent
 Rodando em: http://127.0.0.1:8765
   - http://127.0.0.1:8765/health
   - http://127.0.0.1:8765/hardware
 Pressione Ctrl+C nesta janela para encerrar o Agent.
========================================================
```

### Verificar se está funcionando

Com o Agent rodando, abra em outra janela/terminal (ou no navegador):

```
http://127.0.0.1:8765/health
http://127.0.0.1:8765/hardware
```

`/health` deve responder algo como `{"status":"ok", ...}`.
`/hardware` deve responder um JSON com as specs do seu PC.

### Encerrar

Clique na janela do console e aperte `Ctrl+C`. Fechar a janela também encerra.

## 2. Gerando o executável (GhostGamerAgent.exe)

Para que quem for usar o Ghost Gamer **não precise instalar Python**, dá
para empacotar o Agent em um único `.exe` com o PyInstaller:

```bat
cd ghost-gamer-hardware-agent
build.bat
```

O `build.bat`:
1. confere se o Python está instalado;
2. instala o PyInstaller (`pip install pyinstaller`);
3. pergunta se você quer o `.exe` **com console** (recomendado enquanto
   estiver testando -- mostra logs/erros em uma janela preta) ou
   **sem console** (`--noconsole`, para a versão final que outras pessoas
   vão usar, já testada e funcionando);
4. roda `pyinstaller --onefile [--console|--noconsole] --name GhostGamerAgent agent.py`.

Só `agent.py` é passado para o PyInstaller -- ele enxerga e empacota
`hardware.py` sozinho, porque `agent.py` faz `import hardware`. Não rode o
PyInstaller em `hardware.py` separadamente, ele não é um programa
independente (não tem servidor, só funções de coleta).

O executável final fica em:

```
ghost-gamer-hardware-agent\dist\GhostGamerAgent.exe
```

Para usar: copie esse único arquivo `.exe` para onde quiser (ele não
depende de mais nada) e dê duplo clique. Uma janela de console abre e
mostra a mesma mensagem de "Rodando em http://127.0.0.1:8765".

Para encerrar o `.exe`: feche a janela do console, ou clique nela e
aperte `Ctrl+C` (se foi gerado com `--noconsole`, não existe janela --
encerre pelo Gerenciador de Tarefas, procurando por "GhostGamerAgent.exe").

### Onde colocar o .exe para o site enxergar

Copie `dist\GhostGamerAgent.exe` para:

```
site-ghost-gamer\ASSETS\AGENT\GhostGamerAgent.exe
```

O botão **"Baixar Ghost Gamer Agent"** em `PAGINAS/telaJogo.php` já aponta
para esse caminho (`../ASSETS/AGENT/GhostGamerAgent.exe`, com o atributo
`download`). Essa pasta não tem bloqueio de acesso direto (ao contrário de
`ASSETS/DOWNLOADS/`, usada para os arquivos dos jogos) -- é só colocar o
`.exe` lá que o link passa a funcionar.

## 3. Como o site (Ghost Gamer) detecta o Agent

Na página de cada jogo, a área "Verificação automática" tem dois botões:
**"Baixar Ghost Gamer Agent"** (link direto para o `.exe`, sempre visível)
e **"Verificar meu PC automaticamente"**, que dispara em JavaScript
(`ASSETS/JS/hardwareAgent.js`):

1. `GET http://127.0.0.1:8765/health` com um tempo limite curto.
   - Se falhar (Agent fechado/não instalado): mostra a mensagem
     *"O Ghost Gamer Agent não está em execução. Baixe o agente, execute
     o GhostGamerAgent.exe e tente novamente."*, sem quebrar o resto da
     página -- o verificador manual (já existente, com todos os campos
     de CPU/núcleos/threads/RAM/GPU/VRAM/armazenamento) continua
     funcionando normalmente.
2. Se o Agent responder, `GET http://127.0.0.1:8765/hardware` para pegar
   as specs reais.
   - Se algum dado obrigatório não puder ser lido (ex.: PowerShell
     indisponível), o site avisa exatamente quais dados faltaram, em vez
     de tentar enviar algo incompleto para a API.
3. Envia essas specs para a API de comparação (`HARDWARE_API_URL`,
   configurada em `XAMP/config_hardware.php` -- ver seção 5 abaixo),
   endpoint `POST /comparar`, junto com o nome do jogo.
4. Mostra o resultado (atende / não atende aos requisitos, e quais
   componentes ficaram abaixo do esperado).

## O que cada campo de `/hardware` significa (e suas limitações reais)

| Campo | Como é obtido | Limitação conhecida |
|---|---|---|
| `so`, `so_versao` | `Get-CimInstance Win32_OperatingSystem` | -- |
| `cpu_modelo`, `cpu_nucleos`, `cpu_threads` | `Get-CimInstance Win32_Processor` | -- |
| `cpu_clock_ghz` | `Win32_Processor.MaxClockSpeed` (frequência **máxima nominal**, não a instantânea) | Não reflete o clock real "boost" alcançado durante o jogo, só o valor nominal reportado pela placa-mãe/BIOS |
| `gpu_modelo` | `Get-CimInstance Win32_VideoController` | Em notebooks com vídeo integrado + dedicado, pode retornar o primeiro adaptador listado pelo Windows, nem sempre o dedicado |
| `gpu_vram_gb` | Registro do driver (`HardwareInformation.qwMemorySize`), com `Win32_VideoController.AdapterRAM` como alternativa | O campo `AdapterRAM` da WMI estoura (32 bits) em GPUs com 4 GB+; por isso o registro é tentado primeiro. Se nenhum dos dois estiver disponível, vem `0` |
| `ram_total_gb` | Soma de `Win32_PhysicalMemory.Capacity` | -- |
| `ram_velocidade_mhz`, `ram_tipo` | `Win32_PhysicalMemory` (primeiro pente) | Se os pentes forem de tipos/velocidades diferentes, só o primeiro é reportado |
| `storage_livre_gb`, `storage_total_gb` | `Win32_LogicalDisk` da unidade onde o Windows está instalado (`%SystemDrive%`, normalmente `C:`) | Não soma outras unidades/discos -- ver nota abaixo |
| `directx_versao` | Presença de `d3d12.dll`/`d3d11.dll` em `System32` | É uma estimativa do runtime disponível no Windows, não uma leitura direta de "versão do DirectX" (não existe uma API moderna confiável para isso -- a forma tradicional, `dxdiag /t`, é lenta e pensada para uso manual) |

Qualquer campo que não possa ser obtido com confiança volta como `null`
(nunca um valor inventado).

### Por que só a unidade do sistema (`storage_livre_gb`)

A API (`SpecsUsuario`, em `robo-hardware/05_api.py`) espera um único
número de espaço livre/total, não uma lista por disco. Como a maioria dos
jogos é instalada na unidade padrão do Windows, usamos essa unidade como
referência. Se o usuário costuma instalar jogos em outro disco, o
resultado da comparação de armazenamento pode não refletir isso --
isso é uma limitação do formato atual da API, não do Agent.

## Segurança

- O Agent escuta **somente em `127.0.0.1`** (nunca em `0.0.0.0`), então
  não é acessível por outros computadores da rede/Wi-Fi.
- Não existe nenhum endpoint que receba comandos do navegador e os
  execute -- os únicos dois endpoints (`/health`, `/hardware`) apenas leem
  informação; os comandos PowerShell usados são fixos no código-fonte.
- CORS libera `http://localhost` e `http://127.0.0.1` (qualquer porta --
  onde o Ghost Gamer roda local via XAMPP) e, opcionalmente, o(s)
  domínio(s) público(s) do site depois de hospedado (ver "Domínios
  permitidos" abaixo). Qualquer outra origem não recebe o cabeçalho de
  CORS, então um site diferente aberto no mesmo navegador não consegue
  ler as respostas do Agent.

### Domínios permitidos (quando o site estiver na internet)

Em desenvolvimento local, o navegador manda `Origin: http://localhost`,
que já é liberado por padrão. Quando o Ghost Gamer estiver hospedado (ex.:
`https://ghostgamer.com.br`), é **esse** endereço público que o navegador
vai mandar como `Origin` -- mesmo o Agent continuando em `127.0.0.1` no PC
de quem está jogando. Para liberar isso:

1. Copie `dominios_permitidos.txt.exemplo` para `dominios_permitidos.txt`
   (mesma pasta do `.exe`/`agent.py`).
2. Coloque o domínio real do site, um por linha:
   ```
   https://ghostgamer.com.br
   ```
3. Reinicie o Agent. O console mostra quais domínios foram carregados.

Nenhum domínio vem pré-configurado -- este projeto não inventa endereço
de hospedagem nenhum.

### Mixed Content e HTTPS (navegador bloqueando o Agent?)

Se o Ghost Gamer for servido em **HTTPS**, o navegador em geral bloqueia
uma página seguro de chamar um endereço **HTTP** comum ("Mixed Content").
Mas `http://localhost` e `http://127.0.0.1` são uma exceção reconhecida
pela especificação de "contextos seguros" do W3C -- todo navegador
moderno (Chrome, Edge, Firefox) trata esses dois endereços como
"potencialmente confiáveis" mesmo sendo HTTP puro, exatamente para
permitir esse padrão de "agente local ajudando um site HTTPS" (é o mesmo
mecanismo usado por apps como Spotify ou clientes de VPN com uma UI web).
Então, na prática, **o Agent não precisa de HTTPS/certificado** -- e não
seria nem prático gerar um certificado confiável para `127.0.0.1` em cada
PC de cada usuário.

O que PODE bloquear a chamada, separadamente do Mixed Content, é a
política mais nova dos navegadores chamada **Private Network Access
(PNA)**: quando uma página "pública" (ex.: `https://ghostgamer.com.br`)
tenta falar com um endereço "local" (`127.0.0.1`), o Chrome/Edge exigem
uma resposta extra de permissão (`Access-Control-Allow-Private-Network:
true`) antes da chamada de verdade. **O Agent já envia esse cabeçalho**
(em `agent.py`, função `_cabecalhos_cors`) quando a origem é permitida,
então isso já está tratado -- mas é importante saber que esse
comportamento do navegador existe, pode variar entre versões/navegadores,
e não é algo que o código do site ou do Agent consiga controlar por
completo (é uma decisão de segurança do navegador, não do servidor). Se
um navegador específico continuar bloqueando mesmo assim, a causa mais
provável é o domínio do site não estar em `dominios_permitidos.txt`.

## Limitações conhecidas / não testadas

- Todo este Agent foi desenvolvido e revisado em um ambiente **Linux, sem
  PowerShell nem hardware Windows real para testar**. O que foi validado
  neste ambiente: o servidor HTTP sobe, responde `/health` e `/hardware`
  corretamente, aplica CORS do jeito certo, e cada função de coleta
  degrada para `null`/`0` sem travar quando o PowerShell não está
  disponível (que é exatamente o que vai acontecer se algo mudar no
  Windows do usuário e uma consulta específica falhar).
- O que **não pôde ser testado** por falta de uma máquina Windows real
  neste ambiente: os comandos `Get-CimInstance` específicos (nomes exatos
  de propriedades podem variar ligeiramente entre versões do Windows),
  a leitura do registro de VRAM em GPUs AMD/Intel (o caminho de registro
  usado é o mais documentado publicamente, mas foi validado por terceiros
  majoritariamente em GPUs NVIDIA), e a geração/execução do `.exe` via
  PyInstaller em Windows de verdade.
- Recomendação: depois de colocar isso em um Windows real, rode
  `python hardware.py` primeiro (imprime o JSON coletado no terminal) para
  conferir os valores antes de depender do fluxo completo pelo navegador.
- **Novidades desta rodada** (CORS com domínio público, cabeçalho
  `Access-Control-Allow-Private-Network`, leitura de `dominios_permitidos.txt`,
  `build.bat` com escolha console/sem console): testadas via `curl` e um
  servidor real rodando neste ambiente Linux (confirmado: origem pública
  cadastrada libera CORS + PNA, origem não cadastrada continua bloqueada,
  arquivo `.exemplo` não é lido por engano). O que **não pôde ser
  testado**: o comportamento real de bloqueio/liberação em um Chrome/Edge
  de verdade contra um site HTTPS público de verdade (só pude simular as
  respostas HTTP do servidor, não o comportamento do navegador do lado
  cliente), e a geração do `.exe` em si (sem Windows nem PyInstaller
  disponíveis neste ambiente -- nem consegui instalar o PyInstaller aqui
  por falta de acesso à rede, então nem um build Linux de teste foi
  possível). Antes de depender disso em produção, teste manualmente em
  pelo menos Chrome e Firefox reais.
