function configurarPassoRecuperacao(idForm, idMsg, idBtn, url, redirecionarPadrao) {
    const form = document.getElementById(idForm);
    if (!form) return;

    const msg = document.getElementById(idMsg);
    const btn = document.getElementById(idBtn);
    const textoOriginal = btn.textContent;

    form.addEventListener("submit", async (e) => {
        e.preventDefault();

        msg.className = "form-msg";
        btn.disabled = true;
        btn.textContent = "Aguarde...";

        try {
            const dados = await enviarFormulario(url, new FormData(form));

            msg.className = "form-msg sucesso";
            msg.textContent = dados.mensagem;
            mostrarToast(dados.mensagem, "sucesso");

            setTimeout(() => {
                window.location.href = dados.redirecionar || redirecionarPadrao;
            }, 700);
        } catch (erro) {
            msg.className = "form-msg erro";
            msg.textContent = erro.message;
            mostrarToast(erro.message, "erro");
            btn.disabled = false;
            btn.textContent = textoOriginal;
        }
    });
}

configurarPassoRecuperacao("formRecuperar", "msgRecuperar", "btnRecuperar", "../XAMP/enviarRecuperacao.php", "verificarCodigo.php");
configurarPassoRecuperacao("formCodigo", "msgCodigo", "btnCodigo", "../XAMP/verificarCodigo.php", "novaSenha.php");
configurarPassoRecuperacao("formNovaSenha", "msgNovaSenha", "btnNovaSenha", "../XAMP/novaSenha.php", "loginCliente.php");
