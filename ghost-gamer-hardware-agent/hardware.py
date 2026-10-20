"""
Ghost Gamer Hardware Agent — coleta de hardware (hardware.py)
==============================================================

Responsável SOMENTE por ler informações de hardware do Windows e devolver
um dicionário Python no mesmo formato do modelo `SpecsUsuario` usado pela
API FastAPI (robo-hardware/05_api.py, POST /comparar).

Como é coletado
---------------
Tudo é lido via `Get-CimInstance` (PowerShell), por exigência explícita do
projeto: NUNCA usamos `wmic` (obsoleto e removido em versões recentes do
Windows). Cada consulta roda em um processo `powershell.exe` isolado, com
timeout, e o resultado vem em JSON (`ConvertTo-Json`) -- nada de parsing de
texto frágil.

Nenhuma função aqui aceita entrada vinda de fora (HTTP, usuário, etc.) --
todos os comandos executados são strings fixas, escritas neste arquivo.
Isso é proposital: o Agent só LÊ hardware, nunca executa comandos
arbitrários (ver PARTE 13 do pedido / seção "Segurança" do README).

Campos que não puderem ser obtidos com confiança voltam como `None`
(-> `null` no JSON) em vez de um valor inventado -- ver cada função abaixo
para o motivo específico de cada campo.
"""

from __future__ import annotations

import json
import re
import shutil
import subprocess
from pathlib import Path
from typing import Any, Optional

TIMEOUT_PADRAO_SEG = 8


# ─────────────────────────────────────────────────────────────
#  Infra: rodar PowerShell e sempre receber JSON
# ─────────────────────────────────────────────────────────────

def _powershell_disponivel() -> Optional[str]:
    """Localiza o executável do PowerShell (pwsh.exe ou Windows PowerShell)."""
    for nome in ("powershell.exe", "pwsh.exe"):
        caminho = shutil.which(nome)
        if caminho:
            return caminho
    return None


def _run_ps_json(comando: str, timeout: int = TIMEOUT_PADRAO_SEG) -> Any:
    """
    Executa um comando PowerShell que termina em `| ConvertTo-Json` e
    devolve o resultado já desserializado (dict, list, str, int... ou
    None se der qualquer problema: PowerShell ausente, timeout, comando
    sem saída, JSON inválido etc.).

    Importante: `comando` é sempre uma string FIXA definida nas funções
    abaixo -- nunca algo vindo de uma requisição HTTP. O Agent não expõe
    nenhuma forma de executar comandos arbitrários.
    """
    exe = _powershell_disponivel()
    if not exe:
        return None

    try:
        resultado = subprocess.run(
            [
                exe,
                "-NoProfile",
                "-NonInteractive",
                "-ExecutionPolicy", "Bypass",
                "-Command", comando,
            ],
            capture_output=True,
            text=True,
            timeout=timeout,
            creationflags=subprocess.CREATE_NO_WINDOW if hasattr(subprocess, "CREATE_NO_WINDOW") else 0,
        )
    except (subprocess.TimeoutExpired, OSError):
        return None

    saida = (resultado.stdout or "").strip()
    if not saida:
        return None

    try:
        return json.loads(saida)
    except json.JSONDecodeError:
        return None


def _como_lista(valor: Any) -> list:
    """
    `ConvertTo-Json` devolve um OBJETO quando a consulta retorna um único
    item (por exemplo, um PC com um único pente de RAM) e um ARRAY quando
    retorna vários. Esta função normaliza os dois casos para sempre
    trabalharmos com uma lista.
    """
    if valor is None:
        return []
    if isinstance(valor, list):
        return valor
    return [valor]


# ─────────────────────────────────────────────────────────────
#  Sistema operacional
# ─────────────────────────────────────────────────────────────

def coletar_so() -> dict:
    dados = _run_ps_json(
        "Get-CimInstance Win32_OperatingSystem | "
        "Select-Object Caption, Version, OSArchitecture | ConvertTo-Json"
    )
    if not dados:
        return {"so": "Windows", "so_versao": None}

    caption = (dados.get("Caption") or "").strip()
    versao = (dados.get("Version") or "").strip() or None
    return {
        "so": "Windows",
        # Ex.: "Microsoft Windows 11 Pro (10.0.22631)" -- mantemos o nome
        # legível (Caption) e o número de versão juntos, pois o endpoint
        # /comparar trata "so_versao" como texto livre, não como número.
        "so_versao": f"{caption} ({versao})".strip() if caption else versao,
    }


# ─────────────────────────────────────────────────────────────
#  CPU
# ─────────────────────────────────────────────────────────────

