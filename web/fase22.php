<?php
/**
 * Fase 22 (30/08): hero PRO estilo Tupi — carrusel ANIMADO de 3 banners
 * diseñados (autoplay cada 5s, con link cada uno) en lugar del hero de
 * tarjetas; + BUSCADOR LARGO en el header (como Tupi/Amazon).
 * Correr: php ~/bin/wp eval-file ~/fase22.php
 */
$fid = (int) get_option('page_on_front');
$p = get_post($fid);
$c = $p->post_content;

$i = strpos($c, '[section label="tarvo-hero-destacados"');
$j = ($i !== false) ? strpos($c, '[/section]', $i) : false;
if ($i === false || $j === false) { echo "no encontré el hero — abortando\n"; return; }

$B = 'https://miltonroa.github.io/mundo-ofertas/web';
$hero = '[section label="tarvo-hero-banners" padding="0px"]'
    . '[ux_slider style="container" slide_width="100%" slide_align="center" '
    . 'hide_nav="false" nav_style="simple" nav_color="light" bullet_style="simple" '
    . 'timer="5000" auto_slide="true" pause_hover="true"]'
    . '[ux_banner height="420px" height__sm="300px" bg="' . $B . '/web_heroA.jpg" '
    . 'bg_size="original" bg_pos="left center" link="/tienda/"][/ux_banner]'
    . '[ux_banner height="420px" height__sm="300px" bg="' . $B . '/web_heroB.jpg" '
    . 'bg_size="original" bg_pos="left center" link="/categoria-producto/tecnologia/"][/ux_banner]'
    . '[ux_banner height="420px" height__sm="300px" bg="' . $B . '/web_heroC.jpg" '
    . 'bg_size="original" bg_pos="left center" link="/categoria-producto/hogar-y-cocina/"][/ux_banner]'
    . '[/ux_slider][/section]';

$c = substr($c, 0, $i) . $hero . substr($c, $j + 10);
wp_update_post(['ID' => $fid, 'post_content' => $c]);
echo "hero de banners animado instalado\n";

echo "== BUSCADOR EN HEADER ==\n";
foreach (['header_elements_top_left', 'header_elements_top_center',
          'header_elements_top_right', 'header_elements_bottom_left',
          'header_elements_bottom_center', 'header_elements_bottom_right'] as $k) {
    echo "  antes $k = " . json_encode(get_theme_mod($k, '(sin set)')) . "\n";
}
set_theme_mod('header_elements_top_left', ['logo']);
set_theme_mod('header_elements_top_center', ['search-form']);
set_theme_mod('header_elements_top_right', ['account', 'cart']);
set_theme_mod('header_elements_bottom_left', ['nav']);
set_theme_mod('header_elements_bottom_center', []);
set_theme_mod('header_search_style', 'form');
set_theme_mod('search_placeholder', '¿Qué estás buscando?');
echo "buscador configurado (si el header queda raro, los valores de arriba "
    . "permiten restaurar)\n";
wp_cache_flush();
echo "== FASE 22 LISTA ==\n";
