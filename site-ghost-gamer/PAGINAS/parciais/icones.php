<?php
/**
 * Ícones SVG inline, estilo "outline" (stroke, sem preenchimento).
 * Usado no lugar de emojis em toda a interface, para um visual
 * profissional e consistente entre sistemas operacionais/navegadores
 * (emoji renderiza diferente em cada um; SVG é sempre idêntico).
 *
 * Uso: <?= icone('home') ?>  ou  <?= icone('home', 20) ?>
 */
function icone(string $nome, int $tamanho = 20): string
{
    $caminhos = [
        "home"        => '<path d="M3 11.5 12 4l9 7.5"/><path d="M5 10v9a1 1 0 0 0 1 1h4v-6h4v6h4a1 1 0 0 0 1-1v-9"/>',
        "biblioteca"  => '<rect x="3" y="4" width="7" height="16" rx="1"/><rect x="14" y="4" width="7" height="16" rx="1"/><path d="M10 8h4M10 12h4M10 16h4"/>',
        "categoria"   => '<rect x="3" y="3" width="8" height="8" rx="1"/><rect x="13" y="3" width="8" height="8" rx="1"/><rect x="3" y="13" width="8" height="8" rx="1"/><rect x="13" y="13" width="8" height="8" rx="1"/>',
        "perfil"      => '<circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/>',
        "suporte"     => '<circle cx="12" cy="12" r="9"/><path d="M12 15v-1c0-1 .6-1.6 1.4-2.2.8-.6 1.3-1.2 1.3-2.1A2.7 2.7 0 0 0 12 7a2.7 2.7 0 0 0-2.7 2.3"/><circle cx="12" cy="17.7" r="0.6" fill="currentColor" stroke="none"/>',
        "login"       => '<path d="M15 4h3a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-3"/><path d="M10 8l4 4-4 4"/><path d="M14 12H3"/>',
        "cadastro"    => '<circle cx="9" cy="8" r="4"/><path d="M2 20c0-4 3.1-7 7-7s7 3 7 7"/><path d="M18 8v6M15 11h6"/>',
        "sair"        => '<path d="M9 4H6a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h3"/><path d="M14 8l4 4-4 4"/><path d="M18 12H8"/>',
        "admin"       => '<path d="M12 3l7 3v6c0 4.5-3 7.7-7 9-4-1.3-7-4.5-7-9V6l7-3z"/><path d="m9 12 2 2 4-4"/>',
        "buscar"      => '<circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/>',
        "editar"      => '<path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/>',
        "fechar"      => '<path d="M6 6l12 12M18 6 6 18"/>',
        "menu"        => '<path d="M4 6h16M4 12h16M4 18h16"/>',
        "adicionar"   => '<path d="M12 5v14M5 12h14"/>',
        "check"       => '<path d="m5 13 4 4L19 7"/>',
        "coracao"     => '<path d="M12 20s-7-4.5-9.5-8.8C.6 8 2 4.5 5.5 4c2-.3 3.7.7 4.5 2.2C10.8 4.7 12.5 3.7 14.5 4 18 4.5 19.4 8 17.5 11.2 15 15.5 12 20 12 20Z"/>',
        "seta-direita"=> '<path d="M5 12h14M13 6l6 6-6 6"/>',
        "alerta"      => '<path d="M12 3 2 20h20L12 3Z"/><path d="M12 10v4"/><circle cx="12" cy="17" r=".7" fill="currentColor" stroke="none"/>',
        "monitor"     => '<rect x="3" y="4" width="18" height="12" rx="1.5"/><path d="M8 20h8M12 16v4"/>',
        "escudo-check"=> '<path d="M12 3l7 3v6c0 4.5-3 7.7-7 9-4-1.3-7-4.5-7-9V6l7-3z"/><path d="m9 12 2 2 4-4"/>',
        "empresa"     => '<rect x="4" y="3" width="16" height="18" rx="1"/><path d="M9 8h1M14 8h1M9 12h1M14 12h1M9 16h1M14 16h1"/>',
        "jogo"        => '<rect x="2" y="7" width="20" height="10" rx="4"/><path d="M7 10v4M5 12h4"/><circle cx="16" cy="10.5" r=".8" fill="currentColor" stroke="none"/><circle cx="18" cy="13" r=".8" fill="currentColor" stroke="none"/>',
        "download"    => '<path d="M12 3v12"/><path d="m7 10 5 5 5-5"/><path d="M5 21h14"/>',

        // Ícones adicionados na reformulação visual (seta de submenu, painel admin
        // e ícones de gênero usados nos blocos de categoria da Home/Categorias).
        "seta-baixo"  => '<path d="m6 9 6 6 6-6"/>',
        "painel"      => '<rect x="3" y="3" width="7" height="9" rx="1"/><rect x="14" y="3" width="7" height="5" rx="1"/><rect x="14" y="12" width="7" height="9" rx="1"/><rect x="3" y="16" width="7" height="5" rx="1"/>',
        "fantasma"    => '<path d="M9 10h.01M15 10h.01"/><path d="M12 2a8 8 0 0 0-8 8v12l3-3 2.5 2.5L12 19l2.5 2.5L17 19l3 3V10a8 8 0 0 0-8-8z"/>',
        "carro"       => '<path d="M19 17h2c.6 0 1-.4 1-1v-3c0-.9-.7-1.7-1.5-1.9C18.7 10.6 16 10 16 10s-1.3-1.4-2.2-2.3c-.5-.4-1.1-.7-1.8-.7H5c-.6 0-1.1.4-1.4.9l-1.4 2.9A3.7 3.7 0 0 0 2 12v4c0 .6.4 1 1 1h2"/><circle cx="7" cy="17" r="2"/><path d="M9 17h6"/><circle cx="17" cy="17" r="2"/>',
        "mira"        => '<circle cx="12" cy="12" r="10"/><path d="M22 12h-4M6 12H2M12 6V2M12 22v-4"/>',
        "espada"      => '<path d="m14.5 17.5-11.5-11.5V3h3l11.5 11.5"/><path d="m13 19 6-6M16 16l4 4M19 21l2-2"/>',
        "musica"      => '<path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/>',
        "bussola"     => '<circle cx="12" cy="12" r="10"/><path d="m16.2 7.8-2.1 6.3-6.3 2.1 2.1-6.3z"/>',
        "controle"    => '<path d="M6 12h4M8 10v4"/><circle cx="15" cy="13" r="1"/><circle cx="18" cy="11" r="1"/><path d="M17.3 5H6.7a4 4 0 0 0-3.9 3.2L1.6 16a3 3 0 0 0 5.3 2.3L9 16h6l2.1 2.3a3 3 0 0 0 5.3-2.3l-1.2-7.8A4 4 0 0 0 17.3 5z"/>',
        "play"        => '<path d="M7 4.5v15l12.5-7.5z" fill="currentColor" stroke="currentColor"/>',
    ];

    $conteudo = $caminhos[$nome] ?? $caminhos["alerta"];

    return '<svg class="icone" width="' . $tamanho . '" height="' . $tamanho . '" viewBox="0 0 24 24" '
         . 'fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
         . $conteudo . '</svg>';
}

/**
 * Escolhe um ícone (dos definidos acima) para representar o gênero de um jogo.
 * Usado nos blocos de categoria da Home e da página Categorias.
 * Gêneros fora da lista caem no ícone de controle genérico.
 */
function iconeGenero(?string $genero): string
{
    $mapa = [
        "Terror"        => "fantasma",
        "Corrida"       => "carro",
        "Tiro"          => "mira",
        "Battle Royale" => "mira",
        "Ação"          => "espada",
        "RPG"           => "espada",
        "Luta"          => "espada",
        "Musical"       => "musica",
        "Aventura"      => "bussola",
        "Sobrevivência" => "bussola",
        "Estratégia"    => "escudo-check",
        "Simulação"     => "monitor",
        "Esportes"      => "coracao",
        "MOBA"          => "mira",
        "MMORPG"        => "espada",
        "Indie"         => "adicionar",
        "Plataforma"    => "jogo",
        "Puzzle"        => "categoria",
    ];
    return $mapa[$genero] ?? "controle";
}
