"""
Ghost Gamer Hardware Agent — servidor local (agent.py)
========================================================

Pequeno servidor HTTP que roda NO COMPUTADOR DO USUÁRIO (Windows), fora do
Docker, para resolver a limitação descrita na PARTE 5 do pedido: um
scanner rodando dentro de um contêiner Docker enxerga o Linux/WSL2 do
contêiner, não o hardware físico da máquina Windows que o usuário realmente
tem. Este Agent não depende de Docker, Python global instalado pelo
usuário (vira um .exe via PyInstaller -- ver build.bat) nem de rede externa.

Endpoints
---------
    GET /health     -> {"status": "ok", ...}            (ver PARTE 7)
    GET /hardware   -> specs reais do PC (ver hardware.py / PARTE 8)

Qualquer outra rota/método responde 404/405.

Segurança (PARTE 13 do pedido)
-------------------------------
- O servidor escuta SOMENTE em 127.0.0.1 (loopback) -- nunca em 0.0.0.0 --
  então não fica acessível por outros computadores da rede.
- Não existe NENHUM endpoint que receba e execute comandos vindos do
  navegador. As únicas ações possíveis são "ver /health" e "ler hardware".
  Os comandos PowerShell executados (hardware.py) são strings fixas
  escritas no código; nada do que chega por HTTP é usado para montar ou
  escolher um comando.
- CORS é restrito a origens em `localhost`/`127.0.0.1` (qualquer porta --
  onde o Ghost Gamer roda localmente via XAMPP) mais, opcionalmente, o(s)
  domínio(s) público(s) reais listados em `dominios_permitidos.txt` (ver
  seção logo abaixo), para quando o site estiver hospedado na internet.
  Nenhuma outra origem recebe o cabeçalho `Access-Control-Allow-Origin`,
  então um site qualquer não consegue ler a resposta deste Agent a partir
  do navegador da vítima.
"""

from __future__ import annotations

import json
import sys
from pathlib import Path
from http.server import BaseHTTPRequestHandler, ThreadingHTTPServer
from urllib.parse import urlparse

import hardware

HOST = "127.0.0.1"   # NUNCA 0.0.0.0 -- ver nota de segurança acima.
PORT = 8765

VERSAO = "1.0.0"

# ─────────────────────────────────────────────────────────────
#  Quais sites podem chamar este Agent (CORS)
# ─────────────────────────────────────────────────────────────
#
# Em desenvolvimento local (XAMPP), o navegador manda
# Origin: http://localhost (ou http://127.0.0.1) -- sempre liberado abaixo.
#
# Mas quando o Ghost Gamer estiver hospedado na internet (ex.:
# https://ghostgamer.com.br), é ESSA origem pública que o navegador vai
# mandar -- mesmo com o Agent continuando a rodar em 127.0.0.1 no PC do
# usuário. Por isso o domínio real do site, depois de hospedado, precisa
# ser adicionado na lista abaixo. Não inventamos nenhum domínio aqui: o
# arquivo abaixo começa vazio e cada instalação do Agent preenche com o
# endereço real do próprio site.
#
# Arquivo (opcional) ao lado do .exe (ou de agent.py, se rodando via
# `python agent.py`), um domínio por linha, por exemplo:
#     https://ghostgamer.com.br
#     https://www.ghostgamer.com.br
NOME_ARQUIVO_DOMINIOS = "dominios_permitidos.txt"


def _pasta_do_agent() -> Path:
    """Pasta onde o .exe (ou agent.py) está -- não a pasta temporária que
    o PyInstaller usa internamente (sys._MEIPASS) quando congelado."""
    if getattr(sys, "frozen", False):
        return Path(sys.executable).resolve().parent
    return Path(__file__).resolve().parent


def _carregar_dominios_adicionais() -> set[str]:
    caminho = _pasta_do_agent() / NOME_ARQUIVO_DOMINIOS
    if not caminho.exists():
        return set()
    dominios = set()
    for linha in caminho.read_text(encoding="utf-8", errors="ignore").splitlines():
        linha = linha.strip().rstrip("/")
        if linha and not linha.startswith("#"):
            dominios.add(linha)
    return dominios


DOMINIOS_ADICIONAIS_PERMITIDOS = _carregar_dominios_adicionais()


