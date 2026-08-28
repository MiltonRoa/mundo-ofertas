<?php
/**
 * Fase 15b — FIX REAL de la tienda vacía: el mod html_shop_page_content
 * (la franja informativa agregada el 15/08) REEMPLAZA la grilla de productos
 * en Flatsome — la tienda quedó sin productos desde ese día. La info de la
 * franja ya vive en la topbar (fase15), así que la franja se elimina.
 * Correr: php ~/bin/wp eval-file ~/fase15b.php
 */
echo "antes: " . var_export((bool) get_theme_mod('html_shop_page_content'), true) . "\n";
remove_theme_mod('html_shop_page_content');
echo "despues: " . var_export((bool) get_theme_mod('html_shop_page_content'), true) . "\n";
wp_cache_flush();
echo "FASE 15b LISTA — refrescar /tienda/\n";
