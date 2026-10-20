// ===================== ÍCONES (mesmo estilo do PHP, sem emoji) =====================
const SVG_MENU = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 6h16M4 12h16M4 18h16"/></svg>';
const SVG_FECHAR = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M6 6l12 12M18 6 6 18"/></svg>';

// ===================== MENU LATERAL =====================
(function () {
    const btn = document.getElementById("menu-btn");
    const menu = document.getElementById("menu");
    const overlay = document.getElementById("overlay");
    if (!btn || !menu || !overlay) return;

    function fecharMenu() {
        menu.classList.remove("ativo");
        overlay.classList.remove("ativo");
        btn.innerHTML = SVG_MENU;
    }

    function alternarMenu(e) {
        e.stopPropagation();
        const abrindo = !menu.classList.contains("ativo");
        menu.classList.toggle("ativo", abrindo);
        overlay.classList.toggle("ativo", abrindo);
        btn.innerHTML = abrindo ? SVG_FECHAR : SVG_MENU;
    }

    btn.addEventListener("click", alternarMenu);
    overlay.addEventListener("click", fecharMenu);
    menu.addEventListener("click", (e) => e.stopPropagation());
    document.addEventListener("keydown", (e) => {
        if (e.key === "Escape") fecharMenu();
    });
})();

// ===================== BUSCA DE JOGOS (filtro em tempo real) =====================
// Funciona em qualquer página que tenha um grid de cards com [data-titulo]
// e o campo #searchInput no cabeçalho -- sem precisar de outra consulta ao banco.
(function () {
    const input = document.getElementById("searchInput");
    if (!input) return;

    const cards = document.querySelectorAll("[data-titulo]");
    if (!cards.length) return;

    input.addEventListener("input", () => {
        const termo = input.value.trim().toLowerCase();
        let visiveis = 0;

        cards.forEach((card) => {
            const titulo = card.dataset.titulo.toLowerCase();
            const bate = titulo.includes(termo);
            card.style.display = bate ? "" : "none";
            if (bate) visiveis++;
        });

        const vazio = document.getElementById("buscaVazia");
        if (vazio) vazio.style.display = (termo && visiveis === 0) ? "block" : "none";
    });
})();

// ===================== TOASTS =====================
function mostrarToast(mensagem, tipo = "info") {
    const container = document.getElementById("toast-container");
    if (!container) {
        alert(mensagem);
        return;
    }
    const toast = document.createElement("div");
    toast.className = "toast " + tipo;
    toast.textContent = mensagem;
    container.appendChild(toast);
    setTimeout(() => {
        toast.style.opacity = "0";
        toast.style.transition = "opacity .3s";
        setTimeout(() => toast.remove(), 300);
    }, 4200);
}

// ===================== HELPER DE FETCH JSON =====================
async function enviarFormulario(url, formData) {
    const resposta = await fetch(url, {
        method: "POST",
        body: formData,
        headers: { "X-Requested-With": "XMLHttpRequest" },
    });

    let dados;
    try {
        dados = await resposta.json();
    } catch (erro) {
        throw new Error("Resposta inesperada do servidor.");
    }

    if (!resposta.ok || !dados.ok) {
        throw new Error(dados.mensagem || "Não foi possível concluir a ação.");
    }

    return dados;
}
