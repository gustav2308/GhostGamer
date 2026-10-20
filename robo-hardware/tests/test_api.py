"""
Testes básicos da Hardware API.
Rodam contra o Postgres de teste que o CI já sobe e popula com
01_banco_de_dados.sql (ver deploy.yml).
"""

SPECS_PC_FORTE = {
    "so": "Windows 11", "so_versao": "23H2",
    "cpu_modelo": "Core i7-12700K", "cpu_nucleos": 12, "cpu_threads": 20, "cpu_clock_ghz": 3.6,
    "gpu_modelo": "RTX 3070", "gpu_vram_gb": 8,
    "ram_total_gb": 16, "ram_velocidade_mhz": 3200, "ram_tipo": "DDR4",
    "storage_livre_gb": 100, "storage_total_gb": 500,
}


def test_raiz_responde_ok(client):
    resposta = client.get("/")
    assert resposta.status_code == 200


def test_health_check(client):
    resposta = client.get("/health")
    assert resposta.status_code == 200
    corpo = resposta.json()
    assert corpo["status"] in ("ok", "erro")


def test_listar_apps(client):
    resposta = client.get("/apps")
    assert resposta.status_code == 200
    apps = resposta.json()["apps"]
    assert isinstance(apps, list)
    nomes = [a["nome"] for a in apps]
    assert "Cyberpunk 2077" in nomes


def test_listar_cpus(client):
    resposta = client.get("/cpus")
    assert resposta.status_code == 200
    assert isinstance(resposta.json()["cpus"], list)


def test_listar_gpus(client):
    resposta = client.get("/gpus")
    assert resposta.status_code == 200
    assert isinstance(resposta.json()["gpus"], list)


def test_comparar_app_inexistente_retorna_404(client):
    payload = {"specs": SPECS_PC_FORTE, "app_nome": "Um Jogo Que Nao Existe", "salvar": False}
    resposta = client.post("/comparar", json=payload)
    assert resposta.status_code == 404


def test_comparar_specs_incompletas_retorna_erro_de_validacao(client):
    payload = {"specs": {"so": "Windows 11"}, "app_nome": "Cyberpunk 2077"}
    resposta = client.post("/comparar", json=payload)
    assert resposta.status_code == 422


def test_comparar_cyberpunk_com_pc_forte(client):
    payload = {"specs": SPECS_PC_FORTE, "app_nome": "Cyberpunk 2077", "salvar": False}
    resposta = client.post("/comparar", json=payload)
    assert resposta.status_code == 200
    corpo = resposta.json()
    assert "score_geral" in corpo
    assert 0 <= corpo["score_geral"] <= 100
    assert corpo["nivel_geral"] in ("INSUFICIENTE", "MÍNIMO", "RECOMENDADO", "ÓTIMO")