def _origem_permitida(origem: str | None) -> bool:
    """Libera CORS para http(s)://localhost ou http(s)://127.0.0.1 (em
    qualquer porta -- onde o Ghost Gamer roda localmente via XAMPP) e,
    além disso, para qualquer origem listada em dominios_permitidos.txt
    (o domínio real do site depois de hospedado)."""
    if not origem:
        return False
    try:
        partes = urlparse(origem)
    except ValueError:
        return False
    if partes.hostname in ("localhost", "127.0.0.1"):
        return True
    return origem.rstrip("/") in DOMINIOS_ADICIONAIS_PERMITIDOS


class AgentHandler(BaseHTTPRequestHandler):
    server_version = "GhostGamerAgent/" + VERSAO

    # Silencia o log padrão barulhento do http.server; mantemos só uma
    # linha curta por requisição (ajuda a ver no console que está vivo).
    def log_message(self, fmt: str, *args) -> None:
        sys.stdout.write("[Ghost Gamer Agent] " + (fmt % args) + "\n")

    def _cabecalhos_cors(self) -> None:
        origem = self.headers.get("Origin")
        if _origem_permitida(origem):
            self.send_header("Access-Control-Allow-Origin", origem)
            self.send_header("Vary", "Origin")
            # Chrome/Edge recentes classificam 127.0.0.1 como um endereço
            # "local" e exigem este cabeçalho extra (Private Network
            # Access) quando a página que está chamando veio de um
            # endereço "público" -- por exemplo, o Ghost Gamer hospedado
            # na internet com HTTPS chamando o Agent no PC do usuário.
            # Sem isso, o navegador bloqueia a chamada mesmo com o CORS
            # normal correto. Documentado no README do Agent.
            if self.headers.get("Access-Control-Request-Private-Network") == "true":
                self.send_header("Access-Control-Allow-Private-Network", "true")
        self.send_header("Access-Control-Allow-Methods", "GET, OPTIONS")
        self.send_header("Access-Control-Allow-Headers", "Content-Type")

    def _responder_json(self, status: int, corpo: dict) -> None:
        dados = json.dumps(corpo, ensure_ascii=False).encode("utf-8")
        self.send_response(status)
        self.send_header("Content-Type", "application/json; charset=utf-8")
        self.send_header("Content-Length", str(len(dados)))
        self._cabecalhos_cors()
        self.end_headers()
        self.wfile.write(dados)

    def do_OPTIONS(self) -> None:  # preflight CORS
        self.send_response(204)
        self._cabecalhos_cors()
        self.end_headers()

    def do_GET(self) -> None:
        caminho = urlparse(self.path).path

        if caminho == "/health":
            self._responder_json(200, {
                "status": "ok",
                "agente": "Ghost Gamer Hardware Agent",
                "versao": VERSAO,
            })
            return

        if caminho == "/hardware":
            try:
                specs = hardware.coletar_tudo()
                self._responder_json(200, specs)
            except Exception as erro:  # nunca deixa o Agent cair por causa de 1 coleta
                self._responder_json(500, {
                    "erro": "Falha ao coletar hardware.",
                    "detalhe": str(erro),
                })
            return

        self._responder_json(404, {"erro": "Rota não encontrada.", "rotas_disponiveis": ["/health", "/hardware"]})


def main() -> None:
    servidor = ThreadingHTTPServer((HOST, PORT), AgentHandler)
    print("=" * 56)
    print(" Ghost Gamer Hardware Agent")
    print(f" Rodando em: http://{HOST}:{PORT}")
    print(f"   - http://{HOST}:{PORT}/health")
    print(f"   - http://{HOST}:{PORT}/hardware")
    if DOMINIOS_ADICIONAIS_PERMITIDOS:
        print(" Domínios adicionais liberados (" + NOME_ARQUIVO_DOMINIOS + "):")
        for d in sorted(DOMINIOS_ADICIONAIS_PERMITIDOS):
            print("   - " + d)
    else:
        print(f" Nenhum domínio adicional em {NOME_ARQUIVO_DOMINIOS} "
              f"(só localhost/127.0.0.1 liberados por padrão).")
    print(" Pressione Ctrl+C nesta janela para encerrar o Agent.")
    print("=" * 56)
    try:
        servidor.serve_forever()
    except KeyboardInterrupt:
        print("\nEncerrando o Ghost Gamer Hardware Agent...")
    finally:
        servidor.server_close()


if __name__ == "__main__":
    main()
