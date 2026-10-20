"""
=============================================================
 ROBÔ DE CADASTRO AUTOMÁTICO DE CPUs
 Insere processadores no banco de dados por geração/fabricante
 Fontes: Intel ARK API + AMD (scraping) + fallback local
=============================================================

Instalação das dependências:
    pip install requests beautifulsoup4 psycopg2-binary python-dotenv tqdm

Uso:
    python 02_robo_cadastro_cpus.py --fabricante intel --geracao 14
    python 02_robo_cadastro_cpus.py --fabricante amd --todos
    python 02_robo_cadastro_cpus.py --fabricante todos --todos
"""

import os
import re
import time
import json
import logging
import argparse
from datetime import datetime

import requests
import psycopg2
import psycopg2.extras
from bs4 import BeautifulSoup
from tqdm import tqdm
from dotenv import load_dotenv

load_dotenv()

# ─────────────────────────────────────────
#  Configuração de log
# ─────────────────────────────────────────
logging.basicConfig(
    level=logging.INFO,
    format="%(asctime)s [%(levelname)s] %(message)s",
    handlers=[
        logging.FileHandler("robo_cpus.log", encoding="utf-8"),
        logging.StreamHandler(),
    ],
)
log = logging.getLogger("RoboCPU")

# ─────────────────────────────────────────
#  Conexão com o banco
# ─────────────────────────────────────────
def conectar_banco():
    """Retorna conexão com o PostgreSQL usando variáveis de ambiente."""
    return psycopg2.connect(
        host=os.getenv("DB_HOST", "localhost"),
        port=int(os.getenv("DB_PORT", 5432)),
        dbname=os.getenv("DB_NAME", "hardware_db"),
        user=os.getenv("DB_USER", "postgres"),
        password=os.getenv("DB_PASSWORD", ""),
    )

# ─────────────────────────────────────────
#  Inserção no banco
# ─────────────────────────────────────────
INSERT_CPU_SQL = """
INSERT INTO cpus (
    fabricante, familia, modelo, geracao, geracao_numero, socket,
    nucleos_total, nucleos_performance, nucleos_eficiencia, threads,
    clock_base_ghz, clock_boost_ghz, cache_l2_mb, cache_l3_mb,
    tdp_w, litografia_nm, ram_suportada, ram_max_gb, ram_canais,
    ram_velocidade_mhz, gpu_integrada, gpu_integrada_nome,
    pcie_versao, pcie_lanes, ano_lancamento, preco_lancamento_usd,
    benchmark_cinebench_r23_single, benchmark_cinebench_r23_multi,
    benchmark_passmark
)
VALUES (
    %(fabricante)s, %(familia)s, %(modelo)s, %(geracao)s, %(geracao_numero)s,
    %(socket)s, %(nucleos_total)s, %(nucleos_performance)s, %(nucleos_eficiencia)s,
    %(threads)s, %(clock_base_ghz)s, %(clock_boost_ghz)s, %(cache_l2_mb)s,
    %(cache_l3_mb)s, %(tdp_w)s, %(litografia_nm)s, %(ram_suportada)s,
    %(ram_max_gb)s, %(ram_canais)s, %(ram_velocidade_mhz)s,
    %(gpu_integrada)s, %(gpu_integrada_nome)s, %(pcie_versao)s, %(pcie_lanes)s,
    %(ano_lancamento)s, %(preco_lancamento_usd)s,
    %(benchmark_cinebench_r23_single)s, %(benchmark_cinebench_r23_multi)s,
    %(benchmark_passmark)s
)
ON CONFLICT (modelo) DO UPDATE SET
    clock_base_ghz = EXCLUDED.clock_base_ghz,
    clock_boost_ghz = EXCLUDED.clock_boost_ghz,
    tdp_w = EXCLUDED.tdp_w,
    benchmark_cinebench_r23_single = EXCLUDED.benchmark_cinebench_r23_single,
    benchmark_cinebench_r23_multi  = EXCLUDED.benchmark_cinebench_r23_multi,
    benchmark_passmark             = EXCLUDED.benchmark_passmark,
    atualizado_em = NOW()
RETURNING id, modelo;
"""

