<?php
/**
 * Fase 17 — "Recién llegados" duplicaba a "Lo nuevo de hoy" (ambos por
 * fecha). Se convierte en "✨ Descubrí Tarvo": selección ALEATORIA que
 * cambia en cada visita. Correr: php ~/bin/wp eval-file ~/fase17.php
 */
$fid = (int) get_option('page_on_front');
$p = get_post($fid);
$c = $p->post_content;

$pos = strpos($c, 'Recién llegados');
if ($pos === false) { echo "no encontré 'Recién llegados'\n"; return; }
echo "contexto: ..." . str_replace("\n", " ", substr($c, max(0, $pos - 60), 200)) . "...\n";

// retitular
$c = str_replace('Recién llegados', '✨ Descubrí Tarvo', $c);

// el [ux_products] que sigue al título pasa a orden aleatorio
$pos = strpos($c, '✨ Descubrí Tarvo');
$ux = strpos($c, '[ux_products', $pos);
if ($ux !== false) {
    $fin = strpos($c, ']', $ux);
    $short = substr($c, $ux, $fin - $ux + 1);
    echo "shortcode antes: $short\n";
    if (strpos($short, 'orderby=') !== false) {
        $nuevo = preg_replace('~orderby="[^"]*"~', 'orderby="rand"', $short);
    } else {
        $nuevo = str_replace('[ux_products', '[ux_products orderby="rand"', $short);
    }
    // sin order="desc" con rand
    $nuevo = preg_replace('~\s*order="[^"]*"~', '', $nuevo);
    $c = substr($c, 0, $ux) . $nuevo . substr($c, $fin + 1);
    echo "shortcode después: $nuevo\n";
} else {
    echo "OJO: no encontré [ux_products] tras el título\n";
}

if ($c !== $p->post_content) {
    wp_update_post(['ID' => $fid, 'post_content' => $c]);
    echo "portada actualizada\n";
}
wp_cache_flush();
echo "== FASE 17 LISTA ==\n";
