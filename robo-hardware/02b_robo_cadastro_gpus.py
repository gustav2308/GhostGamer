"""
=============================================================
 ROBÔ DE CADASTRO AUTOMÁTICO DE GPUs
 Insere placas de vídeo no banco de dados por fabricante.

 Este arquivo estava faltando no pacote original: a documentação
 (Manual_Hardware_Compatibility) instrui "rode o robô de GPUs" como
 solução para comparações com vram_gb=0, mas o robô não existia.
 Segue a MESMA estrutura e convenções do 02_robo_cadastro_cpus.py
 (dataset local, idempotente via ON CONFLICT, mesmo CLI) para que o
 agendador (04_scheduler.py) e o restante da automação funcionem sem
 nenhuma outra mudança.

Instalação das dependências: (mesmas do robô de CPUs)
    pip install psycopg2-binary python-dotenv tqdm

Uso:
    python 02b_robo_cadastro_gpus.py --fabricante nvidia --todos
    python 02b_robo_cadastro_gpus.py --fabricante amd --todos
    python 02b_robo_cadastro_gpus.py --fabricante todos --todos
    python 02b_robo_cadastro_gpus.py --fabricante todos --todos --dry-run
"""

import os
import logging
import argparse

import psycopg2
import psycopg2.extras
from tqdm import tqdm
from dotenv import load_dotenv

load_dotenv()

logging.basicConfig(
    level=logging.INFO,
    format="%(asctime)s [%(levelname)s] %(message)s",
    handlers=[
        logging.FileHandler("robo_gpus.log", encoding="utf-8"),
        logging.StreamHandler(),
    ],
)
log = logging.getLogger("RoboGPU")


def conectar_banco():
    return psycopg2.connect(
        host=os.getenv("DB_HOST", "localhost"),
        port=int(os.getenv("DB_PORT", 5432)),
        dbname=os.getenv("DB_NAME", "hardware_db"),
        user=os.getenv("DB_USER", "postgres"),
        password=os.getenv("DB_PASSWORD", ""),
    )


INSERT_GPU_SQL = """
INSERT INTO gpus (
    fabricante, familia, modelo, geracao, arquitetura,
    vram_gb, vram_tipo, vram_barramento_bit, cuda_cores, stream_processors,
    clock_base_mhz, clock_boost_mhz, tdp_w, conector_pcie,
    ray_tracing, dlss_versao, fsr_versao, ano_lancamento,
    preco_lancamento_usd, benchmark_3dmark_ts
)
VALUES (
    %(fabricante)s, %(familia)s, %(modelo)s, %(geracao)s, %(arquitetura)s,
    %(vram_gb)s, %(vram_tipo)s, %(vram_barramento_bit)s, %(cuda_cores)s, %(stream_processors)s,
    %(clock_base_mhz)s, %(clock_boost_mhz)s, %(tdp_w)s, %(conector_pcie)s,
    %(ray_tracing)s, %(dlss_versao)s, %(fsr_versao)s, %(ano_lancamento)s,
    %(preco_lancamento_usd)s, %(benchmark_3dmark_ts)s
)
ON CONFLICT (modelo) DO UPDATE SET
    vram_gb = EXCLUDED.vram_gb,
    clock_base_mhz = EXCLUDED.clock_base_mhz,
    clock_boost_mhz = EXCLUDED.clock_boost_mhz,
    tdp_w = EXCLUDED.tdp_w,
    benchmark_3dmark_ts = EXCLUDED.benchmark_3dmark_ts
RETURNING id, modelo;
"""


def inserir_gpu(cur, gpu: dict) -> tuple:
    campos_default = {
        "geracao": None, "arquitetura": None, "vram_tipo": None,
        "vram_barramento_bit": None, "cuda_cores": None, "stream_processors": None,
        "clock_base_mhz": None, "clock_boost_mhz": None, "tdp_w": None,
        "conector_pcie": None, "ray_tracing": False, "dlss_versao": None,
        "fsr_versao": None, "ano_lancamento": None, "preco_lancamento_usd": None,
        "benchmark_3dmark_ts": None,
    }
    dados = {**campos_default, **gpu}
    cur.execute(INSERT_GPU_SQL, dados)
    return cur.fetchone()