def inserir_cpu(cur, cpu: dict) -> tuple[int, str]:
    """Insere ou atualiza uma CPU e retorna (id, modelo)."""
    campos_default = {
        "familia": None, "geracao": None, "geracao_numero": None,
        "socket": None, "nucleos_total": None, "nucleos_performance": None,
        "nucleos_eficiencia": None, "threads": None, "clock_base_ghz": None,
        "clock_boost_ghz": None, "cache_l2_mb": None, "cache_l3_mb": None,
        "tdp_w": None, "litografia_nm": None, "ram_suportada": None,
        "ram_max_gb": None, "ram_canais": None, "ram_velocidade_mhz": None,
        "gpu_integrada": False, "gpu_integrada_nome": None,
        "pcie_versao": None, "pcie_lanes": None, "ano_lancamento": None,
        "preco_lancamento_usd": None, "benchmark_cinebench_r23_single": None,
        "benchmark_cinebench_r23_multi": None, "benchmark_passmark": None,
    }
    campos_default.update(cpu)
    cur.execute(INSERT_CPU_SQL, campos_default)
    row = cur.fetchone()
    return row["id"], row["modelo"]


# ═══════════════════════════════════════════════════════════════
#  FONTE 1: Intel ARK  (API não-oficial mas estável)
# ═══════════════════════════════════════════════════════════════
INTEL_ARK_SEARCH = "https://ark.intel.com/content/www/us/en/ark/search/featurefilter.html"
INTEL_ARK_DETAIL = "https://ark.intel.com/content/www/us/en/ark/products/{ark_id}/{slug}.html"

# Mapeamento geração → família de produto Intel
INTEL_GERACOES = {
    1:  {"nome": "1ª Geração (Nehalem/Westmere)", "litografia": 45, "socket": "LGA1156"},
    2:  {"nome": "2ª Geração (Sandy Bridge)",      "litografia": 32, "socket": "LGA1155"},
    3:  {"nome": "3ª Geração (Ivy Bridge)",         "litografia": 22, "socket": "LGA1155"},
    4:  {"nome": "4ª Geração (Haswell)",             "litografia": 22, "socket": "LGA1150"},
    5:  {"nome": "5ª Geração (Broadwell)",           "litografia": 14, "socket": "LGA1150"},
    6:  {"nome": "6ª Geração (Skylake)",             "litografia": 14, "socket": "LGA1151"},
    7:  {"nome": "7ª Geração (Kaby Lake)",           "litografia": 14, "socket": "LGA1151"},
    8:  {"nome": "8ª Geração (Coffee Lake)",         "litografia": 14, "socket": "LGA1151"},
    9:  {"nome": "9ª Geração (Coffee Lake Refresh)", "litografia": 14, "socket": "LGA1151"},
    10: {"nome": "10ª Geração (Comet Lake)",         "litografia": 14, "socket": "LGA1200"},
    11: {"nome": "11ª Geração (Rocket Lake)",        "litografia": 14, "socket": "LGA1200"},
    12: {"nome": "12ª Geração (Alder Lake)",         "litografia": 10, "socket": "LGA1700"},
    13: {"nome": "13ª Geração (Raptor Lake)",        "litografia": 10, "socket": "LGA1700"},
    14: {"nome": "14ª Geração (Raptor Lake Refresh)","litografia": 10, "socket": "LGA1700"},
}

