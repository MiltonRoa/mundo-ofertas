<?php
/**
 * Fase 16 — Tarvo web (28/08, Milton): eliminar las categorías de origen
 * ("Entrega rápida" ex Nacionales y "Importados de USA" ex Importados) —
 * todo producto ya vive en su rubro (los 9 suman 838/838). Antes de borrar:
 * reapuntar portada y menú para no dejar nada roto.
 * Correr: php ~/bin/wp eval-file ~/fase16.php  (desde ~/public_html)
 */

$nac = get_term_by('slug', 'nacionales', 'product_cat');
$imp = get_term_by('slug', 'importados', 'product_cat');
$tec = get_term_by('slug', 'tecnologia', 'product_cat');
$hog = get_term_by('slug', 'hogar-y-cocina', 'product_cat');
if (!$nac || !$imp || !$tec || !$hog) { echo "FALTA ALGUN TERM — abortando\n"; return; }
echo "ids: nac={$nac->term_id} imp={$imp->term_id} tec={$tec->term_id} hog={$hog->term_id}\n";

echo "== PORTADA ==\n";
$fid = (int) get_option('page_on_front');
$p = get_post($fid);
$c = $p->post_content;
// contexto de cada mención (diagnóstico en pantalla)
foreach (['nacionales', 'importados', 'Entrega rápida', 'Importados de USA',
          '"' . $nac->term_id . '"', '"' . $imp->term_id . '"'] as $needle) {
    $off = 0;
    while (($pos = strpos($c, $needle, $off)) !== false) {
        echo "  [$needle] ..." . str_replace("\n", " ", substr($c, max(0, $pos - 40), 90)) . "...\n";
        $off = $pos + 1;
    }
}
// reemplazos: títulos de sección + links + atributos por slug y por id
$c = str_replace('text="Entrega rápida"', 'text="Tecnología"', $c);
$c = str_replace('text="Importados de USA"', 'text="Hogar y Cocina"', $c);
$c = str_replace('/categoria-producto/nacionales', '/categoria-producto/tecnologia', $c);
$c = str_replace('/categoria-producto/importados', '/categoria-producto/hogar-y-cocina', $c);
foreach ([['cat="nacionales"', 'cat="tecnologia"'],
          ['cat_slug="nacionales"', 'cat_slug="tecnologia"'],
          ['cat="importados"', 'cat="hogar-y-cocina"'],
          ['cat_slug="importados"', 'cat_slug="hogar-y-cocina"'],
          ['cat="' . $nac->term_id . '"', 'cat="' . $tec->term_id . '"'],
          ['cat="' . $imp->term_id . '"', 'cat="' . $hog->term_id . '"'],
          ['ids="' . $nac->term_id . '"', 'ids="' . $tec->term_id . '"'],
          ['ids="' . $imp->term_id . '"', 'ids="' . $hog->term_id . '"']] as $par) {
    $c = str_replace($par[0], $par[1], $c);
}
if ($c !== $p->post_content) {
    wp_update_post(['ID' => $fid, 'post_content' => $c]);
    echo "portada $fid reapuntada a rubros\n";
} else {
    echo "portada sin cambios (revisar contexto de arriba)\n";
}

echo "== MENU ==\n";
$items = get_posts(['post_type' => 'nav_menu_item', 'numberposts' => -1]);
foreach ($items as $it) {
    $obj = get_post_meta($it->ID, '_menu_item_object', true);
    $oid = (int) get_post_meta($it->ID, '_menu_item_object_id', true);
    if ($obj === 'product_cat' && in_array($oid, [$nac->term_id, $imp->term_id])) {
        wp_delete_post($it->ID, true);
        echo "menu item {$it->ID} (term $oid) eliminado\n";
    }
}

echo "== CATEGORIAS ==\n";
wp_delete_term($nac->term_id, 'product_cat');
echo "categoria 'Entrega rápida' (nacionales) eliminada\n";
wp_delete_term($imp->term_id, 'product_cat');
echo "categoria 'Importados de USA' (importados) eliminada\n";

wp_cache_flush();
echo "== FASE 16 LISTA ==\n";
