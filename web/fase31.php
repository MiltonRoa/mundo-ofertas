<?php
/**
 * FASE 31 (23/11) — Subcategorías visibles en la web:
 *   1. Páginas de rubro muestran primero las subcategorías (fichas) y
 *      debajo los productos (woocommerce_category_archive_display=both).
 *   2. Menú "Principal": las subcategorías NO VACÍAS cuelgan de su rubro
 *      (Hogar y Cocina / Electrodomésticos) en el dropdown Categorías.
 * Idempotente: se puede correr de nuevo sin duplicar items.
 * Uso: wp eval-file fase31.php
 */

update_option('woocommerce_category_archive_display', 'both');
echo "opcion category_archive_display=both\n";

$padres = array('Hogar y Cocina', 'Electrodomésticos');

$menu = wp_get_nav_menu_object('Principal');
if (!$menu) {
    echo "FASE31-ERROR: no encuentro el menu Principal\n";
    return;
}
$items = wp_get_nav_menu_items($menu->term_id);
$por_term = array();   // term_id de product_cat -> item del menú
foreach ($items as $it) {
    if ($it->object === 'product_cat') {
        $por_term[(int) $it->object_id] = $it;
    }
}

$agregados = 0;
$saltados = 0;
foreach ($padres as $nombre_padre) {
    $padre = get_term_by('name', $nombre_padre, 'product_cat');
    if (!$padre) {
        echo "aviso: no existe la categoria $nombre_padre\n";
        continue;
    }
    if (!isset($por_term[$padre->term_id])) {
        echo "aviso: $nombre_padre no esta en el menu — salto\n";
        continue;
    }
    $item_padre = $por_term[$padre->term_id];
    $hijos = get_terms(array('taxonomy' => 'product_cat',
                             'parent' => $padre->term_id,
                             'hide_empty' => true));
    foreach ($hijos as $h) {
        if (isset($por_term[$h->term_id])) {
            $saltados++;
            continue;  // ya está en el menú
        }
        $r = wp_update_nav_menu_item($menu->term_id, 0, array(
            'menu-item-type' => 'taxonomy',
            'menu-item-object' => 'product_cat',
            'menu-item-object-id' => $h->term_id,
            'menu-item-parent-id' => $item_padre->ID,
            'menu-item-title' => $h->name,
            'menu-item-status' => 'publish',
        ));
        if (is_wp_error($r)) {
            echo "error con {$h->name}: " . $r->get_error_message() . "\n";
        } else {
            $agregados++;
            echo "menu: $nombre_padre -> {$h->name}\n";
        }
    }
}
echo "FASE31-OK agregados=$agregados ya_estaban=$saltados\n";
