// Ghost Gamer: permite instalabilidade sem armazenar páginas privadas ou dados sensíveis.
self.addEventListener('install', () => self.skipWaiting());
self.addEventListener('activate', event => event.waitUntil(self.clients.claim()));
self.addEventListener('fetch', event => {
  // As páginas PHP continuam dependendo de servidor e Internet.
  // Não cacheamos login, cadastro ou consultas ao banco.
});
