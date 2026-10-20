// ===================== ADICIONAR À BIBLIOTECA (AJAX, sem reload) =====================
const btnAdicionar = document.getElementById("btnAdicionarBiblioteca");
const msgBiblioteca = document.getElementById("msgBiblioteca");

btnAdicionar?.addEventListener("click", async () => {
    btnAdicionar.disabled = true;
    const textoOriginal = btnAdicionar.innerHTML;
    btnAdicionar.textContent = "Adicionando...";

    try {
        const formData = new FormData();
        formData.append("id_jogo", btnAdicionar.dataset.id);
        formData.append("csrf_token", btnAdicionar.dataset.csrf);

        const dados = await enviarFormulario("../XAMP/adicionarBiblioteca.php", formData);

        msgBiblioteca.className = "form-msg sucesso";
        msgBiblioteca.textContent = dados.mensagem;
        mostrarToast(dados.mensagem, "sucesso");

        btnAdicionar.outerHTML = '<button class="btn btn-outline" disabled>Já está na sua biblioteca</button>';
    } catch (erro) {
        msgBiblioteca.className = "form-msg erro";
        msgBiblioteca.textContent = erro.message;
        mostrarToast(erro.message, "erro");
        btnAdicionar.disabled = false;
        btnAdicionar.innerHTML = textoOriginal;
    }
});

// ===================== VERIFICADOR DE COMPATIBILIDADE =====================
// Compara os dados que o usuário informa com os requisitos do jogo
// (window.REQUISITOS_JOGO, injetado pela página). Funciona 100% no
// navegador, sem depender de nenhum serviço externo.
const formCompat = document.getElementById("formCompat");
const resultadoCompat = document.getElementById("resultadoCompat");

function calcularNivel(usuario, minimo) {
    if (minimo <= 0) return { pct: 100, status: "ok" };
    const pct = Math.min(100, Math.round((usuario / minimo) * 100));
    if (pct < 100) return { pct, status: "abaixo" };
    if (pct < 130) return { pct: 100, status: "ok" };
    return { pct: 100, status: "otimo" };
}

function linhaResultado(nome, usuario, minimo, unidade) {
    const { pct, status } = calcularNivel(usuario, minimo);
    const classe = status === "abaixo" ? "insuficiente" : (status === "otimo" ? "otimo" : "recomendado");
    const rotulo = status === "abaixo" ? "Abaixo do mínimo" : (status === "otimo" ? "Acima do requisito" : "Atende ao mínimo");

    return `
        <div class="linha-compat ${classe}">
            <div class="linha-compat-topo">
                <span>${nome}</span>
                <span>${usuario}${unidade} / ${minimo}${unidade} mín.</span>
            </div>
            <div class="barra-compat"><div class="barra-compat-preenchida" style="width:${pct}%"></div></div>
            <span class="linha-compat-status">${rotulo}</span>
        </div>
    `;
}

formCompat?.addEventListener("submit", (e) => {
    e.preventDefault();

    const req = window.REQUISITOS_JOGO;
    const usuario = {
        nucleos: Number(document.getElementById("compatNucleos").value),
        frequencia: Number(document.getElementById("compatFrequencia").value),
        ram_gb: Number(document.getElementById("compatRam").value),
        vram_gb: Number(document.getElementById("compatVram").value),
        armazenamento: Number(document.getElementById("compatArmazenamento").value),
    };

    const linhas = [
        linhaResultado("Núcleos", usuario.nucleos, req.nucleos, ""),
        linhaResultado("Frequência", usuario.frequencia, req.frequencia, " GHz"),
        linhaResultado("Memória RAM", usuario.ram_gb, req.ram_gb, " GB"),
        linhaResultado("Memória de vídeo", usuario.vram_gb, req.vram_gb, " GB"),
        linhaResultado("Armazenamento livre", usuario.armazenamento, req.armazenamento, " GB"),
    ].join("");

    const algumInsuficiente = [
        [usuario.nucleos, req.nucleos],
        [usuario.frequencia, req.frequencia],
        [usuario.ram_gb, req.ram_gb],
        [usuario.vram_gb, req.vram_gb],
        [usuario.armazenamento, req.armazenamento],
    ].some(([u, m]) => u < m);

    const veredito = algumInsuficiente
        ? '<p class="veredito-compat insuficiente">Seu PC não atende a todos os requisitos mínimos deste jogo.</p>'
        : '<p class="veredito-compat ok">Seu PC atende aos requisitos mínimos deste jogo.</p>';

    resultadoCompat.innerHTML = veredito + linhas;
    resultadoCompat.classList.add("ativo");
});
