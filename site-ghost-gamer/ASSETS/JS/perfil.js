// ===================== MODAL EDITAR PERFIL =====================
const modal = document.getElementById("modalEditarPerfil");
const btnAbrir = document.getElementById("btnAbrirEdicao");
const btnFechar = document.getElementById("btnFecharEdicao");
const formEditar = document.getElementById("formEditarPerfil");
const msgEditar = document.getElementById("msgEditarPerfil");
const btnSalvar = document.getElementById("btnSalvarPerfil");
const inputFoto = document.getElementById("inputFoto");
const previewNovaFoto = document.getElementById("previewNovaFoto");

function abrirModal() {
    modal.classList.add("ativo");
}
function fecharModal() {
    modal.classList.remove("ativo");
}

btnAbrir?.addEventListener("click", abrirModal);
btnFechar?.addEventListener("click", fecharModal);
modal?.addEventListener("click", (e) => {
    if (e.target === modal) fecharModal();
});

// Preview da nova foto antes de enviar
inputFoto?.addEventListener("change", () => {
    const arquivo = inputFoto.files[0];
    if (!arquivo) return;

    const leitor = new FileReader();
    leitor.onload = (e) => {
        if (previewNovaFoto.tagName === "IMG") {
            previewNovaFoto.src = e.target.result;
        } else {
            const img = document.createElement("img");
            img.className = "perfil-avatar";
            img.id = "previewNovaFoto";
            img.src = e.target.result;
            previewNovaFoto.replaceWith(img);
        }
    };
    leitor.readAsDataURL(arquivo);
});

formEditar?.addEventListener("submit", async (e) => {
    e.preventDefault();

    msgEditar.className = "form-msg";
    btnSalvar.disabled = true;
    btnSalvar.textContent = "Salvando...";

    try {
        const dados = await enviarFormulario("../XAMP/atualizarPerfil.php", new FormData(formEditar));

        msgEditar.className = "form-msg sucesso";
        msgEditar.textContent = dados.mensagem;

        document.getElementById("nomeExibido").textContent = dados.nickname;
        mostrarToast("Perfil atualizado! A página vai atualizar.", "sucesso");

        setTimeout(() => window.location.reload(), 900);
    } catch (erro) {
        msgEditar.className = "form-msg erro";
        msgEditar.textContent = erro.message;
        mostrarToast(erro.message, "erro");
    } finally {
        btnSalvar.disabled = false;
        btnSalvar.textContent = "Salvar alterações";
    }
});
