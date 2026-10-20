// =====================================================================
// GHOST GAMER HARDWARE AGENT — integração com a página do jogo
// =====================================================================
// Fluxo (clique em "Verificar meu PC automaticamente"):
//   1. GET  {HARDWARE_AGENT_URL}/health    -> o Agent está rodando?
//   2. GET  {HARDWARE_AGENT_URL}/hardware  -> specs reais do PC do usuário
//   3. POST {HARDWARE_API_URL}/comparar    -> compara com os requisitos do jogo
//   4. mostra o resultado
//
// Este arquivo NÃO reimplementa a lógica de comparação (ela já existe na
// API/robô de hardware) -- só busca os dados, chama a API e exibe o que
// ela devolve. Se o Agent ou a API estiverem fora do ar, mostra uma
// mensagem amigável e não quebra o restante da página (o verificador
// manual, em telajogo.js, continua funcionando normalmente).
// =====================================================================

(() => {
    const btn = document.getElementById("btnVerificarAgente");
    const statusEl = document.getElementById("statusAgente");
    const resultadoEl = document.getElementById("resultadoAgente");
    if (!btn || !statusEl || !resultadoEl) return;

    const AGENT_URL = window.HARDWARE_AGENT_URL || "http://127.0.0.1:8765";
    const API_URL = window.HARDWARE_API_URL || "http://localhost:8000";

    /** fetch() com tempo limite (a API nativa não tem isso embutido). */
    async function buscarComTimeout(url, opcoes = {}, timeoutMs = 5000) {
        const controlador = new AbortController();
        const timer = setTimeout(() => controlador.abort(), timeoutMs);
        try {
            return await fetch(url, { ...opcoes, signal: controlador.signal });
        } finally {
            clearTimeout(timer);
        }
    }

    function mostrarStatus(mensagem, tipo = "info") {
        statusEl.className = "status-agente " + tipo;
        statusEl.innerHTML = mensagem;
    }

    function limparStatus() {
        statusEl.className = "status-agente";
        statusEl.innerHTML = "";
    }

    function mensagemAgentAusente() {
        // O botão "Baixar Ghost Gamer Agent" já fica sempre visível logo
        // acima (ver telaJogo.php) -- aqui só explicamos por que é
        // necessário e o que fazer, sem duplicar o link.
        mostrarStatus(
            "O Ghost Gamer Agent não está em execução. Baixe o agente, execute o " +
            "<code>GhostGamerAgent.exe</code> e tente novamente. " +
            "Enquanto isso, você pode preencher os dados manualmente abaixo.",
            "aviso"
        );
    }

    // Campos que a API (SpecsUsuario) exige sempre preenchidos -- se o
    // Agent não conseguir ler algum deles no Windows do usuário, é melhor
    // avisar exatamente qual campo faltou do que deixar a API recusar o
    // envio com um erro genérico (ver PARTE 13 do pedido: "dados
    // incompletos" precisa de mensagem específica, não um erro técnico).
    const CAMPOS_OBRIGATORIOS = {
        cpu_modelo: "modelo do processador",
        cpu_nucleos: "núcleos do processador",
        cpu_threads: "threads do processador",
        cpu_clock_ghz: "frequência do processador",
        ram_total_gb: "memória RAM",
        storage_livre_gb: "espaço livre em disco",
    };

    function camposFaltando(specs) {
        return Object.entries(CAMPOS_OBRIGATORIOS)
            .filter(([campo]) => specs[campo] === null || specs[campo] === undefined || specs[campo] === "")
            .map(([, rotulo]) => rotulo);
    }

    // ------------------------- Renderização do resultado -------------------------

    // Valores reais de `nivel_geral` e `componente.status`, conforme
    // robo-hardware/03_comparacao.py (não são os mesmos textos nos dois
    // casos -- "MINIMO" sem acento no nível geral, "MÍNIMO" com acento
    // no componente -- por isso dois mapas separados).
    const ROTULOS_NIVEL = {
        ABAIXO_MINIMO: { texto: "Não atende aos requisitos mínimos", classe: "insuficiente" },
        MINIMO: { texto: "Atende ao mínimo (desempenho limitado)", classe: "minimo" },
        RECOMENDADO: { texto: "Atende aos requisitos recomendados", classe: "ok" },
        ACIMA: { texto: "Acima dos requisitos recomendados", classe: "ok" },
    };

    const CLASSE_STATUS_COMPONENTE = {
        INSUFICIENTE: "insuficiente",
        "MÍNIMO": "minimo",
        RECOMENDADO: "otimo",
        "ÓTIMO": "otimo",
    };

    function corStatusComponente(status) {
        return CLASSE_STATUS_COMPONENTE[status] || "minimo";
    }

    function renderizarResultado(laudo) {
        const nivel = ROTULOS_NIVEL[laudo.nivel_geral] || { texto: laudo.nivel_geral, classe: "minimo" };

        const linhasComponentes = (laudo.componentes || []).map((c) => `
            <div class="linha-compat ${corStatusComponente(c.status)}">
                <div class="linha-compat-topo">
                    <span>${c.componente}</span>
                    <span>${c.score}/100</span>
                </div>
                <div class="barra-compat"><div class="barra-compat-preenchida" style="width:${c.score}%"></div></div>
                <span class="linha-compat-status">Seu PC: ${c.valor_usuario} — mínimo: ${c.valor_minimo}</span>
            </div>
        `).join("");

        resultadoEl.innerHTML = `
            <p class="veredito-compat ${nivel.classe}">${nivel.texto}</p>
            <p class="agente-resumo">${laudo.resumo}</p>
            ${linhasComponentes}
        `;
        resultadoEl.classList.add("ativo");
    }

    // ------------------------------- Fluxo principal -------------------------------

    btn.addEventListener("click", async () => {
        // Lido no momento do clique (não no carregamento da página) por
        // segurança/robustez, ainda que na prática o PHP sempre renderize
        // este atributo uma vez só por carregamento de página.
        const tituloJogo = btn.dataset.jogoTitulo || "";

        btn.disabled = true;
        limparStatus();
        resultadoEl.classList.remove("ativo");
        resultadoEl.innerHTML = "";

        if (!tituloJogo) {
            mostrarStatus("Não foi possível identificar o jogo desta página.", "erro");
            btn.disabled = false;
            return;
        }

        // 1) O Agent está rodando?
        mostrarStatus("Procurando o Ghost Gamer Hardware Agent no seu computador...", "info");
        try {
            const respostaHealth = await buscarComTimeout(AGENT_URL + "/health", {}, 2000);
            if (!respostaHealth.ok) throw new Error("agent-indisponivel");
        } catch (erro) {
            mensagemAgentAusente();
            btn.disabled = false;
            return;
        }

        // 2) Lê o hardware real do PC
        mostrarStatus("Agent encontrado! Lendo as informações do seu computador...", "info");
        let specs;
        try {
            const respostaHardware = await buscarComTimeout(AGENT_URL + "/hardware", {}, 8000);
            if (!respostaHardware.ok) throw new Error("hardware-falhou");
            specs = await respostaHardware.json();
        } catch (erro) {
            mostrarStatus(
                "Não foi possível obter as informações do computador. " +
                "Verifique se o Ghost Gamer Agent está funcionando e tente novamente.",
                "erro"
            );
            btn.disabled = false;
            return;
        }

        // 2.1) Dados incompletos? Avisa exatamente o que faltou em vez de
        // deixar a API rejeitar o envio com um erro técnico.
        const faltando = camposFaltando(specs);
        if (faltando.length > 0) {
            mostrarStatus(
                "O Ghost Gamer Agent não conseguiu obter todas as informações do seu PC " +
                `(${faltando.join(", ")}). Tente novamente ou preencha os dados manualmente abaixo.`,
                "aviso"
            );
            btn.disabled = false;
            return;
        }

        // 3) Envia para a API de comparação (robô de hardware)
        mostrarStatus("Comparando com os requisitos de " + tituloJogo + "...", "info");
        try {
            const respostaComparar = await buscarComTimeout(
                API_URL + "/comparar",
                {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify({ specs, app_nome: tituloJogo, salvar: true }),
                },
                8000
            );

            if (respostaComparar.status === 404) {
                mostrarStatus(
                    `"${tituloJogo}" ainda não está cadastrado no catálogo de hardware do robô oficial, ` +
                    "então a verificação automática não consegue compará-lo. Use o formulário manual abaixo " +
                    "(ele usa os requisitos já cadastrados deste jogo no Ghost Gamer).",
                    "aviso"
                );
                btn.disabled = false;
                return;
            }

            if (!respostaComparar.ok) {
                throw new Error("api-erro-" + respostaComparar.status);
            }

            const laudo = await respostaComparar.json();
            limparStatus();
            renderizarResultado(laudo);
            mostrarToast("Verificação concluída!", "sucesso");
        } catch (erro) {
            mostrarStatus(
                "O serviço de comparação está temporariamente indisponível. " +
                "Tente novamente mais tarde ou utilize a comparação manual abaixo.",
                "erro"
            );
        } finally {
            btn.disabled = false;
        }
    });
})();