def coletar_cpu() -> dict:
    dados = _run_ps_json(
        "Get-CimInstance Win32_Processor | Select-Object Name, "
        "NumberOfCores, NumberOfLogicalProcessors, MaxClockSpeed | ConvertTo-Json"
    )
    item = _como_lista(dados)
    item = item[0] if item else {}

    modelo = (item.get("Name") or "").strip() or None
    nucleos = item.get("NumberOfCores")
    threads = item.get("NumberOfLogicalProcessors")

    # NOTA (PARTE 9 do pedido): `MaxClockSpeed` é a frequência MÁXIMA
    # NOMINAL do processador em MHz, conforme reportada pela BIOS/UEFI --
    # é o valor usado aqui de propósito. NÃO usamos `CurrentClockSpeed`,
    # que varia a cada instante (throttling, economia de energia, boost),
    # pois comparar um valor instantâneo com o requisito mínimo de um
    # jogo (que é expresso como "frequência base/turbo esperada") daria
    # um resultado instável e pouco confiável.
    clock_mhz = item.get("MaxClockSpeed")
    clock_ghz = round(clock_mhz / 1000, 2) if isinstance(clock_mhz, (int, float)) and clock_mhz else None

    return {
        "cpu_modelo": modelo,
        "cpu_nucleos": int(nucleos) if isinstance(nucleos, (int, float)) else None,
        "cpu_threads": int(threads) if isinstance(threads, (int, float)) else None,
        "cpu_clock_ghz": clock_ghz,
    }


# ─────────────────────────────────────────────────────────────
#  GPU
# ─────────────────────────────────────────────────────────────

# `Win32_VideoController.AdapterRAM` é um campo de 32 bits (DWORD) e, por
# uma limitação antiga e conhecida do Windows, estoura (overflow) em
# praticamente toda GPU com 4 GB de VRAM ou mais -- é comum ver "4 GB"
# reportados para uma placa de 8, 12 ou 16 GB, ou até valores negativos.
# Por isso tentamos primeiro o registro (HardwareInformation.qwMemorySize,
# um QWORD de 64 bits escrito pelo driver), que é a forma mais confiável
# e documentada publicamente de contornar esse bug (PARTE 10 do pedido:
# "se o método do Windows tiver limitações, procure uma alternativa").
_PS_GPU = r"""
$ErrorActionPreference = 'SilentlyContinue'
$ctrl = Get-CimInstance Win32_VideoController | Select-Object -First 1
$nome = $ctrl.Name
$ramCim = $ctrl.AdapterRAM

$vramRegistro = $null
$chaveBase = 'HKLM:\SYSTEM\ControlSet001\Control\Class\{4d36e968-e325-11ce-bfc1-08002be10318}'
if (Test-Path $chaveBase) {
    Get-ChildItem $chaveBase -ErrorAction SilentlyContinue | ForEach-Object {
        $valor = Get-ItemProperty -Path $_.PSPath -Name 'HardwareInformation.qwMemorySize' -ErrorAction SilentlyContinue
        if ($valor -and $valor.'HardwareInformation.qwMemorySize' -gt 0) {
            $script:vramRegistro = $valor.'HardwareInformation.qwMemorySize'
        }
    }
}

[PSCustomObject]@{
    Nome          = $nome
    AdapterRAM    = $ramCim
    VramRegistro  = $vramRegistro
} | ConvertTo-Json
"""


def coletar_gpu() -> dict:
    dados = _run_ps_json(_PS_GPU)
    item = _como_lista(dados)
    item = item[0] if item else {}

    modelo = (item.get("Nome") or "").strip() or None

    vram_gb = None
    vram_registro = item.get("VramRegistro")
    if isinstance(vram_registro, (int, float)) and vram_registro > 0:
        vram_gb = round(vram_registro / (1024 ** 3))
    else:
        ram_cim = item.get("AdapterRAM")
        # AdapterRAM negativo ou None = valor não confiável (overflow do
        # DWORD citado acima) -> não inventamos, devolvemos null.
        if isinstance(ram_cim, (int, float)) and ram_cim > 0:
            vram_gb = round(ram_cim / (1024 ** 3))

    return {
        "gpu_modelo": modelo or "",
        "gpu_vram_gb": vram_gb if vram_gb is not None else 0,
    }


# ─────────────────────────────────────────────────────────────
#  RAM
# ─────────────────────────────────────────────────────────────

# Mapa dos códigos numéricos de tipo de memória usados pela WMI/CIM
# (SMBIOSMemoryType é o mais novo/confiável; MemoryType é o campo legado,
# que em muitas máquinas modernas vem "0 - Unknown"). Valores conforme a
# especificação SMBIOS (Type 17 - Memory Device).
_TIPOS_RAM = {
    20: "DDR", 21: "DDR2", 22: "DDR2 FB-DIMM", 24: "DDR3",
    26: "DDR4", 34: "DDR5",
}


