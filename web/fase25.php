<?php
/**
 * Fase 25 (30/08): layout final del header —
 *   fila del logo: logo + MENÚ + Acceder/Carrito (como estaba antes)
 *   fila de abajo: BUSCADOR LARGO centrado (estilo Tupi/Amazon)
 * Correr: php ~/bin/wp eval-file ~/fase25.php
 */
set_theme_mod('header_elements_top_left', ['logo']);
set_theme_mod('header_elements_top_center', []);
set_theme_mod('header_elements_top_right', ['nav', 'account', 'cart']);
set_theme_mod('header_elements_bottom_left', []);
set_theme_mod('header_elements_bottom_center', ['search-form']);
set_theme_mod('header_elements_bottom_right', []);
set_theme_mod('search_placeholder', '¿Qué estás buscando?');
echo "filas del header configuradas\n";

$css = wp_get_custom_css();
// fuera el respaldo de la fase24 (ocultaria el menu de arriba)
$css = str_replace(
    "/* fase24: sin menu duplicado en la fila del logo (respaldo) */\n"
    . ".header-main .header-nav > li.menu-item{display:none!important}\n"
    . ".header-main .header-nav > li.html{display:none!important}\n", '', $css);
if (strpos($css, 'fase25') === false) {
    $css .= "\n/* fase25: buscador largo abajo, sin lupa arriba */\n"
        . ".header-main li.header-search{display:none!important}\n"
        . ".header-bottom .flex-center{flex:1;max-width:980px}\n"
        . ".header-bottom .header-search-form,"
        . ".header-bottom .searchform{width:100%}\n"
        . ".header-bottom .searchform .flex-col:first-child{flex:1}\n";
}
wp_update_custom_css_post($css);
echo "CSS actualizado\n";
wp_cache_flush();
echo "== FASE 25 LISTA ==\n";
