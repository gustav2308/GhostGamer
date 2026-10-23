"""
=============================================================
 API REST — FastAPI
 Recebe specs do PC do usuário e retorna laudo de
 compatibilidade com os apps cadastrados no banco.
=============================================================

Instalação:
    pip install fastapi uvicorn psycopg2-binary python-dotenv pydantic

Rodando:
    uvicorn 05_api:app --reload --port 8000

Endpoints:
    GET  /                      → status da API
    GET  /apps                  → lista apps disponíveis
    GET  /cpus?fabricante=Intel → lista CPUs cadastradas
    POST /comparar              → compara specs com um app
    GET  /health                → health check do banco
"""

import os
import json
import logging
from typing import Optional
from datetime import datetime

import psycopg2
import psycopg2.extras
import psycopg2.pool
from dotenv import load_dotenv
from fastapi import FastAPI, HTTPException, Query
from fastapi.middleware.cors import CORSMiddleware
from pydantic import BaseModel, Field

load_dotenv()

for nome in ["DB_HOST", "DB_PORT", "DB_NAME", "DB_USER", "DB_PASSWORD"]:
    valor = os.getenv(nome)
    if nome == "DB_PASSWORD":
        print(f"[CONFIG] {nome}: {'CONFIGURADA' if valor else 'AUSENTE'}")
    else:
        print(f"[CONFIG] {nome}: {valor if valor else 'AUSENTE'}")
log = logging.getLogger("API")

# ─────────────────────────────────────────
#  App FastAPI
# ─────────────────────────────────────────
app = FastAPI(
    title="Hardware Compatibility API",
    description="Compara as specs do PC do usuário com requisitos de aplicativos.",
    version="1.0.0",
)

app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],     # ajuste para seu domínio em produção
    allow_methods=["*"],
    allow_headers=["*"],
)


# ─────────────────────────────────────────
#  Banco de dados
# ─────────────────────────────────────────
# Pool de conexões: evita abrir/fechar uma conexão TCP nova a cada
# requisição (o maior gargalo de performance de uma API que recebe
# muitas chamadas, como um launcher consultando compatibilidade).
_pool: "psycopg2.pool.ThreadedConnectionPool | None" = None



def _criar_pool() -> "psycopg2.pool.ThreadedConnectionPool":
    host = os.getenv("PGHOST") or os.getenv("DB_HOST")
    porta = os.getenv("PGPORT") or os.getenv("DB_PORT", "5432")
    banco = os.getenv("PGDATABASE") or os.getenv("DB_NAME")
    usuario = os.getenv("PGUSER") or os.getenv("DB_USER")
    senha = os.getenv("PGPASSWORD") or os.getenv("DB_PASSWORD")

    if not all([host, banco, usuario, senha]):
        raise RuntimeError(
            "Configuração do PostgreSQL incompleta. "
            "Verifique PGHOST, PGDATABASE, PGUSER e PGPASSWORD."
        )

    print(f"[DB] Host: {host}")
    print(f"[DB] Porta: {porta}")
    print(f"[DB] Banco: {banco}")
    print(f"[DB] Usuário: {usuario}")

    return psycopg2.pool.ThreadedConnectionPool(
        minconn=int(os.getenv("DB_POOL_MIN", "2")),
        maxconn=int(os.getenv("DB_POOL_MAX", "20")),
        host=host,
        port=int(porta),
        dbname=banco,
        user=usuario,
        password=senha,
        cursor_factory=psycopg2.extras.RealDictCursor,
        connect_timeout=10,
        sslmode="require",
    )



def get_conn():
    """Pega uma conexão emprestada do pool (chame put_conn() para devolver)."""
    global _pool
    if _pool is None:
        _pool = _criar_pool()
    return _pool.getconn()


def put_conn(conn) -> None:
    """Devolve a conexão ao pool em vez de fechar a conexão TCP."""
    if _pool is not None and conn is not None:
        _pool.putconn(conn)


@app.on_event("startup")
def _iniciar_pool() -> None:
    global _pool
    _pool = _criar_pool()
    log.info("Pool de conexões com o PostgreSQL iniciado.")