def coletar_ram() -> dict:
    dados = _run_ps_json(
        "Get-CimInstance Win32_PhysicalMemory | "
        "Select-Object Capacity, Speed, SMBIOSMemoryType, MemoryType | ConvertTo-Json"
    )
    pentes = _como_lista(dados)

    total_bytes = sum(
        int(p["Capacity"]) for p in pentes
        if isinstance(p.get("Capacity"), (int, float, str)) and str(p.get("Capacity")).isdigit()
    )
    ram_total_gb = round(total_bytes / (1024 ** 3)) if total_bytes else None

    velocidade = None
    tipo = None
    if pentes:
        primeiro = pentes[0]
        vel = primeiro.get("Speed")
        velocidade = int(vel) if isinstance(vel, (int, float)) else None

        codigo = primeiro.get("SMBIOSMemoryType") or primeiro.get("MemoryType")
        tipo = _TIPOS_RAM.get(codigo) if isinstance(codigo, (int, float)) else None

    return {
        "ram_total_gb": ram_total_gb,
        "ram_velocidade_mhz": velocidade,
        "ram_tipo": tipo,
    }


# ─────────────────────────────────────────────────────────────
#  Armazenamento
# ─────────────────────────────────────────────────────────────

def coletar_armazenamento() -> dict:
    """
    A API (SpecsUsuario) espera UM único valor de espaço livre/total, não
    uma lista por unidade de disco (PARTE 12 do pedido). Usamos a unidade
    onde o Windows está instalado (variável de ambiente SystemDrive, em
    geral "C:") por ser a referência mais previsível de "espaço
    disponível para instalar o jogo".
    """
    dados = _run_ps_json(
        "$u = $env:SystemDrive; "
        "Get-CimInstance Win32_LogicalDisk -Filter \"DeviceID='$u'\" | "
        "Select-Object FreeSpace, Size | ConvertTo-Json"
    )
    item = _como_lista(dados)
    item = item[0] if item else {}

    livre = item.get("FreeSpace")
    total = item.get("Size")

    return {
        "storage_livre_gb": round(int(livre) / (1024 ** 3)) if isinstance(livre, (int, float, str)) and str(livre).isdigit() else None,
        "storage_total_gb": round(int(total) / (1024 ** 3)) if isinstance(total, (int, float, str)) and str(total).isdigit() else 0,
    }


# ─────────────────────────────────────────────────────────────
#  DirectX
# ─────────────────────────────────────────────────────────────

def coletar_directx() -> Optional[str]:
    """
    Não existe uma classe CIM/WMI confiável para "versão do DirectX"
    (a antiga chave de registro `HKLM\\SOFTWARE\\Microsoft\\DirectX\\Version`
    parou de ser atualizada ainda na era do DirectX 9/10 e não reflete mais
    nada em Windows 10/11). A forma tradicional de obter isso de verdade é
    rodar `dxdiag /t arquivo.txt` e ler o arquivo -- lento (vários segundos)
    e pensado para diagnóstico manual, não para uma chamada de API.

    Como heurística honesta e documentada (PARTE 8.14: "se não puder obter
    de forma confiável, retorne null, não invente"), verificamos apenas se
    as DLLs de runtime do Direct3D 12/11 existem em System32. Isso indica
    com segurança qual versão do RUNTIME está disponível no Windows (todo
    Windows 10/11 tem DirectX 12), mas não garante que uma GPU/driver
    específico suporte o nível de recursos completo dessa versão.
    """
    system32 = Path(r"C:\Windows\System32")
    try:
        if (system32 / "d3d12.dll").exists():
            return "12"
        if (system32 / "d3d11.dll").exists():
            return "11"
    except OSError:
        pass
    return None


# ─────────────────────────────────────────────────────────────
#  Função principal
# ─────────────────────────────────────────────────────────────

def coletar_tudo() -> dict:
    """Coleta tudo e devolve no formato esperado por POST /comparar (specs)."""
    especificacoes: dict = {}
    especificacoes.update(coletar_so())
    especificacoes.update(coletar_cpu())
    especificacoes.update(coletar_gpu())
    especificacoes.update(coletar_ram())
    especificacoes.update(coletar_armazenamento())
    especificacoes["directx_versao"] = coletar_directx()

    # Garante que todas as chaves do contrato existam mesmo que alguma
    # coleta tenha falhado silenciosamente (ex.: PowerShell indisponível).
    padrao = {
        "so": "Windows", "so_versao": None,
        "cpu_modelo": None, "cpu_nucleos": None, "cpu_threads": None, "cpu_clock_ghz": None,
        "gpu_modelo": "", "gpu_vram_gb": 0,
        "ram_total_gb": None, "ram_velocidade_mhz": None, "ram_tipo": None,
        "storage_livre_gb": None, "storage_total_gb": 0,
        "directx_versao": None,
    }
    padrao.update({k: v for k, v in especificacoes.items() if v is not None})
    return padrao


if __name__ == "__main__":
    # Teste manual: `python hardware.py` imprime o JSON coletado.
    print(json.dumps(coletar_tudo(), indent=2, ensure_ascii=False))
