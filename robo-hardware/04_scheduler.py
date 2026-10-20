"""
=============================================================
 AGENDADOR AUTOMÁTICO — scheduler.py
 Roda o robô de CPUs + GPUs em horários programados,
 envia notificações e mantém log de execuções.
=============================================================

Instalação:
    pip install apscheduler requests python-dotenv

Uso:
    python 04_scheduler.py               # roda em loop contínuo
    python 04_scheduler.py --agora       # executa imediatamente e sai
    python 04_scheduler.py --listar      # lista próximas execuções
"""

import os
import sys
import json
import logging
import argparse
import subprocess
from datetime import datetime
from pathlib import Path

import requests
from dotenv import load_dotenv
from apscheduler.schedulers.blocking import BlockingScheduler
from apscheduler.schedulers.background import BackgroundScheduler
from apscheduler.triggers.cron import CronTrigger

load_dotenv()

# ─────────────────────────────────────────
#  Configuração de log
# ─────────────────────────────────────────
LOG_FILE = Path("logs/scheduler.log")
LOG_FILE.parent.mkdir(exist_ok=True)

logging.basicConfig(
    level=logging.INFO,
    format="%(asctime)s [%(levelname)s] %(message)s",
    handlers=[
        logging.FileHandler(LOG_FILE, encoding="utf-8"),
        logging.StreamHandler(),
    ],
)
log = logging.getLogger("Scheduler")

# ─────────────────────────────────────────
#  Configurações via .env
# ─────────────────────────────────────────
WEBHOOK_SLACK   = os.getenv("SLACK_WEBHOOK_URL", "")       # notificação Slack
WEBHOOK_DISCORD = os.getenv("DISCORD_WEBHOOK_URL", "")     # notificação Discord
EMAIL_TO        = os.getenv("NOTIFY_EMAIL", "")            # e-mail de alerta
ROBO_SCRIPT     = Path(os.getenv("ROBO_SCRIPT", "02_robo_cadastro_cpus.py"))
GPU_ROBO_SCRIPT = Path(os.getenv("GPU_ROBO_SCRIPT", "02b_robo_cadastro_gpus.py"))
PYTHON_BIN      = os.getenv("PYTHON_BIN", sys.executable)


# ═══════════════════════════════════════════════════════════════
#  NOTIFICAÇÕES
# ═══════════════════════════════════════════════════════════════

def notificar(titulo: str, mensagem: str, erro: bool = False):
    """Envia notificação para Slack e/ou Discord."""
    emoji = "❌" if erro else "✅"
    payload_slack = {
        "text": f"{emoji} *{titulo}*\n{mensagem}",
        "attachments": [{
            "color": "danger" if erro else "good",
            "text": mensagem,
        }]
    }
    if WEBHOOK_SLACK:
        try:
            requests.post(WEBHOOK_SLACK, json=payload_slack, timeout=5)
            log.info("Notificação Slack enviada.")
        except Exception as e:
            log.warning(f"Slack falhou: {e}")

    payload_discord = {
        "content": f"{emoji} **{titulo}**",
        "embeds": [{"description": mensagem, "color": 15158332 if erro else 3066993}]
    }
    if WEBHOOK_DISCORD:
        try:
            requests.post(WEBHOOK_DISCORD, json=payload_discord, timeout=5)
            log.info("Notificação Discord enviada.")
        except Exception as e:
            log.warning(f"Discord falhou: {e}")


# ═══════════════════════════════════════════════════════════════
#  JOBS AGENDADOS
# ═══════════════════════════════════════════════════════════════

def job_atualizar_intel():
    """Atualiza CPUs Intel (todas as gerações). Roda toda segunda-feira às 03:00."""
    _rodar_robo(fabricante="intel", descricao="Intel — todas as gerações")

def job_atualizar_amd():
    """Atualiza CPUs AMD (todas as gerações). Roda toda terça-feira às 03:00."""
    _rodar_robo(fabricante="amd", descricao="AMD — todas as gerações")

def job_atualizar_gpus():
    """Atualiza GPUs NVIDIA + AMD. Roda toda quarta-feira às 03:00."""
    _rodar_robo(fabricante="todos", descricao="GPUs — NVIDIA e AMD", script=GPU_ROBO_SCRIPT)

def job_atualizar_completo():
    """Atualização completa mensal (Intel + AMD + GPUs). Roda todo dia 1 às 02:00."""
    _rodar_robo(fabricante="todos", descricao="Atualização completa mensal (CPUs)")
    _rodar_robo(fabricante="todos", descricao="Atualização completa mensal (GPUs)", script=GPU_ROBO_SCRIPT)

def job_verificar_saude():
    """Verifica se o banco está acessível. Roda a cada 6 horas."""
    import psycopg2
    from dotenv import load_dotenv
    load_dotenv()

    try:
        conn = psycopg2.connect(
            host=os.getenv("DB_HOST", "localhost"),
            port=int(os.getenv("DB_PORT", 5432)),
            dbname=os.getenv("DB_NAME", "hardware_db"),
            user=os.getenv("DB_USER", "postgres"),
            password=os.getenv("DB_PASSWORD", ""),
            connect_timeout=5,
        )
        with conn.cursor() as cur:
            cur.execute("SELECT COUNT(*) FROM cpus WHERE ativo = TRUE")
            total = cur.fetchone()[0]
        conn.close()
        log.info(f"[Health Check] OK — {total} CPUs ativas no banco.")
    except Exception as e:
        log.error(f"[Health Check] FALHOU: {e}")
        notificar(
            titulo="Health Check FALHOU",
            mensagem=f"O banco de dados não está acessível.\nErro: {e}",
            erro=True,
        )


