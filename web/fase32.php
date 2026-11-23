<?php
/**
 * FASE 32 (23/11) — rubro nuevo "Mascotas" en el menú Principal,
 * colgado del dropdown "Categorías" (después del caso Crok Crok 25kg
 * clasificado en Niños y Bebés). Idempotente.
 * Uso: wp eval-file fase32.php
 */
$menu = wp_get_nav_menu_object('Principal');
if (!$menu) {
    echo "FASE32-ERROR: no encuentro el menu Principal\n";
    return;
}
$term = get_term_by('name', 'Mascotas', 'product_cat');
if (!$term) {
    echo "FASE32-ERROR: no existe la categoria Mascotas\n";
    return;
}
$items = wp_get_nav_menu_items($menu->term_id);
$padre = null;
foreach ($items as $it) {
    if ($it->object === 'product_cat' && (int) $it->object_id === $term->term_id) {
        echo "FASE32-OK ya estaba en el menu\n";
        return;
    }
    if (!$padre && stripos($it->title, 'Categor') !== false) {
        $padre = $it;  // el dropdown "Categorías"
    }
}
$r = wp_update_nav_menu_item($menu->term_id, 0, array(
    'menu-item-type' => 'taxonomy',
    'menu-item-object' => 'product_cat',
    'menu-item-object-id' => $term->term_id,
    'menu-item-parent-id' => $padre ? $padre->ID : 0,
    'menu-item-title' => 'Mascotas',
    'menu-item-status' => 'publish',
));
if (is_wp_error($r)) {
    echo "FASE32-ERROR: " . $r->get_error_message() . "\n";
} else {
    echo "FASE32-OK Mascotas agregada bajo '"
        . ($padre ? $padre->title : 'raiz') . "'\n";
}