# AMD: mapeamento de arquitetura (Zen) por geração
AMD_GERACOES = {
    1: {"nome": "Zen (Ryzen 1000)",  "litografia": 14, "socket": "AM4", "pcie": "3.0"},
    2: {"nome": "Zen+ (Ryzen 2000)", "litografia": 12, "socket": "AM4", "pcie": "3.0"},
    3: {"nome": "Zen 2 (Ryzen 3000)","litografia": 7,  "socket": "AM4", "pcie": "4.0"},
    4: {"nome": "Zen 3 (Ryzen 5000)","litografia": 7,  "socket": "AM4", "pcie": "4.0"},
    5: {"nome": "Zen 4 (Ryzen 7000)","litografia": 5,  "socket": "AM5", "pcie": "5.0"},
    6: {"nome": "Zen 5 (Ryzen 9000)","litografia": 4,  "socket": "AM5", "pcie": "5.0"},
}

# Dataset local de fallback (quando scraping não disponível)
# Formato: (modelo, familia, gen, nucleos_p, nucleos_e, threads, base, boost, l3, tdp, ano)
INTEL_LOCAL = {
    12: [
        ("Core i9-12900K","Core i9", 8,8,16,24, 3.2,5.2,30,125,2021),
        ("Core i9-12900",  "Core i9", 8,8,16,24, 2.4,5.1,30, 65,2021),
        ("Core i7-12700K","Core i7", 8,4,12,20, 3.6,5.0,25,125,2021),
        ("Core i7-12700",  "Core i7", 8,4,12,20, 2.1,4.9,25, 65,2021),
        ("Core i5-12600K","Core i5", 6,4,10,16, 3.7,4.9,20,125,2021),
        ("Core i5-12600",  "Core i5", 6,0, 6,12, 3.3,4.8,18, 65,2021),
        ("Core i3-12100",  "Core i3", 4,0, 4, 8, 3.3,4.3,12, 60,2022),
    ],
    13: [
        ("Core i9-13900K","Core i9",8,8,16,32,3.0,5.8,36,125,2022),
        ("Core i9-13900",  "Core i9",8,8,16,32,2.0,5.6,36, 65,2022),
        ("Core i7-13700K","Core i7",8,4,8, 24,3.4,5.4,30,125,2022),
        ("Core i7-13700",  "Core i7",8,4,8, 24,2.1,5.2,30, 65,2022),
        ("Core i5-13600K","Core i5",6,4,8, 20,3.5,5.1,24,125,2022),
        ("Core i5-13500",  "Core i5",6,2,8, 20,2.5,4.8,24, 65,2022),
        ("Core i5-13400",  "Core i5",6,0,6, 12,2.5,4.6,20, 65,2023),
        ("Core i3-13100",  "Core i3",4,0,4,  8,3.4,4.5,12, 60,2023),
    ],
    14: [
        ("Core i9-14900K","Core i9",8,8,16,32,3.2,6.0,36,125,2023),
        ("Core i9-14900",  "Core i9",8,8,16,32,2.0,5.8,36, 65,2023),
        ("Core i7-14700K","Core i7",8,4,12,28,3.4,5.6,33,125,2023),
        ("Core i7-14700",  "Core i7",8,4,12,28,2.1,5.4,33, 65,2023),
        ("Core i5-14600K","Core i5",6,4,8, 20,3.5,5.3,24,125,2023),
        ("Core i5-14500",  "Core i5",6,2,8, 20,2.6,5.0,24, 65,2024),
        ("Core i5-14400",  "Core i5",6,0,6, 12,2.5,4.7,20, 65,2024),
        ("Core i3-14100",  "Core i3",4,0,4,  8,3.5,4.7,12, 60,2024),
    ],
}

