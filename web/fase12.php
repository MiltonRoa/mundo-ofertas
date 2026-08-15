<?php
// Fase 12 Tarvo — restaurar barra lateral de la tienda (buscador/categorías/precio). wp eval-file fase12.php
update_option('widget_woocommerce_product_search',
    [2 => ['title' => 'Buscar'], '_multiwidget' => 1]);
update_option('widget_woocommerce_product_categories',
    [2 => ['title' => 'Categorías', 'count' => 1, 'hierarchical' => 1, 'hide_empty' => 1], '_multiwidget' => 1]);
update_option('widget_woocommerce_price_filter',
    [2 => ['title' => 'Filtrar por precio'], '_multiwidget' => 1]);

$sw = get_option('sidebars_widgets');
$sw['shop-sidebar'] = ['woocommerce_product_search-2',
                       'woocommerce_product_categories-2',
                       'woocommerce_price_filter-2'];
update_option('sidebars_widgets', $sw);
set_theme_mod('category_sidebar', 'left-sidebar');
echo "sidebar tienda restaurada: " . implode(', ', $sw['shop-sidebar']) . "\n";
echo "FIN FASE 12 OK\n";
