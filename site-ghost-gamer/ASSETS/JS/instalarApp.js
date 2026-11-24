// Instalação do SITE Ghost Gamer, independente dos downloads de jogos.
(() => {
    const downloadItem = document.getElementById('ghostAppDownloadItem');
    const downloadLink = document.getElementById('ghostAppDownload');
    const installItem = document.getElementById('ghostAppInstallItem');
    const installButton = document.getElementById('ghostAppInstall');
    const KEY = 'ghostGamerWindowsExeBaixado';
    let promptInstalacao = null;

    const modoAplicativo = () => window.matchMedia('(display-mode: standalone)').matches ||
        window.navigator.standalone === true;

    function atualizarBotoes() {
        if (downloadItem) downloadItem.hidden = modoAplicativo() || localStorage.getItem(KEY) === 'sim';
        if (installItem) installItem.hidden = modoAplicativo() || !promptInstalacao;
    }

    if (downloadLink) downloadLink.addEventListener('click', () => {
        // Registra apenas que o download foi solicitado neste navegador.
        // Um site não consegue verificar se um EXE foi realmente instalado.
        localStorage.setItem(KEY, 'sim');
        atualizarBotoes();
    });

    window.addEventListener('beforeinstallprompt', event => {
        event.preventDefault();
        promptInstalacao = event;
        atualizarBotoes();
    });

    if (installButton) installButton.addEventListener('click', async () => {
        if (!promptInstalacao) return;
        const evento = promptInstalacao;
        promptInstalacao = null;
        atualizarBotoes();
        await evento.prompt();
        await evento.userChoice;
    });

    window.addEventListener('appinstalled', () => {
        promptInstalacao = null;
        localStorage.setItem(KEY, 'sim');
        atualizarBotoes();
    });
    window.matchMedia('(display-mode: standalone)').addEventListener?.('change', atualizarBotoes);
    atualizarBotoes();

    if ('serviceWorker' in navigator && (location.protocol === 'https:' || location.hostname === 'localhost' || location.hostname === '127.0.0.1')) {
        // Caminho relativo à raiz do site; as páginas internas usam ../.
        navigator.serviceWorker.register('/service-worker.js', {
            scope: '/'
        }).catch(erro => {
            console.error('Erro ao registrar o Service Worker:', erro);
        });
    }
})();
