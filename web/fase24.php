<?php
/**
 * Fase 24 (30/08): header definitivo — el menú UNA sola vez.
 * En esta versión de Flatsome la fila del logo es header_elements_top_*
 * (la fase23 los borró por error creyendo que eran la topbar). Se setean
 * de nuevo + CSS de respaldo que oculta los items de menú duplicados de
 * la fila principal aunque los mods no rendericen.
 * Correr: php ~/bin/wp eval-file ~/fase24.php
 */
foreach (['top', 'main', 'bottom'] as $fila) {
    foreach (['left', 'center', 'right'] as $lado) {
        $k = "header_elements_{$fila}_{$lado}";
        echo "antes $k = " . json_encode(get_theme_mod($k, '(sin set)')) . "\n";
    }
}
set_theme_mod('header_elements_top_left', ['logo']);
set_theme_mod('header_elements_top_center', ['search-form']);
set_theme_mod('header_elements_top_right', ['account', 'cart']);
set_theme_mod('header_elements_bottom_left', ['nav']);
set_theme_mod('header_elements_bottom_center', []);
set_theme_mod('header_elements_bottom_right', []);
remove_theme_mod('header_elements_main_left');
remove_theme_mod('header_elements_main_center');
remove_theme_mod('header_elements_main_right');
set_theme_mod('search_placeholder', '¿Qué estás buscando?');
echo "mods aplicados\n";

$css = wp_get_custom_css();
if (strpos($css, 'fase24') === false) {
    $css .= "\n/* fase24: sin menu duplicado en la fila del logo (respaldo) */\n"
        . ".header-main .header-nav > li.menu-item{display:none!important}\n"
        . ".header-main .header-nav > li.html{display:none!important}\n";
    wp_update_custom_css_post($css);
    echo "CSS respaldo agregado\n";
}
wp_cache_flush();
echo "== FASE 24 LISTA ==\n";