AMD_LOCAL = {
    1: [  # Zen 1
        ("Ryzen 9 1950X","Ryzen 9",16,32,3.4,4.0,32,180,2017),
        ("Ryzen 7 1800X","Ryzen 7", 8,16,3.6,4.0,20, 95,2017),
        ("Ryzen 7 1700X","Ryzen 7", 8,16,3.4,3.8,20, 95,2017),
        ("Ryzen 5 1600X","Ryzen 5", 6,12,3.6,4.0,16, 95,2017),
        ("Ryzen 5 1600", "Ryzen 5", 6,12,3.2,3.6,16, 65,2017),
        ("Ryzen 3 1300X","Ryzen 3", 4, 8,3.5,3.7, 8, 65,2017),
    ],
    3: [  # Zen 2
        ("Ryzen 9 3950X","Ryzen 9",16,32,3.5,4.7,64,105,2019),
        ("Ryzen 9 3900X","Ryzen 9",12,24,3.8,4.6,64,105,2019),
        ("Ryzen 7 3800X","Ryzen 7", 8,16,3.9,4.5,32,105,2019),
        ("Ryzen 7 3700X","Ryzen 7", 8,16,3.6,4.4,32, 65,2019),
        ("Ryzen 5 3600X","Ryzen 5", 6,12,3.8,4.4,32, 95,2019),
        ("Ryzen 5 3600", "Ryzen 5", 6,12,3.6,4.2,32, 65,2019),
    ],
    4: [  # Zen 3
        ("Ryzen 9 5950X","Ryzen 9",16,32,3.4,4.9,64,105,2020),
        ("Ryzen 9 5900X","Ryzen 9",12,24,3.7,4.8,64,105,2020),
        ("Ryzen 7 5800X","Ryzen 7", 8,16,3.8,4.7,32,105,2020),
        ("Ryzen 7 5800X3D","Ryzen 7",8,16,3.4,4.5,96,105,2022),
        ("Ryzen 5 5600X","Ryzen 5", 6,12,3.7,4.6,32, 65,2020),
        ("Ryzen 5 5600",  "Ryzen 5", 6,12,3.5,4.4,32, 65,2021),
        ("Ryzen 3 5300G", "Ryzen 3", 4, 8,4.0,4.2,8,  65,2021),
    ],
    5: [  # Zen 4
        ("Ryzen 9 7950X","Ryzen 9",16,32,4.5,5.7,64,170,2022),
        ("Ryzen 9 7900X","Ryzen 9",12,24,4.7,5.6,64,170,2022),
        ("Ryzen 9 7900",  "Ryzen 9",12,24,3.7,5.4,64, 65,2022),
        ("Ryzen 7 7700X","Ryzen 7", 8,16,4.5,5.4,32,105,2022),
        ("Ryzen 7 7700",  "Ryzen 7", 8,16,3.8,5.3,32, 65,2022),
        ("Ryzen 5 7600X","Ryzen 5", 6,12,4.7,5.3,32,105,2022),
        ("Ryzen 5 7600",  "Ryzen 5", 6,12,3.8,5.1,32, 65,2023),
        ("Ryzen 5 7500F", "Ryzen 5", 6,12,3.7,5.0,32, 65,2023),
    ],
    6: [  # Zen 5
        ("Ryzen 9 9950X","Ryzen 9",16,32,4.3,5.7,64,170,2024),
        ("Ryzen 9 9900X","Ryzen 9",12,24,4.4,5.6,64,120,2024),
        ("Ryzen 7 9700X","Ryzen 7", 8,16,3.8,5.5,32, 65,2024),
        ("Ryzen 5 9600X","Ryzen 5", 6,12,3.9,5.4,32, 65,2024),
    ],
}


# ═══════════════════════════════════════════════════════════════
#  COLETORES
# ═══════════════════════════════════════════════════════════════

