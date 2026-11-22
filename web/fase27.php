<?php
// FASE 27 (22/11): EMERGENCIA DISCO LLENO en MaxDominios — las altas de
// woo_sync fallan con "Disk quota exceeded" desde hace ~2-3 dias y los
// productos nuevos no entran a la web. Causa probable: las fotos de los
// productos rotados (bajas de la ventana 30d) quedan huerfanas — la poda
// semanal quedo solo en el staging EC2, nunca se instalo en el hosting.
// Uso: php ~/bin/wp eval-file ~/fase27.php
global $wpdb;
$mb = 0.0;

// 1) adjuntos huerfanos (el producto padre ya no existe)
$ids = $wpdb->get_col(
    "SELECT p.ID FROM {$wpdb->posts} p
     LEFT JOIN {$wpdb->posts} pa ON p.post_parent = pa.ID
     WHERE p.post_type = 'attachment' AND p.post_parent > 0 AND pa.ID IS NULL"
);
foreach ($ids as $id) {
    $f = get_attached_file((int) $id);
    if ($f && file_exists($f)) {
        $mb += filesize($f) / 1048576;
        // las miniaturas se borran con wp_delete_attachment; sumo aprox x3
        $mb += filesize($f) / 1048576 * 2;
    }
    wp_delete_attachment((int) $id, true);
}
echo count($ids) . " adjuntos huerfanos borrados\n";

// 2) adjuntos colgados de productos en papelera + vaciar papelera
$trash = $wpdb->get_col(
    "SELECT ID FROM {$wpdb->posts} WHERE post_status = 'trash'");
$na = 0;
foreach ($trash as $tid) {
    foreach (get_attached_media('', (int) $tid) as $att) {
        $f = get_attached_file($att->ID);
        if ($f && file_exists($f)) {
            $mb += filesize($f) / 1048576 * 3;
        }
        wp_delete_attachment($att->ID, true);
        $na++;
    }
    wp_delete_post((int) $tid, true);
}
echo count($trash) . " posts de papelera purgados (+$na fotos)\n";

// 3) transients vencidos (basura de la base)
$t = $wpdb->query(
    "DELETE FROM {$wpdb->options}
     WHERE option_name LIKE '%\_transient\_%'");
echo "$t transients borrados\n";

echo "aprox " . round($mb) . " MB liberados en uploads\n";
echo "FASE27-OK\n";