@app.on_event("shutdown")
def _encerrar_pool() -> None:
    global _pool
    if _pool is not None:
        _pool.closeall()
        log.info("Pool de conexões encerrado.")


# ═══════════════════════════════════════════════════════════════
#  SCHEMAS (Pydantic)
# ═══════════════════════════════════════════════════════════════

class SpecsUsuario(BaseModel):
    """Specs do PC enviadas pelo app do usuário."""
    # Sistema operacional
    so: str = Field(..., example="Windows")
    so_versao: str = Field(..., example="10.0.19045")

    # CPU
    cpu_modelo: str = Field(..., example="Intel Core i7-12700K")
    cpu_nucleos: int = Field(..., example=12)
    cpu_threads: int = Field(..., example=20)
    cpu_clock_ghz: float = Field(..., example=3.6)

    # GPU
    gpu_modelo: str = Field("", example="NVIDIA GeForce RTX 3070")
    gpu_vram_gb: int = Field(0, example=8)

    # RAM
    ram_total_gb: int = Field(..., example=16)
    ram_velocidade_mhz: Optional[int] = Field(None, example=3200)
    ram_tipo: Optional[str] = Field(None, example="DDR4")

    # Armazenamento
    storage_livre_gb: int = Field(..., example=120)
    storage_total_gb: int = Field(0, example=500)

    # DirectX
    directx_versao: Optional[str] = Field(None, example="12")


class ComparacaoRequest(BaseModel):
    specs: SpecsUsuario
    app_nome: str = Field(..., example="Cyberpunk 2077")
    salvar: bool = Field(True, description="Salvar resultado no banco")


class ComponenteScore(BaseModel):
    componente: str
    valor_usuario: str
    valor_minimo: str
    valor_recomendado: str
    score: int
    status: str
    observacao: Optional[str] = None


class ComparacaoResponse(BaseModel):
    app_nome: str
    nivel_geral: str
    score_geral: int
    score_cpu: int
    score_gpu: int
    score_ram: int
    score_storage: int
    componentes: list[ComponenteScore]
    resumo: str
    timestamp: str


# ═══════════════════════════════════════════════════════════════
#  LÓGICA DE COMPARAÇÃO (inline, sem importar o arquivo 03)
# ═══════════════════════════════════════════════════════════════

def _score(valor: float, minimo: float, recomendado: float):
    if not minimo:
        return 100, "ÓTIMO"
    if valor < minimo:
        return max(0, int((valor / minimo) * 50)), "INSUFICIENTE"
    if not recomendado or valor >= recomendado:
        return 100, "ÓTIMO"
    score = 50 + int(((valor - minimo) / (recomendado - minimo)) * 50)
    return min(score, 99), "MÍNIMO" if score < 75 else "RECOMENDADO"


def _match_cpu(cur, modelo: str):
    import re
    match = re.search(r"(i\d-\d{4,5}\w*|Ryzen\s\d\s\d{4}\w*)", modelo, re.IGNORECASE)
    termo = match.group(0) if match else modelo[:30]
    cur.execute(
        "SELECT id, modelo FROM cpus WHERE ativo=TRUE AND LOWER(modelo) LIKE LOWER(%s) LIMIT 1",
        (f"%{termo}%",)
    )
    return cur.fetchone()


def _match_gpu(cur, modelo: str):
    import re
    match = re.search(r"(RTX\s?\d{4}\s?\w*|GTX\s?\d{4}\s?\w*|RX\s?\d{4}\s?\w*)", modelo, re.IGNORECASE)
    termo = match.group(0) if match else modelo[:30]
    cur.execute(
        "SELECT id, modelo FROM gpus WHERE ativo=TRUE AND LOWER(modelo) LIKE LOWER(%s) LIMIT 1",
        (f"%{termo}%",)
    )
    return cur.fetchone()


