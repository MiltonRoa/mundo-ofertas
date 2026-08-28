<?php
/**
 * Fase 16b — pulido post-eliminación de categorías de origen:
 * etiquetas de botones, títulos H3 de secciones, frase del CTA y
 * links muertos del footer (custom_html fijo). Los H2 de los slides
 * ("Entrega rápida con delivery incluido" / "Importados de USA") se
 * QUEDAN: son copy de marketing, no links de categoría.
 * Correr: php ~/bin/wp eval-file ~/fase16b.php
 */

echo "== PORTADA ==\n";
$fid = (int) get_option('page_on_front');
$p = get_post($fid);
$c = $p->post_content;
$reps = [
    ['<span>Ver nacionales</span>', '<span>Ver productos</span>'],
    ['<span>Ver importados</span>', '<span>Ver productos</span>'],
    ['Todos los nacionales', 'Ver más Tecnología'],
    ['Todos los importados', 'Ver más de Hogar y Cocina'],
    ['🇵🇾 Entrega rápida</h3>', '💻 Tecnología</h3>'],
    ['✈️ Importados de USA</h3>', '🍳 Hogar y Cocina</h3>'],
    ['te lo conseguimos — nacionales o importado de USA.',
     'te lo conseguimos al mejor precio.'],
];
foreach ($reps as $r) {
    $n = substr_count($c, $r[0]);
    if ($n) { $c = str_replace($r[0], $r[1], $c); echo "  [$n] {$r[0]}\n"; }
}
if ($c !== $p->post_content) {
    wp_update_post(['ID' => $fid, 'post_content' => $c]);
    echo "portada actualizada\n";
} else {
    echo "portada sin cambios\n";
}

echo "== FOOTER ==\n";
$wh = get_option('widget_custom_html');
$ch = false;
if (is_array($wh)) {
    foreach ($wh as $i => $w) {
        if (!is_array($w) || !isset($w['content'])) continue;
        $n = preg_replace(
            '~<li><a href="[^"]*categoria-producto/(nacionales|importados)/"[^>]*>[^<]*</a></li>~u',
            '', $w['content'], -1, $cnt);
        if ($cnt) { $wh[$i]['content'] = $n; $ch = true; echo "  widget $i: $cnt links muertos fuera\n"; }
    }
}
if ($ch) { update_option('widget_custom_html', $wh); echo "footer actualizado\n"; }
else { echo "footer sin cambios\n"; }

wp_cache_flush();
echo "== FASE 16b LISTA ==\n";