# ─────────────────────────────────────────
#  Dataset local -- NVIDIA (GeForce RTX 30 / 40)
# ─────────────────────────────────────────
NVIDIA_LOCAL = [
    {"fabricante": "NVIDIA", "familia": "GeForce RTX", "modelo": "RTX 4090", "geracao": "Ada Lovelace",
     "arquitetura": "AD102", "vram_gb": 24, "vram_tipo": "GDDR6X", "vram_barramento_bit": 384,
     "cuda_cores": 16384, "clock_base_mhz": 2235, "clock_boost_mhz": 2520, "tdp_w": 450,
     "conector_pcie": "PCIe 4.0 x16", "ray_tracing": True, "dlss_versao": "3", "ano_lancamento": 2022},
    {"fabricante": "NVIDIA", "familia": "GeForce RTX", "modelo": "RTX 4080", "geracao": "Ada Lovelace",
     "arquitetura": "AD103", "vram_gb": 16, "vram_tipo": "GDDR6X", "vram_barramento_bit": 256,
     "cuda_cores": 9728, "clock_base_mhz": 2205, "clock_boost_mhz": 2505, "tdp_w": 320,
     "conector_pcie": "PCIe 4.0 x16", "ray_tracing": True, "dlss_versao": "3", "ano_lancamento": 2022},
    {"fabricante": "NVIDIA", "familia": "GeForce RTX", "modelo": "RTX 4070 Ti", "geracao": "Ada Lovelace",
     "arquitetura": "AD104", "vram_gb": 12, "vram_tipo": "GDDR6X", "vram_barramento_bit": 192,
     "cuda_cores": 7680, "clock_base_mhz": 2310, "clock_boost_mhz": 2610, "tdp_w": 285,
     "conector_pcie": "PCIe 4.0 x16", "ray_tracing": True, "dlss_versao": "3", "ano_lancamento": 2023},
    {"fabricante": "NVIDIA", "familia": "GeForce RTX", "modelo": "RTX 4070", "geracao": "Ada Lovelace",
     "arquitetura": "AD104", "vram_gb": 12, "vram_tipo": "GDDR6X", "vram_barramento_bit": 192,
     "cuda_cores": 5888, "clock_base_mhz": 1920, "clock_boost_mhz": 2475, "tdp_w": 200,
     "conector_pcie": "PCIe 4.0 x16", "ray_tracing": True, "dlss_versao": "3", "ano_lancamento": 2023},
    {"fabricante": "NVIDIA", "familia": "GeForce RTX", "modelo": "RTX 4060 Ti", "geracao": "Ada Lovelace",
     "arquitetura": "AD106", "vram_gb": 8, "vram_tipo": "GDDR6", "vram_barramento_bit": 128,
     "cuda_cores": 4352, "clock_base_mhz": 2310, "clock_boost_mhz": 2535, "tdp_w": 160,
     "conector_pcie": "PCIe 4.0 x8", "ray_tracing": True, "dlss_versao": "3", "ano_lancamento": 2023},
    {"fabricante": "NVIDIA", "familia": "GeForce RTX", "modelo": "RTX 4060", "geracao": "Ada Lovelace",
     "arquitetura": "AD107", "vram_gb": 8, "vram_tipo": "GDDR6", "vram_barramento_bit": 128,
     "cuda_cores": 3072, "clock_base_mhz": 1830, "clock_boost_mhz": 2460, "tdp_w": 115,
     "conector_pcie": "PCIe 4.0 x8", "ray_tracing": True, "dlss_versao": "3", "ano_lancamento": 2023},
    {"fabricante": "NVIDIA", "familia": "GeForce RTX", "modelo": "RTX 3090 Ti", "geracao": "Ampere",
     "arquitetura": "GA102", "vram_gb": 24, "vram_tipo": "GDDR6X", "vram_barramento_bit": 384,
     "cuda_cores": 10752, "clock_base_mhz": 1560, "clock_boost_mhz": 1860, "tdp_w": 450,
     "conector_pcie": "PCIe 4.0 x16", "ray_tracing": True, "dlss_versao": "2", "ano_lancamento": 2022},
    {"fabricante": "NVIDIA", "familia": "GeForce RTX", "modelo": "RTX 3080", "geracao": "Ampere",
     "arquitetura": "GA102", "vram_gb": 10, "vram_tipo": "GDDR6X", "vram_barramento_bit": 320,
     "cuda_cores": 8704, "clock_base_mhz": 1440, "clock_boost_mhz": 1710, "tdp_w": 320,
     "conector_pcie": "PCIe 4.0 x16", "ray_tracing": True, "dlss_versao": "2", "ano_lancamento": 2020},
    {"fabricante": "NVIDIA", "familia": "GeForce RTX", "modelo": "RTX 3070", "geracao": "Ampere",
     "arquitetura": "GA104", "vram_gb": 8, "vram_tipo": "GDDR6", "vram_barramento_bit": 256,
     "cuda_cores": 5888, "clock_base_mhz": 1500, "clock_boost_mhz": 1725, "tdp_w": 220,
     "conector_pcie": "PCIe 4.0 x16", "ray_tracing": True, "dlss_versao": "2", "ano_lancamento": 2020},
    {"fabricante": "NVIDIA", "familia": "GeForce RTX", "modelo": "RTX 3060 Ti", "geracao": "Ampere",
     "arquitetura": "GA104", "vram_gb": 8, "vram_tipo": "GDDR6", "vram_barramento_bit": 256,
     "cuda_cores": 4864, "clock_base_mhz": 1410, "clock_boost_mhz": 1665, "tdp_w": 200,
     "conector_pcie": "PCIe 4.0 x16", "ray_tracing": True, "dlss_versao": "2", "ano_lancamento": 2020},
    {"fabricante": "NVIDIA", "familia": "GeForce RTX", "modelo": "RTX 3060", "geracao": "Ampere",
     "arquitetura": "GA106", "vram_gb": 12, "vram_tipo": "GDDR6", "vram_barramento_bit": 192,
     "cuda_cores": 3584, "clock_base_mhz": 1320, "clock_boost_mhz": 1777, "tdp_w": 170,
     "conector_pcie": "PCIe 4.0 x16", "ray_tracing": True, "dlss_versao": "2", "ano_lancamento": 2021},
    {"fabricante": "NVIDIA", "familia": "GeForce GTX", "modelo": "GTX 1660 Super", "geracao": "Turing",
     "arquitetura": "TU116", "vram_gb": 6, "vram_tipo": "GDDR6", "vram_barramento_bit": 192,
     "cuda_cores": 1408, "clock_base_mhz": 1530, "clock_boost_mhz": 1785, "tdp_w": 125,
     "conector_pcie": "PCIe 3.0 x16", "ray_tracing": False, "ano_lancamento": 2019},
]

