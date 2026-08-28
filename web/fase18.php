<?php
/**
 * Fase 18 — portada "más pro y más larga" (28/08, Milton): se conserva
 * hero + "Lo nuevo de hoy" + tarjetas de categoría, y se RECONSTRUYE todo
 * lo que sigue: franja de confianza, 6 bandas de rubro a lo ancho (fondos
 * alternados, botones centrados y alineados), "Descubrí Tarvo" (rand),
 * "¿Cómo comprar?" en 3 pasos y CTA final de WhatsApp.
 * Correr: php ~/bin/wp eval-file ~/fase18.php
 */
$fid = (int) get_option('page_on_front');
$p = get_post($fid);
$c = $p->post_content;

// head = hasta el cierre de la 3ra seccion (hero + lo-nuevo + tarjetas cat)
$pos = 0;
for ($i = 0; $i < 3; $i++) {
    $pos = strpos($c, '[/section]', $pos);
    if ($pos === false) { echo "ESTRUCTURA INESPERADA (seccion $i) — abortando\n"; return; }
    $pos += 10;
}
$head = substr($c, 0, $pos);
echo "head termina en: ..." . str_replace("\n", " ", substr($head, -140)) . "\n";

$ids = [];
foreach (['tecnologia', 'hogar-y-cocina', 'electrodomesticos', 'ninos-y-bebes',
          'deportes-y-aire-libre', 'moda-y-accesorios'] as $slug) {
    $t = get_term_by('slug', $slug, 'product_cat');
    if (!$t) { echo "FALTA rubro $slug — abortando\n"; return; }
    $ids[$slug] = $t->term_id;
}

function banda($emoji, $nombre, $slug, $id, $gris) {
    $bg = $gris ? ' bg_color="rgb(247,248,252)"' : ' bg_color="rgb(255,255,255)"';
    return '[section' . $bg . ' padding="42px"]'
        . '[title style="center" text="' . $emoji . ' ' . $nombre . '" size="120"]'
        . '[ux_products style="normal" columns="5" columns__md="3" products="10" '
        . 'cat="' . $id . '" show_rating="false"]'
        . '[row h_align="center"][col span="12" align="center"]'
        . '[button text="Ver más de ' . $nombre . '" color="primary" style="outline" '
        . 'size="large" link="/categoria-producto/' . $slug . '/"]'
        . '[/col][/row][/section]' . "\n";
}

$body = '';
// franja de confianza
$body .= '[section bg_color="rgb(15,26,56)" padding="30px"][row]'
    . '[col span="3" align="center"]<p style="color:#fff;margin:0;line-height:1.35">🚚<br><strong>Delivery incluido</strong><br><span style="opacity:.7;font-size:.88em">en entrega rápida</span></p>[/col]'
    . '[col span="3" align="center"]<p style="color:#fff;margin:0;line-height:1.35">💵<br><strong>Precio final en Gs</strong><br><span style="opacity:.7;font-size:.88em">sin sorpresas</span></p>[/col]'
    . '[col span="3" align="center"]<p style="color:#fff;margin:0;line-height:1.35">✈️<br><strong>Traemos de USA</strong><br><span style="opacity:.7;font-size:.88em">en 2 a 3 semanas</span></p>[/col]'
    . '[col span="3" align="center"]<p style="color:#fff;margin:0;line-height:1.35">📱<br><strong>Pedís por WhatsApp</strong><br><span style="opacity:.7;font-size:.88em">atención personal</span></p>[/col]'
    . '[/row][/section]' . "\n";
// bandas de rubro (fondos alternados)
$g = true;
foreach ([['💻', 'Tecnología', 'tecnologia'],
          ['🍳', 'Hogar y Cocina', 'hogar-y-cocina'],
          ['🔌', 'Electrodomésticos', 'electrodomesticos'],
          ['🧸', 'Niños y Bebés', 'ninos-y-bebes'],
          ['⚽', 'Deportes y Aire Libre', 'deportes-y-aire-libre'],
          ['👜', 'Moda y Accesorios', 'moda-y-accesorios']] as $r) {
    $body .= banda($r[0], $r[1], $r[2], $ids[$r[2]], $g);
    $g = !$g;
}
// descubri tarvo (rand)
$body .= '[section bg_color="rgb(255,255,255)" padding="42px"]'
    . '[title style="center" text="✨ Descubrí Tarvo" size="120"]'
    . '[ux_products style="normal" columns="5" columns__md="3" products="10" orderby="rand" show_rating="false"]'
    . '[/section]' . "\n";
// como comprar
$body .= '[section bg_color="rgb(247,248,252)" padding="44px"]'
    . '[title style="center" text="¿Cómo comprar en Tarvo?" size="120"][row]'
    . '[col span="4" align="center"]<p style="margin:0;line-height:1.4"><span style="font-size:2em">1️⃣</span><br><strong>Elegí tus productos</strong><br><span style="color:#5a668c">Mirá el catálogo y elegí lo que te guste.</span></p>[/col]'
    . '[col span="4" align="center"]<p style="margin:0;line-height:1.4"><span style="font-size:2em">2️⃣</span><br><strong>Pedilos por WhatsApp</strong><br><span style="color:#5a668c">Confirmamos precio y entrega al instante.</span></p>[/col]'
    . '[col span="4" align="center"]<p style="margin:0;line-height:1.4"><span style="font-size:2em">3️⃣</span><br><strong>Recibilos en tu puerta</strong><br><span style="color:#5a668c">Delivery incluido; importados en 2 a 3 semanas.</span></p>[/col]'
    . '[/row][row h_align="center"][col span="12" align="center"]'
    . '[button text="Ver la guía completa" color="primary" style="outline" size="large" link="/como-comprar/"]'
    . '[/col][/row][/section]' . "\n";
// CTA final
$body .= '[section bg_color="rgb(15,26,56)" padding="52px"][row h_align="center"]'
    . '[col span="12" align="center"]'
    . '<h2 style="color:#fff;margin-bottom:.2em">¿Buscás algo en particular?</h2>'
    . '<p style="color:#cfd8ff;margin-bottom:1.2em">Escribinos y te lo conseguimos al mejor precio.</p>'
    . '[button text="📱 Escribinos por WhatsApp" color="success" size="large" link="https://wa.me/595992805800" target="_blank"]'
    . '[/col][/row][/section]' . "\n";

wp_update_post(['ID' => $fid, 'post_content' => $head . "\n" . $body]);
wp_cache_flush();
echo "portada reconstruida: " . strlen($head) . " (head) + " . strlen($body) . " (body nuevo)\n";
echo "== FASE 18 LISTA ==\n";
