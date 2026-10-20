"""
=============================================================
 MOTOR DE COMPARAÇÃO DE COMPATIBILIDADE
 Lê as specs do PC do usuário e compara com os requisitos
 de um aplicativo cadastrado no banco de dados.
=============================================================

Fluxo:
  1. scan_pc()        →  detecta hardware do usuário (Windows/Linux/Mac)
  2. match_hardware() →  cruza com a tabela cpus/gpus do banco
  3. comparar()       →  gera score e laudo por componente
  4. salvar_resultado()→  grava em scans_pc + comparacoes

Uso como módulo:
    (arquivos que começam com número não podem ser importados com
    "import", então use importlib -- veja tests/conftest.py para um
    exemplo real disso no projeto)

    import importlib
    comparacao = importlib.import_module("03_comparacao")
    resultado = comparacao.Comparador().analisar(app_nome="Cyberpunk 2077")
    print(resultado)

Uso direto:
    python 03_comparacao.py --app "Cyberpunk 2077"
    python 03_comparacao.py --app "Adobe Premiere Pro" --json
"""

import os
import re
import sys
import json
import platform
import argparse
import subprocess
import logging
from dataclasses import dataclass, asdict, field
from typing import Optional

import psycopg2
import psycopg2.extras
from dotenv import load_dotenv

load_dotenv()
logging.basicConfig(level=logging.INFO, format="%(asctime)s [%(levelname)s] %(message)s")
log = logging.getLogger("Comparador")


# ─────────────────────────────────────────
#  Estruturas de dados
# ─────────────────────────────────────────

@dataclass
class SpecsPC:
    so: str = ""
    so_versao: str = ""
    cpu_modelo: str = ""
    cpu_nucleos: int = 0
    cpu_threads: int = 0
    cpu_clock_ghz: float = 0.0
    gpu_modelo: str = ""
    gpu_vram_gb: int = 0
    ram_total_gb: int = 0
    ram_velocidade_mhz: int = 0
    ram_tipo: str = ""
    storage_livre_gb: int = 0
    storage_total_gb: int = 0
    directx_versao: str = ""

@dataclass
class ScoreComponente:
    componente: str
    valor_usuario: str
    valor_minimo: str
    valor_recomendado: str
    score: int          # 0 = abaixo do mínimo | 50 = mínimo | 100 = recomendado ou acima
    status: str         # INSUFICIENTE | MÍNIMO | RECOMENDADO | ÓTIMO
    observacao: str = ""

@dataclass
class ResultadoComparacao:
    app_nome: str
    nivel_geral: str            # ABAIXO_MINIMO | MINIMO | RECOMENDADO | ACIMA
    score_geral: int            # 0-100
    score_cpu: int = 0
    score_gpu: int = 0
    score_ram: int = 0
    score_storage: int = 0
    componentes: list = field(default_factory=list)
    resumo: str = ""


# ─────────────────────────────────────────
#  Banco de dados
# ─────────────────────────────────────────

def conectar_banco():
    return psycopg2.connect(
        host=os.getenv("DB_HOST", "localhost"),
        port=int(os.getenv("DB_PORT", 5432)),
        dbname=os.getenv("DB_NAME", "hardware_db"),
        user=os.getenv("DB_USER", "postgres"),
        password=os.getenv("DB_PASSWORD", ""),
    )


# ═══════════════════════════════════════════════════════════════
#  1. SCAN DO PC DO USUÁRIO
# ═══════════════════════════════════════════════════════════════