def comparar_specs(specs: SpecsUsuario, app_nome: str, salvar: bool = True) -> dict:
    conn = get_conn()
    try:
        with conn.cursor() as cur:
            # Busca app
            cur.execute(
                "SELECT * FROM aplicativos WHERE LOWER(nome) LIKE LOWER(%s) AND ativo=TRUE LIMIT 1",
                (f"%{app_nome}%",)
            )
            app = cur.fetchone()
            if not app:
                raise HTTPException(status_code=404, detail=f"Aplicativo '{app_nome}' não encontrado.")

            cpu_match = _match_cpu(cur, specs.cpu_modelo)
            gpu_match = _match_gpu(cur, specs.gpu_modelo)

            # CPU
            s_cpu, st_cpu = _score(
                specs.cpu_nucleos,
                app["req_min_cpu_nucleos"] or 0,
                app["req_rec_cpu_nucleos"] or 0,
            )
            s_ghz, st_ghz = _score(
                specs.cpu_clock_ghz,
                float(app["req_min_cpu_ghz"] or 0),
                float(app["req_rec_cpu_ghz"] or 0),
            )
            score_cpu = (s_cpu + s_ghz) // 2
            status_cpu = "INSUFICIENTE" if "INSUFICIENTE" in (st_cpu, st_ghz) else st_ghz

            # GPU
            score_gpu, status_gpu = _score(
                specs.gpu_vram_gb,
                app["req_min_gpu_vram_gb"] or 0,
                app["req_rec_gpu_vram_gb"] or 0,
            )

            # RAM
            score_ram, status_ram = _score(
                specs.ram_total_gb,
                app["req_min_ram_gb"] or 0,
                app["req_rec_ram_gb"] or 0,
            )

            # Storage
            score_storage, status_storage = _score(
                specs.storage_livre_gb,
                app["req_min_storage_gb"] or 0,
                app["req_rec_storage_gb"] or 0,
            )

            # Score geral ponderado
            score_geral = (
                score_cpu     * 35 +
                score_gpu     * 40 +
                score_ram     * 15 +
                score_storage * 10
            ) // 100

            if score_geral < 40:
                nivel = "ABAIXO_MINIMO"
                resumo = f"Seu PC não atende aos requisitos mínimos de {app['nome']}."
            elif score_geral < 65:
                nivel = "MINIMO"
                resumo = f"Seu PC atende ao mínimo de {app['nome']}, mas com desempenho limitado."
            elif score_geral < 90:
                nivel = "RECOMENDADO"
                resumo = f"Seu PC atende aos requisitos recomendados de {app['nome']}."
            else:
                nivel = "ACIMA"
                resumo = f"Seu PC está acima dos requisitos recomendados. {app['nome']} vai rodar com excelência."

            componentes = [
                ComponenteScore(
                    componente="CPU",
                    valor_usuario=f"{specs.cpu_modelo} ({specs.cpu_nucleos} núcleos, {specs.cpu_clock_ghz} GHz)",
                    valor_minimo=f"{app['req_min_cpu_nucleos']} núcleos, {app['req_min_cpu_ghz']} GHz",
                    valor_recomendado=f"{app['req_rec_cpu_nucleos']} núcleos, {app['req_rec_cpu_ghz']} GHz",
                    score=score_cpu, status=status_cpu,
                    observacao=f"Match: {cpu_match['modelo'] if cpu_match else 'não identificado'}",
                ),
                ComponenteScore(
                    componente="GPU",
                    valor_usuario=f"{specs.gpu_modelo} ({specs.gpu_vram_gb} GB VRAM)",
                    valor_minimo=f"{app['req_min_gpu_vram_gb']} GB VRAM",
                    valor_recomendado=f"{app['req_rec_gpu_vram_gb']} GB VRAM",
                    score=score_gpu, status=status_gpu,
                    observacao=f"Match: {gpu_match['modelo'] if gpu_match else 'não identificado'}",
                ),
                ComponenteScore(
                    componente="RAM",
                    valor_usuario=f"{specs.ram_total_gb} GB",
                    valor_minimo=f"{app['req_min_ram_gb']} GB",
                    valor_recomendado=f"{app['req_rec_ram_gb']} GB",
                    score=score_ram, status=status_ram,
                ),
                ComponenteScore(
                    componente="Armazenamento",
                    valor_usuario=f"{specs.storage_livre_gb} GB livres",
                    valor_minimo=f"{app['req_min_storage_gb']} GB",
                    valor_recomendado=f"{app['req_rec_storage_gb']} GB",
                    score=score_storage, status=status_storage,
                ),
            ]

            # Salva no banco (opcional)
            if salvar:
                cur.execute("""
                    INSERT INTO scans_pc (
                        so, so_versao, cpu_modelo, cpu_nucleos, cpu_threads,
                        cpu_clock_ghz, cpu_id, gpu_modelo, gpu_vram_gb, gpu_id,
                        ram_total_gb, ram_velocidade_mhz, ram_tipo,
                        storage_livre_gb, storage_total_gb, directx_versao
                    ) VALUES (
                        %s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s
                    ) RETURNING id
                """, (
                    specs.so, specs.so_versao,
                    specs.cpu_modelo, specs.cpu_nucleos, specs.cpu_threads,
                    specs.cpu_clock_ghz, cpu_match["id"] if cpu_match else None,
                    specs.gpu_modelo, specs.gpu_vram_gb,
                    gpu_match["id"] if gpu_match else None,
                    specs.ram_total_gb, specs.ram_velocidade_mhz, specs.ram_tipo,
                    specs.storage_livre_gb, specs.storage_total_gb, specs.directx_versao,
                ))
                scan_id = cur.fetchone()["id"]

                detalhes = {c.componente: {"score": c.score, "status": c.status} for c in componentes}
                cur.execute("""
                    INSERT INTO comparacoes
                        (scan_id, app_id, nivel, score_cpu, score_gpu,
                         score_ram, score_storage, score_geral, detalhes)
                    VALUES (%s,%s,%s,%s,%s,%s,%s,%s,%s)
                """, (
                    scan_id, app["id"], nivel,
                    score_cpu, score_gpu, score_ram, score_storage, score_geral,
                    json.dumps(detalhes),
                ))
                conn.commit()

            return {
                "app_nome":    app["nome"],
                "nivel_geral": nivel,
                "score_geral": score_geral,
                "score_cpu":   score_cpu,
                "score_gpu":   score_gpu,
                "score_ram":   score_ram,
                "score_storage": score_storage,
                "componentes": [c.dict() for c in componentes],
                "resumo":      resumo,
                "timestamp":   datetime.now().isoformat(),
            }
    finally:
        put_conn(conn)


