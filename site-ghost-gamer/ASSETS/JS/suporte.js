const formSuporte = document.getElementById("formSuporte");
const msgSuporte = document.getElementById("msgSuporte");
const mensagemInput = document.getElementById("mensagem");
const contadorMensagem = document.getElementById("contadorMensagem");
const btnEnviarSuporte = document.getElementById("btnEnviarSuporte");

mensagemInput.addEventListener("input", () => {
    contadorMensagem.textContent = mensagemInput.value.length;
});

formSuporte.addEventListener("submit", async (e) => {
    e.preventDefault();

    msgSuporte.className = "form-msg";
    msgSuporte.textContent = "";

    btnEnviarSuporte.disabled = true;
    btnEnviarSuporte.textContent = "Enviando...";

    try {
        const dados = await enviarFormulario("../XAMP/suporte.php", new FormData(formSuporte));

        msgSuporte.className = "form-msg sucesso";
        msgSuporte.textContent = dados.mensagem;
        mostrarToast(dados.mensagem, "sucesso");
        formSuporte.reset();
        contadorMensagem.textContent = "0";
    } catch (erro) {
        msgSuporte.className = "form-msg erro";
        msgSuporte.textContent = erro.message;
        mostrarToast(erro.message, "erro");
    } finally {
        btnEnviarSuporte.disabled = false;
        btnEnviarSuporte.textContent = "Enviar mensagem";
    }
});