class ScannerPC:
    """Detecta automaticamente as especificações do hardware."""

    def scan(self) -> SpecsPC:
        sistema = platform.system()
        log.info(f"Escaneando PC ({sistema})...")
        if sistema == "Windows":
            return self._scan_windows()
        elif sistema == "Darwin":
            return self._scan_mac()
        else:
            return self._scan_linux()

    # ── Windows via WMI (subprocess wmic) ───────────────────────
    def _scan_windows(self) -> SpecsPC:
        specs = SpecsPC()
        specs.so = "Windows"
        specs.so_versao = platform.version()

        # CPU
        try:
            out = subprocess.check_output(
                ["wmic", "cpu", "get", "Name,NumberOfCores,NumberOfLogicalProcessors,MaxClockSpeed", "/format:csv"],
                text=True, stderr=subprocess.DEVNULL
            )
            for line in out.strip().splitlines():
                if "," in line and "Node" not in line and line.strip():
                    parts = line.split(",")
                    if len(parts) >= 5:
                        specs.cpu_clock_ghz = round(int(parts[1].strip()) / 1000, 2)
                        specs.cpu_modelo    = parts[2].strip()
                        specs.cpu_nucleos   = int(parts[3].strip())
                        specs.cpu_threads   = int(parts[4].strip())
                        break
        except Exception as e:
            log.warning(f"CPU scan falhou: {e}")

        # RAM total
        try:
            out = subprocess.check_output(
                ["wmic", "ComputerSystem", "get", "TotalPhysicalMemory", "/format:csv"],
                text=True, stderr=subprocess.DEVNULL
            )
            for line in out.strip().splitlines():
                if "," in line and "Node" not in line and line.strip():
                    val = line.split(",")[-1].strip()
                    if val.isdigit():
                        specs.ram_total_gb = int(int(val) / (1024**3))
                        break
        except Exception as e:
            log.warning(f"RAM scan falhou: {e}")

        # GPU
        try:
            out = subprocess.check_output(
                ["wmic", "path", "win32_VideoController", "get", "Name,AdapterRAM", "/format:csv"],
                text=True, stderr=subprocess.DEVNULL
            )
            for line in out.strip().splitlines():
                if "," in line and "Node" not in line and line.strip():
                    parts = line.split(",")
                    if len(parts) >= 3:
                        vram_bytes = parts[1].strip()
                        nome = parts[2].strip()
                        if nome and "Microsoft" not in nome:  # ignora driver básico
                            specs.gpu_modelo  = nome
                            specs.gpu_vram_gb = int(int(vram_bytes) / (1024**3)) if vram_bytes.isdigit() else 0
                            break
        except Exception as e:
            log.warning(f"GPU scan falhou: {e}")

        # Storage livre
        try:
            import shutil
            total, _, livre = shutil.disk_usage("/")
            specs.storage_total_gb = int(total / (1024**3))
            specs.storage_livre_gb = int(livre / (1024**3))
        except Exception:
            pass

        # DirectX (registro)
        try:
            import winreg
            key = winreg.OpenKey(winreg.HKEY_LOCAL_MACHINE,
                r"SOFTWARE\Microsoft\DirectX")
            specs.directx_versao = winreg.QueryValueEx(key, "Version")[0]
        except Exception:
            specs.directx_versao = "12"  # default seguro para W10+

        return specs

    # ── macOS via system_profiler ────────────────────────────────
    def _scan_mac(self) -> SpecsPC:
        specs = SpecsPC()
        specs.so = "macOS"
        specs.so_versao = platform.mac_ver()[0]

        try:
            out = subprocess.check_output(
                ["system_profiler", "SPHardwareDataType"], text=True
            )
            for line in out.splitlines():
                if "Chip" in line or "Processor Name" in line:
                    specs.cpu_modelo = line.split(":")[-1].strip()
                elif "Total Number of Cores" in line:
                    specs.cpu_nucleos = int(re.search(r"\d+", line).group())
                elif "Memory:" in line:
                    match = re.search(r"(\d+)\s*GB", line)
                    if match:
                        specs.ram_total_gb = int(match.group(1))
        except Exception as e:
            log.warning(f"Mac scan falhou: {e}")

        try:
            out = subprocess.check_output(
                ["system_profiler", "SPDisplaysDataType"], text=True
            )
            for line in out.splitlines():
                if "Chipset Model" in line:
                    specs.gpu_modelo = line.split(":")[-1].strip()
                elif "VRAM" in line:
                    match = re.search(r"(\d+)\s*(MB|GB)", line)
                    if match:
                        vram = int(match.group(1))
                        specs.gpu_vram_gb = vram if match.group(2) == "GB" else max(1, vram // 1024)
        except Exception as e:
            log.warning(f"GPU Mac scan falhou: {e}")

        try:
            import shutil
            total, _, livre = shutil.disk_usage("/")
            specs.storage_total_gb = int(total / (1024**3))
            specs.storage_livre_gb = int(livre / (1024**3))
        except Exception:
            pass

        return specs

    # ── Linux via /proc e lspci ──────────────────────────────────
    def _scan_linux(self) -> SpecsPC:
        specs = SpecsPC()
        specs.so = "Linux"
        specs.so_versao = platform.release()

        # CPU via /proc/cpuinfo
        try:
            with open("/proc/cpuinfo") as f:
                cpuinfo = f.read()
            for line in cpuinfo.splitlines():
                if "model name" in line:
                    specs.cpu_modelo = line.split(":")[1].strip()
                    break
            specs.cpu_nucleos = cpuinfo.count("processor\t:")
            mhz_matches = re.findall(r"cpu MHz\s*:\s*([\d.]+)", cpuinfo)
            if mhz_matches:
                specs.cpu_clock_ghz = round(max(float(m) for m in mhz_matches) / 1000, 2)
        except Exception as e:
            log.warning(f"CPU Linux scan falhou: {e}")

        # RAM via /proc/meminfo
        try:
            with open("/proc/meminfo") as f:
                mem = f.read()
            match = re.search(r"MemTotal:\s+(\d+)\s+kB", mem)
            if match:
                specs.ram_total_gb = int(int(match.group(1)) / (1024**2))
        except Exception:
            pass

        # GPU via lspci
        try:
            out = subprocess.check_output(["lspci"], text=True, stderr=subprocess.DEVNULL)
            for line in out.splitlines():
                if "VGA" in line or "3D" in line:
                    specs.gpu_modelo = line.split(":")[-1].strip()
                    break
        except Exception:
            pass

        try:
            import shutil
            total, _, livre = shutil.disk_usage("/")
            specs.storage_total_gb = int(total / (1024**3))
            specs.storage_livre_gb = int(livre / (1024**3))
        except Exception:
            pass

        return specs


# ═══════════════════════════════════════════════════════════════
#  2. MATCH COM O BANCO DE DADOS
# ═══════════════════════════════════════════════════════════════

class MatchHardware:
    """Cruza a string do hardware detectado com os registros do banco."""

    def __init__(self, cur):
        self.cur = cur

    def match_cpu(self, modelo_str: str) -> Optional[dict]:
        """Retorna o registro de CPU mais próximo pelo nome."""
        if not modelo_str:
            return None
        # Busca por similaridade de nome
        self.cur.execute("""
            SELECT id, modelo, fabricante, nucleos_total, clock_boost_ghz,
                   geracao, benchmark_cinebench_r23_multi, benchmark_passmark
            FROM cpus
            WHERE ativo = TRUE
              AND LOWER(modelo) LIKE LOWER(%(termo)s)
            ORDER BY ano_lancamento DESC
            LIMIT 1
        """, {"termo": f"%{self._extrair_modelo(modelo_str)}%"})
        row = self.cur.fetchone()
        if not row:
            # fallback: busca pelo fabricante + série
            fab = self._detectar_fabricante(modelo_str)
            serie = self._extrair_serie(modelo_str)
            if fab and serie:
                self.cur.execute("""
                    SELECT id, modelo, fabricante, nucleos_total, clock_boost_ghz,
                           geracao, benchmark_cinebench_r23_multi, benchmark_passmark
                    FROM cpus WHERE ativo=TRUE
                      AND LOWER(fabricante)=LOWER(%(fab)s)
                      AND LOWER(modelo) LIKE LOWER(%(serie)s)
                    LIMIT 1
                """, {"fab": fab, "serie": f"%{serie}%"})
                row = self.cur.fetchone()
        return dict(row) if row else None

    def match_gpu(self, modelo_str: str) -> Optional[dict]:
        if not modelo_str:
            return None
        termo = self._extrair_modelo_gpu(modelo_str)
        self.cur.execute("""
            SELECT id, modelo, fabricante, vram_gb, benchmark_3dmark_ts
            FROM gpus
            WHERE ativo=TRUE AND LOWER(modelo) LIKE LOWER(%(t)s)
            LIMIT 1
        """, {"t": f"%{termo}%"})
        row = self.cur.fetchone()
        return dict(row) if row else None

    def _extrair_modelo(self, s: str) -> str:
        """Extrai o identificador principal do modelo (ex: 'i9-13900K')."""
        match = re.search(r"(i\d-\d{4,5}\w*|Ryzen\s\d\s\d{4}\w*|Core\s\w+|M\d\s)", s, re.IGNORECASE)
        return match.group(0).strip() if match else s[:30]

    def _extrair_modelo_gpu(self, s: str) -> str:
        match = re.search(r"(RTX\s?\d{4}\s?\w*|GTX\s?\d{4}\s?\w*|RX\s?\d{4}\s?\w*|Radeon\s\w+)", s, re.IGNORECASE)
        return match.group(0).strip() if match else s[:30]

    def _detectar_fabricante(self, s: str) -> str:
        s_lower = s.lower()
        if "intel" in s_lower or "core" in s_lower or "celeron" in s_lower:
            return "Intel"
        if "amd" in s_lower or "ryzen" in s_lower:
            return "AMD"
        if "apple" in s_lower or "m1" in s_lower or "m2" in s_lower or "m3" in s_lower:
            return "ARM"
        return ""

    def _extrair_serie(self, s: str) -> str:
        match = re.search(r"\d{4,5}", s)
        return match.group(0) if match else ""


# ═══════════════════════════════════════════════════════════════
#  3. MOTOR DE COMPARAÇÃO
# ═══════════════════════════════════════════════════════════════

def _score_numerico(valor: float, minimo: float, recomendado: float) -> tuple[int, str]:
    """Calcula score 0-100 e status para um valor numérico."""
    if not minimo:
        return 100, "ÓTIMO"
    if valor < minimo:
        score = max(0, int((valor / minimo) * 50))
        return score, "INSUFICIENTE"
    if not recomendado or valor >= recomendado:
        return 100, "ÓTIMO"
    score = 50 + int(((valor - minimo) / (recomendado - minimo)) * 50)
    return min(score, 99), "MÍNIMO" if score < 75 else "RECOMENDADO"


class Comparador:
    """Orquestra todo o processo de scan + match + comparação."""

    def analisar(self, app_nome: str, specs_manuais: Optional[SpecsPC] = None) -> ResultadoComparacao:
        specs = specs_manuais if specs_manuais else ScannerPC().scan()

        conn = conectar_banco()
        try:
            with conn.cursor(cursor_factory=psycopg2.extras.DictCursor) as cur:
                # Busca o aplicativo
                cur.execute("""
                    SELECT * FROM aplicativos
                    WHERE LOWER(nome) LIKE LOWER(%s) AND ativo=TRUE
                    LIMIT 1
                """, (f"%{app_nome}%",))
                app = cur.fetchone()
                if not app:
                    raise ValueError(f"Aplicativo '{app_nome}' não encontrado no banco.")

                app = dict(app)
                matcher = MatchHardware(cur)
                cpu_match = matcher.match_cpu(specs.cpu_modelo)
                gpu_match = matcher.match_gpu(specs.gpu_modelo)

                # ── Score CPU ────────────────────────────────────
                nucleos_min = app.get("req_min_cpu_nucleos") or 0
                nucleos_rec = app.get("req_rec_cpu_nucleos") or nucleos_min
                ghz_min     = float(app.get("req_min_cpu_ghz") or 0)
                ghz_rec     = float(app.get("req_rec_cpu_ghz") or ghz_min)

                score_nucleos, st_nucleos = _score_numerico(specs.cpu_nucleos, nucleos_min, nucleos_rec)
                score_ghz,     st_ghz     = _score_numerico(specs.cpu_clock_ghz, ghz_min, ghz_rec)
                score_cpu = (score_nucleos + score_ghz) // 2

                cpu_comp = ScoreComponente(
                    componente="CPU",
                    valor_usuario=f"{specs.cpu_modelo} ({specs.cpu_nucleos} núcleos, {specs.cpu_clock_ghz} GHz)",
                    valor_minimo=f"{nucleos_min} núcleos, {ghz_min} GHz",
                    valor_recomendado=f"{nucleos_rec} núcleos, {ghz_rec} GHz",
                    score=score_cpu,
                    status=st_nucleos if st_nucleos == "INSUFICIENTE" else st_ghz,
                    observacao=f"Match no banco: {cpu_match['modelo'] if cpu_match else 'não encontrado'}"
                )

                # ── Score GPU / VRAM ─────────────────────────────
                vram_min = app.get("req_min_gpu_vram_gb") or 0
                vram_rec = app.get("req_rec_gpu_vram_gb") or vram_min
                score_gpu, st_gpu = _score_numerico(specs.gpu_vram_gb, vram_min, vram_rec)

                gpu_comp = ScoreComponente(
                    componente="GPU",
                    valor_usuario=f"{specs.gpu_modelo} ({specs.gpu_vram_gb} GB VRAM)",
                    valor_minimo=f"{vram_min} GB VRAM",
                    valor_recomendado=f"{vram_rec} GB VRAM",
                    score=score_gpu,
                    status=st_gpu,
                    observacao=f"Match no banco: {gpu_match['modelo'] if gpu_match else 'não encontrado'}"
                )

                # ── Score RAM ─────────────────────────────────────
                ram_min = app.get("req_min_ram_gb") or 0
                ram_rec = app.get("req_rec_ram_gb") or ram_min
                score_ram, st_ram = _score_numerico(specs.ram_total_gb, ram_min, ram_rec)

                ram_comp = ScoreComponente(
                    componente="RAM",
                    valor_usuario=f"{specs.ram_total_gb} GB",
                    valor_minimo=f"{ram_min} GB",
                    valor_recomendado=f"{ram_rec} GB",
                    score=score_ram,
                    status=st_ram,
                )

                # ── Score Storage ─────────────────────────────────
                storage_min = app.get("req_min_storage_gb") or 0
                storage_rec = app.get("req_rec_storage_gb") or storage_min
                score_storage, st_storage = _score_numerico(specs.storage_livre_gb, storage_min, storage_rec)

                storage_comp = ScoreComponente(
                    componente="Armazenamento",
                    valor_usuario=f"{specs.storage_livre_gb} GB livres",
                    valor_minimo=f"{storage_min} GB",
                    valor_recomendado=f"{storage_rec} GB",
                    score=score_storage,
                    status=st_storage,
                )

                # ── Score geral ───────────────────────────────────
                score_geral = (score_cpu * 35 + score_gpu * 40 + score_ram * 15 + score_storage * 10) // 100

                if score_geral < 40:
                    nivel = "ABAIXO_MINIMO"
                    resumo = f"Seu PC não atende aos requisitos mínimos de {app['nome']}."
                elif score_geral < 65:
                    nivel = "MINIMO"
                    resumo = f"Seu PC atende ao mínimo de {app['nome']}, mas pode ter desempenho limitado."
                elif score_geral < 90:
                    nivel = "RECOMENDADO"
                    resumo = f"Seu PC atende aos requisitos recomendados de {app['nome']}. Boa experiência esperada."
                else:
                    nivel = "ACIMA"
                    resumo = f"Seu PC está acima dos requisitos recomendados. {app['nome']} vai rodar com excelência."

                resultado = ResultadoComparacao(
                    app_nome=app["nome"],
                    nivel_geral=nivel,
                    score_geral=score_geral,
                    score_cpu=score_cpu,
                    score_gpu=score_gpu,
                    score_ram=score_ram,
                    score_storage=score_storage,
                    componentes=[cpu_comp, gpu_comp, ram_comp, storage_comp],
                    resumo=resumo,
                )

                # Salva no banco
                self._salvar(cur, specs, cpu_match, gpu_match, app, resultado)
                conn.commit()

        finally:
            conn.close()

        return resultado

    def _salvar(self, cur, specs: SpecsPC, cpu_match, gpu_match, app: dict, resultado: ResultadoComparacao):
        cur.execute("""
            INSERT INTO scans_pc (
                so, so_versao, cpu_modelo, cpu_nucleos, cpu_threads,
                cpu_clock_ghz, cpu_id, gpu_modelo, gpu_vram_gb, gpu_id,
                ram_total_gb, ram_velocidade_mhz, ram_tipo,
                storage_livre_gb, storage_total_gb, directx_versao
            ) VALUES (
                %(so)s,%(so_versao)s,%(cpu_modelo)s,%(cpu_nucleos)s,%(cpu_threads)s,
                %(cpu_clock_ghz)s,%(cpu_id)s,%(gpu_modelo)s,%(gpu_vram_gb)s,%(gpu_id)s,
                %(ram_total_gb)s,%(ram_velocidade_mhz)s,%(ram_tipo)s,
                %(storage_livre_gb)s,%(storage_total_gb)s,%(directx_versao)s
            ) RETURNING id
        """, {
            **asdict(specs),
            "cpu_id": cpu_match["id"] if cpu_match else None,
            "gpu_id": gpu_match["id"] if gpu_match else None,
        })
        scan_id = cur.fetchone()["id"]

        detalhes = {c.componente: {"score": c.score, "status": c.status} for c in resultado.componentes}
        cur.execute("""
            INSERT INTO comparacoes (scan_id, app_id, nivel, score_cpu, score_gpu,
                score_ram, score_storage, score_geral, detalhes)
            VALUES (%(scan_id)s, %(app_id)s, %(nivel)s, %(score_cpu)s, %(score_gpu)s,
                %(score_ram)s, %(score_storage)s, %(score_geral)s, %(detalhes)s)
        """, {
            "scan_id": scan_id,
            "app_id": app["id"],
            "nivel": resultado.nivel_geral,
            "score_cpu": resultado.score_cpu,
            "score_gpu": resultado.score_gpu,
            "score_ram": resultado.score_ram,
            "score_storage": resultado.score_storage,
            "score_geral": resultado.score_geral,
            "detalhes": json.dumps(detalhes),
        })
        log.info(f"Resultado salvo. Scan ID: {scan_id}")


# ─────────────────────────────────────────
#  Exibição formatada no terminal
# ─────────────────────────────────────────

ICONES = {
    "ÓTIMO":        "✅",
    "RECOMENDADO":  "🟢",
    "MÍNIMO":       "🟡",
    "INSUFICIENTE": "🔴",
}
NIVEL_ICONE = {
    "ABAIXO_MINIMO": "🔴",
    "MINIMO":        "🟡",
    "RECOMENDADO":   "🟢",
    "ACIMA":         "✅",
}

def imprimir_resultado(r: ResultadoComparacao):
    print("\n" + "═" * 60)
    print(f"  COMPATIBILIDADE: {r.app_nome}")
    print("═" * 60)
    print(f"  {NIVEL_ICONE[r.nivel_geral]}  Nível geral : {r.nivel_geral.replace('_', ' ')}")
    print(f"  📊  Score geral : {r.score_geral}/100")
    print(f"\n  {r.resumo}\n")
    print("─" * 60)
    print(f"  {'Componente':<16} {'Você':<30} {'Mín.':<12} {'Rec.':<12} Score")
    print("─" * 60)
    for c in r.componentes:
        icone = ICONES.get(c.status, "⚪")
        print(f"  {icone} {c.componente:<14} {c.valor_usuario[:28]:<30} {c.valor_minimo[:10]:<12} {c.valor_recomendado[:10]:<12} {c.score}")
        if c.observacao:
            print(f"     └─ {c.observacao}")
    print("═" * 60 + "\n")


# ─────────────────────────────────────────
#  CLI
# ─────────────────────────────────────────
def main():
    parser = argparse.ArgumentParser(description="Comparador de compatibilidade de hardware")
    parser.add_argument("--app", required=True, help="Nome do aplicativo a comparar")
    parser.add_argument("--json", action="store_true", help="Saída em JSON")
    parser.add_argument("--demo", action="store_true",
                        help="Usa specs de demo sem escanear o PC real")
    args = parser.parse_args()

    specs_demo = None
    if args.demo:
        # Specs simuladas para testes sem banco
        specs_demo = SpecsPC(
            so="Windows", so_versao="10.0.19045",
            cpu_modelo="Intel Core i7-12700K",
            cpu_nucleos=12, cpu_threads=20, cpu_clock_ghz=3.6,
            gpu_modelo="NVIDIA GeForce RTX 3070",
            gpu_vram_gb=8,
            ram_total_gb=16, ram_velocidade_mhz=3200, ram_tipo="DDR4",
            storage_livre_gb=120, storage_total_gb=500,
            directx_versao="12",
        )

    comparador = Comparador()
    resultado  = comparador.analisar(app_nome=args.app, specs_manuais=specs_demo)

    if args.json:
        import dataclasses
        print(json.dumps(dataclasses.asdict(resultado), indent=2, default=str))
    else:
        imprimir_resultado(resultado)


if __name__ == "__main__":
    main()
