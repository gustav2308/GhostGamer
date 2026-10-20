function mostrarAcao(idAcao) {

    // Esconde todos os filtros
    const elementos = document.querySelectorAll(
        ".empresa, .cliente, .jogo"
    );

    elementos.forEach(function(elemento) {
        elemento.hidden = true;
    });

    // Mostra somente os filtros da ação escolhida
    const elementosAcao = document.querySelectorAll("." + idAcao);

    elementosAcao.forEach(function(elemento) {
        elemento.hidden = false;
    });

    // Guarda a ação no formulário
    document.getElementById("acao").value = idAcao;
}