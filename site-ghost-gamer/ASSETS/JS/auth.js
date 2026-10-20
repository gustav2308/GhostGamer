function configurarFormularioAuth(idForm, idMsg, idBtn, url, textoBotao) {
    const form = document.getElementById(idForm);
    if (!form) return;

    const msg = document.getElementById(idMsg);
    const btn = document.getElementById(idBtn);

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
                window.location.href = dados.redirecionar || "../index.php";
            }, 600);
        } catch (erro) {
            msg.className = "form-msg erro";
            msg.textContent = erro.message;
            mostrarToast(erro.message, "erro");
            btn.disabled = false;
            btn.textContent = textoBotao;
        }
    });
}

configurarFormularioAuth("formLogin", "msgLogin", "btnLogin", "../XAMP/loginCliente.php", "Entrar");
configurarFormularioAuth("formCadastro", "msgCadastro", "btnCadastro", "../XAMP/gravarcad.php", "Criar conta");
