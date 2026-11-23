<?php
/**
 * FASE 33 (23/11) — ordenar el mega-menú "Categorías" (Milton: "la
 * distribución es fea"): 3 columnas parejas.
 *   · Electrodomésticos (con sus subcats, se agrega Air Fryers que
 *     faltaba — fase31 corrió antes de crearla)
 *   · Hogar y Cocina (con sus subcats)
 *   · "Más categorías" (columna con los rubros sin subcategorías)
 * Idempotente. Uso: wp eval-file fase33.php
 */
$menu = wp_get_nav_menu_object('Principal');
if (!$menu) {
    echo "FASE33-ERROR: sin menu Principal\n";
    return;
}
$items = wp_get_nav_menu_items($menu->term_id);
$cat_item = null;        // el dropdown "Categorías"
$por_term = array();     // term_id -> item
foreach ($items as $it) {
    if (!$cat_item && stripos($it->title, 'Categor') !== false
            && $it->object !== 'product_cat') {
        $cat_item = $it;
    }
    if ($it->object === 'product_cat') {
        $por_term[(int) $it->object_id] = $it;
    }
}
if (!$cat_item) {
    echo "FASE33-ERROR: no encuentro el dropdown Categorias\n";
    return;
}

// 1) Air Fryers bajo Electrodomésticos (si falta)
$electro = get_term_by('name', 'Electrodomésticos', 'product_cat');
$af = get_term_by('name', 'Air Fryers', 'product_cat');
if ($electro && $af && isset($por_term[$electro->term_id])
        && !isset($por_term[$af->term_id])) {
    wp_update_nav_menu_item($menu->term_id, 0, array(
        'menu-item-type' => 'taxonomy',
        'menu-item-object' => 'product_cat',
        'menu-item-object-id' => $af->term_id,
        'menu-item-parent-id' => $por_term[$electro->term_id]->ID,
        'menu-item-title' => 'Air Fryers',
        'menu-item-status' => 'publish',
    ));
    echo "menu: Electrodomésticos -> Air Fryers\n";
}

// 2) columna "Más categorías" (crear si falta)
$mas = null;
foreach ($items as $it) {
    if ((int) $it->menu_item_parent === (int) $cat_item->ID
            && stripos($it->title, 'Más categor') !== false) {
        $mas = $it;
    }
}
if (!$mas) {
    $mid = wp_update_nav_menu_item($menu->term_id, 0, array(
        'menu-item-type' => 'custom',
        'menu-item-url' => '/tienda/',
        'menu-item-parent-id' => $cat_item->ID,
        'menu-item-title' => 'Más categorías',
        'menu-item-status' => 'publish',
    ));
    if (is_wp_error($mid)) {
        echo "FASE33-ERROR creando columna: " . $mid->get_error_message() . "\n";
        return;
    }
    $mas = (object) array('ID' => $mid);
    echo "columna 'Más categorías' creada\n";
}

// 3) mover los rubros SIN subcategorías bajo esa columna
$con_columna = array('Electrodomésticos', 'Hogar y Cocina');
$movidos = 0;
foreach ($items as $it) {
    if ($it->object !== 'product_cat') {
        continue;
    }
    if ((int) $it->menu_item_parent !== (int) $cat_item->ID) {
        continue;  // ya es subcat o está en otra parte
    }
    if (in_array($it->title, $con_columna, true)) {
        continue;
    }
    $term = get_term((int) $it->object_id, 'product_cat');
    if ($term && !is_wp_error($term)
            && get_term_children($term->term_id, 'product_cat')) {
        continue;  // tiene hijos: columna propia, no tocar
    }
    $r = wp_update_nav_menu_item($menu->term_id, $it->db_id, array(
        'menu-item-type' => 'taxonomy',
        'menu-item-object' => 'product_cat',
        'menu-item-object-id' => (int) $it->object_id,
        'menu-item-parent-id' => $mas->ID,
        'menu-item-title' => $it->title,
        'menu-item-status' => 'publish',
    ));
    if (is_wp_error($r)) {
        echo "error moviendo {$it->title}: " . $r->get_error_message() . "\n";
    } else {
        $movidos++;
        echo "movido a Más categorías: {$it->title}\n";
    }
}
echo "FASE33-OK movidos=$movidos\n";