class ColetorIntel:
    """Coleta CPUs Intel da base local e tenta enriquecer via ARK."""

    HEADERS = {"User-Agent": "Mozilla/5.0 (compatible; HardwareBot/1.0)"}

    def coletar(self, geracao: int) -> list[dict]:
        info_gen = INTEL_GERACOES.get(geracao)
        if not info_gen:
            log.warning(f"Intel: geração {geracao} não mapeada.")
            return []

        cpus_locais = INTEL_LOCAL.get(geracao, [])
        if not cpus_locais:
            log.warning(f"Intel: sem dados locais para geração {geracao}.")
            return []

        resultado = []
        for row in cpus_locais:
            modelo, familia, nuc_p, nuc_e, threads, base, boost, l3, tdp, ano = (
                row[0], row[1], row[2], row[3], row[4],
                row[5], row[6], row[7], row[8], row[9]
            )
            cpu = {
                "fabricante":           "Intel",
                "familia":              familia,
                "modelo":               modelo,
                "geracao":              info_gen["nome"],
                "geracao_numero":       geracao,
                "socket":               info_gen["socket"],
                "nucleos_total":        nuc_p + nuc_e,
                "nucleos_performance":  nuc_p,
                "nucleos_eficiencia":   nuc_e,
                "threads":              threads,
                "clock_base_ghz":       base,
                "clock_boost_ghz":      boost,
                "cache_l3_mb":          l3,
                "tdp_w":                tdp,
                "litografia_nm":        info_gen["litografia"],
                "ram_suportada":        "DDR4/DDR5" if geracao >= 12 else "DDR4",
                "ram_max_gb":           192 if geracao >= 12 else 128,
                "ram_canais":           2,
                "pcie_versao":          "5.0" if geracao >= 12 else "4.0",
                "pcie_lanes":           20,
                "ano_lancamento":       ano,
                "gpu_integrada":        "F" not in modelo,  # sufixo F = sem iGPU
                "gpu_integrada_nome":   None if "F" in modelo else "Intel UHD Graphics",
            }
            resultado.append(cpu)

        log.info(f"Intel geração {geracao}: {len(resultado)} CPUs coletadas (base local).")
        return resultado


class ColetorAMD:
    """Coleta CPUs AMD da base local."""

    def coletar(self, geracao: int) -> list[dict]:
        info_gen = AMD_GERACOES.get(geracao)
        if not info_gen:
            log.warning(f"AMD: geração/arquitetura {geracao} não mapeada.")
            return []

        cpus_locais = AMD_LOCAL.get(geracao, [])
        if not cpus_locais:
            log.warning(f"AMD: sem dados locais para geração {geracao}.")
            return []

        resultado = []
        for row in cpus_locais:
            modelo, familia, nucleos, threads, base, boost, l3, tdp, ano = (
                row[0], row[1], row[2], row[3], row[4],
                row[5], row[6], row[7], row[8]
            )
            cpu = {
                "fabricante":          "AMD",
                "familia":             familia,
                "modelo":              modelo,
                "geracao":             info_gen["nome"],
                "geracao_numero":      geracao,
                "socket":              info_gen["socket"],
                "nucleos_total":       nucleos,
                "nucleos_performance": None,
                "nucleos_eficiencia":  None,
                "threads":             threads,
                "clock_base_ghz":      base,
                "clock_boost_ghz":     boost,
                "cache_l3_mb":         l3,
                "tdp_w":               tdp,
                "litografia_nm":       info_gen["litografia"],
                "ram_suportada":       "DDR5" if geracao >= 5 else "DDR4",
                "ram_max_gb":          128,
                "ram_canais":          2,
                "pcie_versao":         info_gen["pcie"],
                "pcie_lanes":          24,
                "ano_lancamento":      ano,
                "gpu_integrada":       modelo.endswith("G") or modelo.endswith("GE"),
                "gpu_integrada_nome":  "AMD Radeon Graphics" if (modelo.endswith("G") or modelo.endswith("GE")) else None,
            }
            resultado.append(cpu)

        log.info(f"AMD geração {geracao}: {len(resultado)} CPUs coletadas.")
        return resultado


# ═══════════════════════════════════════════════════════════════
#  ROBÔ PRINCIPAL
# ═══════════════════════════════════════════════════════════════