# ─────────────────────────────────────────
#  Executor do robô (subprocess)
# ─────────────────────────────────────────

def _rodar_robo(fabricante: str, descricao: str, script: Path = None):
    inicio = datetime.now()
    log.info(f"[Job] Iniciando: {descricao}")

    cmd = [
        PYTHON_BIN,
        str(script or ROBO_SCRIPT),
        "--fabricante", fabricante,
        "--todos",
    ]

    try:
        resultado = subprocess.run(
            cmd,
            capture_output=True,
            text=True,
            timeout=3600,  # 1h de timeout máximo
        )

        duracao = int((datetime.now() - inicio).total_seconds())
        saida   = resultado.stdout[-800:] if resultado.stdout else "(sem saída)"

        if resultado.returncode == 0:
            log.info(f"[Job] {descricao} concluído em {duracao}s.")
            notificar(
                titulo=f"Robô concluído: {descricao}",
                mensagem=f"Duração: {duracao}s\n\nLog:\n```\n{saida}\n```",
            )
        else:
            erro = resultado.stderr[-500:] if resultado.stderr else "(sem stderr)"
            log.error(f"[Job] {descricao} falhou (código {resultado.returncode}).")
            notificar(
                titulo=f"Robô FALHOU: {descricao}",
                mensagem=f"Código de saída: {resultado.returncode}\n\nErro:\n```\n{erro}\n```",
                erro=True,
            )

    except subprocess.TimeoutExpired:
        log.error(f"[Job] {descricao} excedeu o timeout de 1h.")
        notificar(titulo=f"Timeout: {descricao}", mensagem="O robô demorou mais de 1h.", erro=True)

    except Exception as e:
        log.error(f"[Job] Exceção inesperada em {descricao}: {e}")
        notificar(titulo=f"Erro inesperado: {descricao}", mensagem=str(e), erro=True)


# ═══════════════════════════════════════════════════════════════
#  AGENDAMENTO
# ═══════════════════════════════════════════════════════════════

def criar_scheduler() -> BlockingScheduler:
    scheduler = BlockingScheduler(timezone="America/Sao_Paulo")

    # ── Intel: toda segunda-feira às 03:00 ─────────────────────
    scheduler.add_job(
        job_atualizar_intel,
        CronTrigger(day_of_week="mon", hour=3, minute=0),
        id="intel_semanal",
        name="Atualizar CPUs Intel",
        misfire_grace_time=3600,    # tolera até 1h de atraso
        replace_existing=True,
    )

    # ── AMD: toda terça-feira às 03:00 ─────────────────────────
    scheduler.add_job(
        job_atualizar_amd,
        CronTrigger(day_of_week="tue", hour=3, minute=0),
        id="amd_semanal",
        name="Atualizar CPUs AMD",
        misfire_grace_time=3600,
        replace_existing=True,
    )

    # ── GPUs: toda quarta-feira às 03:00 ───────────────────────
    scheduler.add_job(
        job_atualizar_gpus,
        CronTrigger(day_of_week="wed", hour=3, minute=0),
        id="gpu_semanal",
        name="Atualizar GPUs (NVIDIA + AMD)",
        misfire_grace_time=3600,
        replace_existing=True,
    )

    # ── Completo: todo dia 1 do mês às 02:00 ───────────────────
    scheduler.add_job(
        job_atualizar_completo,
        CronTrigger(day=1, hour=2, minute=0),
        id="completo_mensal",
        name="Atualização completa mensal",
        misfire_grace_time=7200,
        replace_existing=True,
    )

    # ── Health check: a cada 6 horas ───────────────────────────
    scheduler.add_job(
        job_verificar_saude,
        CronTrigger(hour="*/6", minute=0),
        id="health_check",
        name="Health check banco de dados",
        replace_existing=True,
    )

    return scheduler


# ═══════════════════════════════════════════════════════════════
#  CLI
# ═══════════════════════════════════════════════════════════════

def listar_jobs(scheduler: BlockingScheduler):
    print("\nPróximas execuções agendadas:")
    print("─" * 55)
    for job in scheduler.get_jobs():
        proxima = job.next_run_time
        proxima_str = proxima.strftime("%d/%m/%Y %H:%M") if proxima else "—"
        print(f"  {job.name:<35} → {proxima_str}")
    print()


def main():
    parser = argparse.ArgumentParser(description="Agendador automático do sistema de hardware")
    parser.add_argument("--agora",   action="store_true", help="Executa todos os jobs agora e sai")
    parser.add_argument("--listar",  action="store_true", help="Lista próximas execuções e sai")
    parser.add_argument("--saude",   action="store_true", help="Roda só o health check e sai")
    args = parser.parse_args()

    if args.saude:
        job_verificar_saude()
        return

    if args.agora:
        log.info("Modo --agora: executando todos os jobs imediatamente.")
        job_verificar_saude()
        job_atualizar_intel()
        job_atualizar_amd()
        log.info("Todos os jobs concluídos.")
        return

    scheduler = criar_scheduler()

    if args.listar:
        scheduler.start()          # precisa iniciar para calcular próxima execução
        listar_jobs(scheduler)
        scheduler.shutdown()
        return

    log.info("Agendador iniciado. Pressione Ctrl+C para parar.")
    log.info(f"Fuso horário: America/Sao_Paulo")

    try:
        listar_jobs(scheduler)
        scheduler.start()
    except (KeyboardInterrupt, SystemExit):
        log.info("Agendador encerrado.")


if __name__ == "__main__":
    main()
