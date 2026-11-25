<?php
// FASE 41 (25/11, Milton "figuran los mismos productos una y otra
// vez"): la banda Mayorista de la portada mostraba "los 10 más
// nuevos" (orderby=date) y los últimos cargados eran una familia de
// casi-gemelos (mismo producto en colores/tamaños, por el orden del
// PDF). FIX: orderby=rand — cada visita muestra 10 productos
// mayoristas distintos al azar.
// Uso: php ~/bin/wp --path=$HOME/public_html eval-file ~/fase41.php
$pg = get_post((int) get_option('page_on_front'));
if (!$pg) { echo "FASE41-ERROR: sin portada\n"; return; }
$may = get_term_by('name', 'Mayorista', 'product_cat');
if (!$may) { echo "FASE41-ERROR: sin categoria Mayorista\n"; return; }
$viejo = 'cat="' . $may->term_id . '" orderby="date"';
$nuevo = 'cat="' . $may->term_id . '" orderby="rand"';
$c = $pg->post_content;
if (strpos($c, $nuevo) !== false) {
    echo "FASE41-OK banda=ya-era-rand\n";
    return;
}
if (strpos($c, $viejo) === false) {
    echo "FASE41-ERROR: no encuentro el ux_products de la banda\n";
    return;
}
wp_update_post(array('ID' => $pg->ID,
                     'post_content' => str_replace($viejo, $nuevo, $c)));
echo "FASE41-OK banda=rand\n";