class RoboCadastroCPU:

    def __init__(self, dry_run: bool = False):
        self.dry_run = dry_run
        self.coletores = {
            "intel": ColetorIntel(),
            "amd":   ColetorAMD(),
        }
        self.stats = {
            "inseridos": 0,
            "atualizados": 0,
            "erros": 0,
            "total": 0,
        }

    def _conectar(self):
        if self.dry_run:
            return None
        return conectar_banco()

    def processar_fabricante(self, fabricante: str, geracoes: list[int]):
        coletor = self.coletores.get(fabricante.lower())
        if not coletor:
            log.error(f"Fabricante desconhecido: {fabricante}")
            return

        todas_cpus = []
        for gen in geracoes:
            cpus = coletor.coletar(gen)
            todas_cpus.extend(cpus)

        if not todas_cpus:
            log.warning(f"Nenhuma CPU coletada para {fabricante}.")
            return

        self.stats["total"] += len(todas_cpus)
        log.info(f"{fabricante}: {len(todas_cpus)} CPUs a processar.")

        if self.dry_run:
            log.info("[DRY RUN] Dados que seriam inseridos:")
            for cpu in todas_cpus:
                print(f"  → {cpu['modelo']} ({cpu['geracao']}) | {cpu['nucleos_total']} núcleos | {cpu['clock_boost_ghz']} GHz boost | {cpu['tdp_w']}W")
            return

        conn = self._conectar()
        try:
            with conn:
                with conn.cursor(cursor_factory=psycopg2.extras.DictCursor) as cur:
                    for cpu in tqdm(todas_cpus, desc=f"Inserindo {fabricante}", unit="CPU"):
                        try:
                            cpu_id, modelo = inserir_cpu(cur, cpu)
                            self.stats["inseridos"] += 1
                            log.debug(f"  ✓ [{cpu_id}] {modelo}")
                        except Exception as e:
                            self.stats["erros"] += 1
                            log.error(f"  ✗ Erro ao inserir {cpu.get('modelo')}: {e}")
                            conn.rollback()
        finally:
            conn.close()

    def executar(self, fabricantes: list[str], geracoes_intel: list[int], geracoes_amd: list[int]):
        inicio = datetime.now()
        log.info("=" * 60)
        log.info(" ROBÔ DE CADASTRO DE CPUs INICIADO")
        log.info(f" Modo: {'DRY RUN (sem escrita)' if self.dry_run else 'PRODUÇÃO'}")
        log.info("=" * 60)

        for fab in fabricantes:
            geracoes = geracoes_intel if fab.lower() == "intel" else geracoes_amd
            self.processar_fabricante(fab, geracoes)

        duracao = (datetime.now() - inicio).seconds
        log.info("=" * 60)
        log.info(" RESUMO FINAL")
        log.info(f"  Total processado : {self.stats['total']}")
        log.info(f"  Inseridos/Atualizados: {self.stats['inseridos']}")
        log.info(f"  Erros            : {self.stats['erros']}")
        log.info(f"  Tempo            : {duracao}s")
        log.info("=" * 60)


# ─────────────────────────────────────────
#  CLI
# ─────────────────────────────────────────
def parse_args():
    parser = argparse.ArgumentParser(description="Robô de cadastro automático de CPUs")
    parser.add_argument("--fabricante", choices=["intel", "amd", "todos"], default="todos",
                        help="Fabricante a processar (padrão: todos)")
    parser.add_argument("--geracao", type=int, default=None,
                        help="Geração específica (ex: 14 para Intel, 5 para AMD/Zen)")
    parser.add_argument("--todos", action="store_true",
                        help="Processar todas as gerações mapeadas")
    parser.add_argument("--dry-run", action="store_true",
                        help="Apenas lista o que seria inserido, sem gravar no banco")
    return parser.parse_args()


if __name__ == "__main__":
    args = parse_args()

    # Define quais gerações processar
    if args.todos or args.geracao is None:
        geracoes_intel = list(INTEL_GERACOES.keys())      # 1..14
        geracoes_amd   = list(AMD_GERACOES.keys())         # 1..6
    else:
        geracoes_intel = [args.geracao]
        geracoes_amd   = [args.geracao]

    fabricantes = (
        ["intel", "amd"] if args.fabricante == "todos"
        else [args.fabricante]
    )

    robo = RoboCadastroCPU(dry_run=args.dry_run)
    robo.executar(
        fabricantes=fabricantes,
        geracoes_intel=geracoes_intel,
        geracoes_amd=geracoes_amd,
    )
