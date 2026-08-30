<?php
/**
 * Fase 23 (30/08): arreglos del hero y el header.
 *  1. Banners SIN recorte: se suben a la biblioteca de medios y los slides
 *     usan [ux_image] (imagen completa, escala sola en cualquier pantalla).
 *  2. Header sin menú duplicado: logo + BUSCADOR largo + cuenta/carrito en
 *     la fila principal; el menú solo en la fila de abajo.
 * Correr: php ~/bin/wp eval-file ~/fase23.php
 */
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

$B = 'https://miltonroa.github.io/mundo-ofertas/web';
$imgs = ['A' => 'web_heroA.jpg', 'B' => 'web_heroB.jpg', 'C' => 'web_heroC.jpg',
         'Am' => 'web_heroA_m.jpg', 'Bm' => 'web_heroB_m.jpg', 'Cm' => 'web_heroC_m.jpg'];
$ids = get_option('tarvo_hero_ids', []);
foreach ($imgs as $k => $file) {
    if (!empty($ids[$k]) && wp_get_attachment_url($ids[$k])) continue;
    $id = media_sideload_image("$B/$file?v=2", 0, "Tarvo hero $k", 'id');
    if (is_wp_error($id)) { echo "ERROR subiendo $file: " . $id->get_error_message() . "\n"; return; }
    $ids[$k] = $id;
    echo "subido $file -> id $id\n";
}
update_option('tarvo_hero_ids', $ids);

$fid = (int) get_option('page_on_front');
$p = get_post($fid);
$c = $p->post_content;
$i = strpos($c, '[section label="tarvo-hero-banners"');
$j = ($i !== false) ? strpos($c, '[/section]', $i) : false;
if ($i === false || $j === false) { echo "no encontré el hero — abortando\n"; return; }

$sl = '[ux_slider style="container" slide_width="100%" slide_align="center" '
    . 'nav_style="simple" bullet_style="simple" timer="5000" auto_slide="true" '
    . 'pause_hover="true"]';
$lk = ['/tienda/', '/categoria-producto/tecnologia/', '/categoria-producto/hogar-y-cocina/'];
$desk = $mov = '';
foreach (['A', 'B', 'C'] as $n => $k) {
    $desk .= '[ux_image id="' . $ids[$k] . '" image_size="original" link="' . $lk[$n] . '" margin="0px"]';
    $mov .= '[ux_image id="' . $ids[$k . 'm'] . '" image_size="original" link="' . $lk[$n] . '" margin="0px"]';
}
$hero = '[section label="tarvo-hero-banners" padding="0px" padding__sm="0px"]'
    . '[row visibility="hide-for-small" col_style="collapse"][col span="12" padding="0px"]'
    . $sl . $desk . '[/ux_slider][/col][/row]'
    . '[row visibility="show-for-small" col_style="collapse"][col span="12" padding="0px"]'
    . $sl . $mov . '[/ux_slider][/col][/row]'
    . '[/section]';
$c = substr($c, 0, $i) . $hero . substr($c, $j + 10);
wp_update_post(['ID' => $fid, 'post_content' => $c]);
echo "hero con ux_image (sin recortes) instalado\n";

echo "== HEADER ==\n";
foreach (['top', 'main', 'bottom'] as $fila) {
    foreach (['left', 'center', 'right'] as $lado) {
        $k = "header_elements_{$fila}_{$lado}";
        echo "  antes $k = " . json_encode(get_theme_mod($k, '(sin set)')) . "\n";
    }
}
// deshacer los top_* que puso la fase22 (fila top = topbar en Flatsome)
remove_theme_mod('header_elements_top_left');
remove_theme_mod('header_elements_top_center');
remove_theme_mod('header_elements_top_right');
// fila principal: logo + buscador + cuenta/carrito; menú SOLO abajo
set_theme_mod('header_elements_main_left', ['logo']);
set_theme_mod('header_elements_main_center', ['search-form']);
set_theme_mod('header_elements_main_right', ['account', 'cart']);
set_theme_mod('header_elements_bottom_left', ['nav']);
set_theme_mod('header_elements_bottom_center', []);
set_theme_mod('header_elements_bottom_right', []);
set_theme_mod('search_placeholder', '¿Qué estás buscando?');
echo "header reordenado\n";
wp_cache_flush();
echo "== FASE 23 LISTA ==\n";
