<?php
/**
 * URL pública do painel do robô de compatibilidade de hardware
 * (pasta /robo-hardware/painel/index.html, servida por Docker/nginx
 * ou qualquer outro servidor -- ver README do robô).
 *
 * Deixe como "" para ocultar o link de "verificação avançada" em
 * telaJogo.php e usar apenas o verificador local (que já funciona
 * sem nenhuma dependência extra).
 *
 * Exemplo depois de rodar `docker compose up` na pasta do robô:
 *   define("HARDWARE_PAINEL_URL", "http://localhost:8080");
 */

define("HARDWARE_PAINEL_URL", "");

/**
 * URL da API de comparação de hardware (robo-hardware/05_api.py,
 * endpoint POST /comparar). Usada por ASSETS/JS/hardwareAgent.js quando
 * o usuário clica em "Verificar meu PC" em telaJogo.php, e também por
 * XAMP/sincronizarRobo.php / sincronizar_jogos_robo.php.
 *
 * ===========================================================
 *  "http://localhost:8000" SÓ FUNCIONA NA SUA MÁQUINA DE DESENVOLVIMENTO
 * ===========================================================
 * "localhost" aqui quer dizer "o computador que está executando o
 * navegador de quem está acessando o site". Enquanto o Ghost Gamer e a
 * API rodam as duas na SUA máquina (XAMPP local), isso funciona. Mas
 * quando o SITE for hospedado na internet, o navegador de cada visitante
 * vai tentar abrir "http://localhost:8000" dentro do PRÓPRIO computador
 * dele (onde não existe nada rodando ali) -- a verificação automática
 * simplesmente vai falhar com "serviço indisponível" para todo mundo.
 *
 * Antes de publicar o site, troque o valor abaixo pelo endereço real
 * (público, com HTTPS) de onde a API robo-hardware/05_api.py estiver
 * hospedada, por exemplo:
 *   define("HARDWARE_API_URL", "https://api-hardware.seudominio.com.br");
 *
 * Este projeto não assume nem inventa esse domínio -- ver o README de
 * entrega (seção "Hospedagem da API") para as opções de onde hospedar
 * uma API FastAPI + PostgreSQL e o que precisa ser publicado.
 */
define("HARDWARE_API_URL", "http://localhost:8000");

/**
 * URL do Ghost Gamer Hardware Agent -- programa separado que roda no
 * Windows do usuário (fora do Docker) e expõe o hardware real do PC em
 * /health e /hardware. Ver pasta ghost-gamer-hardware-agent/ (raiz do
 * projeto) e seu README.md para como rodar/gerar o .exe.
 *
 * Não precisa alterar este valor: o Agent sempre escuta em 127.0.0.1
 * (loopback), então esta URL é a mesma para qualquer usuário.
 */
define("HARDWARE_AGENT_URL", "http://127.0.0.1:8765");