# ─────────────────────────────────────────
#  Dataset local -- AMD (Radeon RX 6000 / 7000)
# ─────────────────────────────────────────
AMD_LOCAL = [
    {"fabricante": "AMD", "familia": "Radeon RX", "modelo": "RX 7900 XTX", "geracao": "RDNA 3",
     "arquitetura": "Navi 31", "vram_gb": 24, "vram_tipo": "GDDR6", "vram_barramento_bit": 384,
     "stream_processors": 6144, "clock_base_mhz": 1855, "clock_boost_mhz": 2500, "tdp_w": 355,
     "conector_pcie": "PCIe 4.0 x16", "ray_tracing": True, "fsr_versao": "3", "ano_lancamento": 2022},
    {"fabricante": "AMD", "familia": "Radeon RX", "modelo": "RX 7900 XT", "geracao": "RDNA 3",
     "arquitetura": "Navi 31", "vram_gb": 20, "vram_tipo": "GDDR6", "vram_barramento_bit": 320,
     "stream_processors": 5376, "clock_base_mhz": 1500, "clock_boost_mhz": 2400, "tdp_w": 315,
     "conector_pcie": "PCIe 4.0 x16", "ray_tracing": True, "fsr_versao": "3", "ano_lancamento": 2022},
    {"fabricante": "AMD", "familia": "Radeon RX", "modelo": "RX 7800 XT", "geracao": "RDNA 3",
     "arquitetura": "Navi 32", "vram_gb": 16, "vram_tipo": "GDDR6", "vram_barramento_bit": 256,
     "stream_processors": 3840, "clock_base_mhz": 1295, "clock_boost_mhz": 2430, "tdp_w": 263,
     "conector_pcie": "PCIe 4.0 x16", "ray_tracing": True, "fsr_versao": "3", "ano_lancamento": 2023},
    {"fabricante": "AMD", "familia": "Radeon RX", "modelo": "RX 7700 XT", "geracao": "RDNA 3",
     "arquitetura": "Navi 32", "vram_gb": 12, "vram_tipo": "GDDR6", "vram_barramento_bit": 192,
     "stream_processors": 3456, "clock_base_mhz": 1544, "clock_boost_mhz": 2544, "tdp_w": 245,
     "conector_pcie": "PCIe 4.0 x16", "ray_tracing": True, "fsr_versao": "3", "ano_lancamento": 2023},
    {"fabricante": "AMD", "familia": "Radeon RX", "modelo": "RX 7600", "geracao": "RDNA 3",
     "arquitetura": "Navi 33", "vram_gb": 8, "vram_tipo": "GDDR6", "vram_barramento_bit": 128,
     "stream_processors": 2048, "clock_base_mhz": 1720, "clock_boost_mhz": 2655, "tdp_w": 165,
     "conector_pcie": "PCIe 4.0 x8", "ray_tracing": True, "fsr_versao": "3", "ano_lancamento": 2023},
    {"fabricante": "AMD", "familia": "Radeon RX", "modelo": "RX 6950 XT", "geracao": "RDNA 2",
     "arquitetura": "Navi 21", "vram_gb": 16, "vram_tipo": "GDDR6", "vram_barramento_bit": 256,
     "stream_processors": 5120, "clock_base_mhz": 1720, "clock_boost_mhz": 2310, "tdp_w": 335,
     "conector_pcie": "PCIe 4.0 x16", "ray_tracing": True, "fsr_versao": "2", "ano_lancamento": 2022},
    {"fabricante": "AMD", "familia": "Radeon RX", "modelo": "RX 6800 XT", "geracao": "RDNA 2",
     "arquitetura": "Navi 21", "vram_gb": 16, "vram_tipo": "GDDR6", "vram_barramento_bit": 256,
     "stream_processors": 4608, "clock_base_mhz": 1825, "clock_boost_mhz": 2250, "tdp_w": 300,
     "conector_pcie": "PCIe 4.0 x16", "ray_tracing": True, "fsr_versao": "2", "ano_lancamento": 2020},
    {"fabricante": "AMD", "familia": "Radeon RX", "modelo": "RX 6700 XT", "geracao": "RDNA 2",
     "arquitetura": "Navi 22", "vram_gb": 12, "vram_tipo": "GDDR6", "vram_barramento_bit": 192,
     "stream_processors": 2560, "clock_base_mhz": 2321, "clock_boost_mhz": 2581, "tdp_w": 230,
     "conector_pcie": "PCIe 4.0 x16", "ray_tracing": True, "fsr_versao": "2", "ano_lancamento": 2021},
    {"fabricante": "AMD", "familia": "Radeon RX", "modelo": "RX 6600", "geracao": "RDNA 2",
     "arquitetura": "Navi 23", "vram_gb": 8, "vram_tipo": "GDDR6", "vram_barramento_bit": 128,
     "stream_processors": 1792, "clock_base_mhz": 1626, "clock_boost_mhz": 2491, "tdp_w": 132,
     "conector_pcie": "PCIe 4.0 x8", "ray_tracing": True, "fsr_versao": "2", "ano_lancamento": 2021},
]