# ═══════════════════════════════════════════════════════════════
#  ENDPOINTS
# ═══════════════════════════════════════════════════════════════

@app.get("/", tags=["Status"])
def raiz():
    return {
        "status": "online",
        "versao": "1.0.0",
        "descricao": "Hardware Compatibility API",
        "timestamp": datetime.now().isoformat(),
    }


@app.get("/health", tags=["Status"])
def health_check():
    """Verifica conexão com o banco de dados."""
    try:
        conn = get_conn()
        with conn.cursor() as cur:
            cur.execute("SELECT COUNT(*) AS total FROM cpus WHERE ativo=TRUE")
            total_cpus = cur.fetchone()["total"]
            cur.execute("SELECT COUNT(*) AS total FROM gpus WHERE ativo=TRUE")
            total_gpus = cur.fetchone()["total"]
        put_conn(conn)
        return {
            "status": "ok",
            "banco": "conectado",
            "cpus_cadastradas": total_cpus,
            "gpus_cadastradas": total_gpus,
        }
    except Exception as e:
        raise HTTPException(status_code=503, detail=f"Banco indisponível: {e}")


@app.get("/apps", tags=["Catálogo"])
def listar_apps(categoria: Optional[str] = Query(None, example="Game")):
    """Lista todos os aplicativos disponíveis para comparação."""
    conn = get_conn()
    try:
        with conn.cursor() as cur:
            if categoria:
                cur.execute(
                    "SELECT id, nome, versao, categoria, desenvolvedor FROM aplicativos "
                    "WHERE ativo=TRUE AND LOWER(categoria)=LOWER(%s) ORDER BY nome",
                    (categoria,)
                )
            else:
                cur.execute(
                    "SELECT id, nome, versao, categoria, desenvolvedor FROM aplicativos "
                    "WHERE ativo=TRUE ORDER BY categoria, nome"
                )
            return {"apps": cur.fetchall()}
    finally:
        put_conn(conn)


