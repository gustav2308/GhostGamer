# Robô de Compatibilidade de Hardware — Guia rápido

## Como rodar (com Docker, mais fácil)

```bash
cp .env.example .env
# edite o .env se quiser configurar Slack/Discord/e-mail (opcional)

docker compose up -d
```

Isso sobe 4 serviços:
- **PostgreSQL** na porta 5432 (já criado e populado com `01_banco_de_dados.sql`)
- **API** (FastAPI) na porta 8000 -- documentação automática em http://localhost:8000/docs
- **Painel web** na porta 8080 -- http://localhost:8080
- **Agendador** rodando em segundo plano (atualiza CPUs/GPUs automaticamente todo dia 1, e semanalmente)

Para ver o pgAdmin (interface visual do banco), rode:
```bash
docker compose --profile dev up -d
```
e acesse http://localhost:5050.

## Rodando os robôs manualmente

```bash
python 02_robo_cadastro_cpus.py --fabricante todos --todos
python 02b_robo_cadastro_gpus.py --fabricante todos --todos
```

Use `--dry-run` para ver o que seria cadastrado sem gravar no banco.

## Rodando os testes

```bash
pip install -r requirements-dev.txt
pytest tests/ -v
```

## Conectando ao site PHP

No arquivo `site-ghost-gamer/XAMP/config_hardware.php`, defina:

```php
define("HARDWARE_PAINEL_URL", "http://localhost:8080");
```

Isso faz aparecer um link de "Verificação avançada pelo robô oficial"
na página de cada jogo (`telaJogo.php`), que abre o painel já com o
jogo selecionado.

**Importante**: para o robô encontrar um jogo pelo nome, ele precisa
existir na tabela `aplicativos` do banco `hardware_db` (Postgres) --
esse cadastro é independente da tabela `jogo` do site (MySQL). Ainda
não existe uma sincronização automática entre os dois bancos; por
enquanto, cadastre os jogos manualmente em `aplicativos` com o mesmo
nome usado em `jogo.titulo` no site.

## O que foi corrigido nesta versão

Veja o `LEIA-ME_GERAL.md` na pasta raiz da entrega para a lista
completa. Resumo rápido: robô de GPUs (não existia), pool de conexões
na API, pasta de testes, workflow do GitHub Actions movido para o
lugar certo, bug de healthcheck no Docker, `.env.example` completo, e
o painel web (não existia).