class ColetorGPU:
    def __init__(self, dry_run: bool = False):
        self.dry_run = dry_run
        self.stats = {"inseridas": 0, "atualizadas": 0, "erros": 0}

    def _conectar(self):
        return None if self.dry_run else conectar_banco()

    def processar_fabricante(self, fabricante: str):
        fabricante = fabricante.lower()
        if fabricante == "nvidia":
            todas_gpus = NVIDIA_LOCAL
        elif fabricante == "amd":
            todas_gpus = AMD_LOCAL
        elif fabricante == "todos":
            todas_gpus = NVIDIA_LOCAL + AMD_LOCAL
        else:
            log.error("Fabricante desconhecido: %s (use nvidia, amd ou todos)", fabricante)
            return

        log.info("Processando %d GPU(s) de '%s'...", len(todas_gpus), fabricante)

        if self.dry_run:
            for gpu in todas_gpus:
                print(f"[DRY-RUN] {gpu['fabricante']} {gpu['modelo']} -- {gpu['vram_gb']}GB {gpu.get('vram_tipo')}")
            return

        conn = self._conectar()
        try:
            with conn:
                with conn.cursor(cursor_factory=psycopg2.extras.RealDictCursor) as cur:
                    for gpu in tqdm(todas_gpus, desc=f"GPUs {fabricante}"):
                        try:
                            gpu_id, modelo = inserir_gpu(cur, gpu)
                            self.stats["inseridas"] += 1
                            log.info("OK: %s (id=%s)", modelo, gpu_id)
                        except Exception as e:
                            self.stats["erros"] += 1
                            log.error("Erro ao inserir %s: %s", gpu.get("modelo"), e)
                            conn.rollback()
        finally:
            conn.close()

        log.info("Concluído. Estatísticas: %s", self.stats)


def main():
    parser = argparse.ArgumentParser(description="Robô de cadastro automático de GPUs")
    parser.add_argument("--fabricante", choices=["nvidia", "amd", "todos"], required=True)
    parser.add_argument("--todos", action="store_true", help="Processa todo o catálogo do fabricante")
    parser.add_argument("--dry-run", action="store_true", help="Não grava no banco, só mostra o que seria feito")
    args = parser.parse_args()

    coletor = ColetorGPU(dry_run=args.dry_run)
    coletor.processar_fabricante(args.fabricante)


if __name__ == "__main__":
    main()
