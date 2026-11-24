<?php
// FASE 36 (24/11, Milton "en moda y accesorio necesitamos segmentar"):
// GENERALIZA el mega-menú — todo rubro CON subcategorías se promueve a
// columna propia del dropdown Categorías (si estaba en "Más categorías"
// se saca de ahí) y se le cuelgan sus subcats no vacías. Idempotente;
// sirve también para futuras segmentaciones (correr de nuevo y listo).
// Uso: wp eval-file fase36.php
$menu = wp_get_nav_menu_object('Principal');
if (!$menu) { echo "FASE36-ERROR: sin menu Principal\n"; return; }
$items = wp_get_nav_menu_items($menu->term_id);
$cat_item = null; $mas_item = null; $por_term = array();
foreach ($items as $it) {
    if (!$cat_item && $it->object !== 'product_cat'
            && stripos($it->title, 'Categor') !== false) { $cat_item = $it; }
    if (!$mas_item && stripos($it->title, 'Más categor') !== false) {
        $mas_item = $it;
    }
    if ($it->object === 'product_cat') {
        $por_term[(int) $it->object_id] = $it;
    }
}
if (!$cat_item) { echo "FASE36-ERROR: sin dropdown Categorias\n"; return; }

$rubros = get_terms(array('taxonomy' => 'product_cat', 'parent' => 0,
                          'hide_empty' => true));
$prom = 0; $hijos_n = 0;
foreach ($rubros as $rubro) {
    $hijos = get_terms(array('taxonomy' => 'product_cat',
                             'parent' => $rubro->term_id,
                             'hide_empty' => true));
    if (!$hijos) { continue; }  // sin subcats: queda donde esté
    // 1) el rubro como hijo DIRECTO de Categorías
    if (isset($por_term[$rubro->term_id])) {
        $it = $por_term[$rubro->term_id];
        if ((int) $it->menu_item_parent !== (int) $cat_item->ID) {
            wp_update_nav_menu_item($menu->term_id, $it->db_id, array(
                'menu-item-type' => 'taxonomy',
                'menu-item-object' => 'product_cat',
                'menu-item-object-id' => $rubro->term_id,
                'menu-item-parent-id' => $cat_item->ID,
                'menu-item-title' => $it->title,
                'menu-item-status' => 'publish'));
            $prom++;
            echo "columna propia: {$rubro->name}\n";
            $padre_id = $it->db_id;
        } else { $padre_id = $it->ID; }
    } else {
        $padre_id = wp_update_nav_menu_item($menu->term_id, 0, array(
            'menu-item-type' => 'taxonomy',
            'menu-item-object' => 'product_cat',
            'menu-item-object-id' => $rubro->term_id,
            'menu-item-parent-id' => $cat_item->ID,
            'menu-item-title' => $rubro->name,
            'menu-item-status' => 'publish'));
        echo "rubro agregado: {$rubro->name}\n";
    }
    // 2) subcats colgadas
    foreach ($hijos as $h) {
        if (isset($por_term[$h->term_id])) { continue; }
        $r = wp_update_nav_menu_item($menu->term_id, 0, array(
            'menu-item-type' => 'taxonomy',
            'menu-item-object' => 'product_cat',
            'menu-item-object-id' => $h->term_id,
            'menu-item-parent-id' => $padre_id,
            'menu-item-title' => $h->name,
            'menu-item-status' => 'publish'));
        if (!is_wp_error($r)) {
            $hijos_n++;
            echo "  + {$h->name}\n";
        }
    }
}
echo "FASE36-OK columnas_promovidas=$prom subcats_agregadas=$hijos_n\n";