@app.get("/cpus", tags=["Catálogo"])
def listar_cpus(
    fabricante: Optional[str] = Query(None, example="Intel"),
    socket: Optional[str] = Query(None, example="LGA1700"),
    geracao: Optional[int] = Query(None, example=14),
    limit: int = Query(50, le=200),
):
    """Lista CPUs cadastradas com filtros opcionais."""
    conn = get_conn()
    try:
        with conn.cursor() as cur:
            where = ["ativo = TRUE"]
            params = []
            if fabricante:
                where.append("LOWER(fabricante) = LOWER(%s)")
                params.append(fabricante)
            if socket:
                where.append("LOWER(socket) = LOWER(%s)")
                params.append(socket)
            if geracao:
                where.append("geracao_numero = %s")
                params.append(geracao)
            params.append(limit)
            cur.execute(
                f"SELECT fabricante, modelo, geracao, socket, nucleos_total, "
                f"clock_boost_ghz, tdp_w, ano_lancamento "
                f"FROM cpus WHERE {' AND '.join(where)} "
                f"ORDER BY fabricante, geracao_numero DESC, modelo LIMIT %s",
                params
            )
            return {"cpus": cur.fetchall(), "total": cur.rowcount}
    finally:
        put_conn(conn)


@app.get("/gpus", tags=["Catálogo"])
def listar_gpus(
    fabricante: Optional[str] = Query(None, example="NVIDIA"),
    vram_min: Optional[int] = Query(None, example=8),
    limit: int = Query(50, le=200),
):
    """Lista GPUs cadastradas."""
    conn = get_conn()
    try:
        with conn.cursor() as cur:
            where = ["ativo = TRUE"]
            params = []
            if fabricante:
                where.append("LOWER(fabricante) = LOWER(%s)")
                params.append(fabricante)
            if vram_min:
                where.append("vram_gb >= %s")
                params.append(vram_min)
            params.append(limit)
            cur.execute(
                f"SELECT fabricante, modelo, geracao, vram_gb, vram_tipo, "
                f"tdp_w, ano_lancamento, preco_lancamento_usd "
                f"FROM gpus WHERE {' AND '.join(where)} "
                f"ORDER BY fabricante, vram_gb DESC LIMIT %s",
                params
            )
            return {"gpus": cur.fetchall(), "total": cur.rowcount}
    finally:
        put_conn(conn)


@app.post("/comparar", response_model=ComparacaoResponse, tags=["Comparação"])
def comparar(body: ComparacaoRequest):
    """
    Endpoint principal: recebe as specs do PC do usuário
    e retorna o laudo de compatibilidade com o app solicitado.

    Exemplo de chamada (curl):
    ```
    curl -X POST http://localhost:8000/comparar \\
      -H "Content-Type: application/json" \\
      -d '{
        "app_nome": "Cyberpunk 2077",
        "specs": {
          "so": "Windows", "so_versao": "10.0.19045",
          "cpu_modelo": "Intel Core i7-12700K",
          "cpu_nucleos": 12, "cpu_threads": 20, "cpu_clock_ghz": 3.6,
          "gpu_modelo": "NVIDIA GeForce RTX 3070", "gpu_vram_gb": 8,
          "ram_total_gb": 16, "storage_livre_gb": 120
        }
      }'
    ```
    """
    resultado = comparar_specs(body.specs, body.app_nome, body.salvar)
    return resultado


@app.get("/historico/{app_nome}", tags=["Relatórios"])
def historico_app(app_nome: str, limit: int = Query(20, le=100)):
    """Retorna o histórico de comparações de um app."""
    conn = get_conn()
    try:
        with conn.cursor() as cur:
            cur.execute("""
                SELECT sp.cpu_modelo, sp.gpu_modelo, sp.ram_total_gb,
                       c.nivel, c.score_geral, c.realizado_em
                FROM comparacoes c
                JOIN scans_pc sp ON sp.id = c.scan_id
                JOIN aplicativos a ON a.id = c.app_id
                WHERE LOWER(a.nome) LIKE LOWER(%s)
                ORDER BY c.realizado_em DESC
                LIMIT %s
            """, (f"%{app_nome}%", limit))
            return {"historico": cur.fetchall()}
    finally:
        put_conn(conn)
