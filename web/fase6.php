<?php
// Fase 6 Tarvo — tarjetas de categoría con foto (estilo Amazon) + CSS pro.
// Correr con: wp eval-file fase6.php  (idempotente)
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/image.php';
$raw = 'https://raw.githubusercontent.com/MiltonRoa/mundo-ofertas/main/web';

// 1) imágenes de tarjeta (idempotente por título)
$defs = [
    ['cat1', 'Tecnología',            '/categoria-producto/tecnologia/'],
    ['cat2', 'Electrodomésticos',     '/categoria-producto/electrodomesticos/'],
    ['cat3', 'Hogar y Cocina',        '/categoria-producto/hogar-y-cocina/'],
    ['cat4', 'Niños y Bebés',         '/categoria-producto/ninos-y-bebes/'],
    ['cat5', 'Deportes',              '/categoria-producto/deportes-y-aire-libre/'],
    ['cat6', 'Moda y Accesorios',     '/categoria-producto/moda-y-accesorios/'],
    ['cat7', 'Belleza y Cuidado',     '/categoria-producto/belleza-y-cuidado/'],
    ['cat8', 'Toda la tienda',        '/tienda/'],
];
$cards = "[section padding=\"18px\" padding__sm=\"10px\"]\n[row]\n[col span=\"12\" align=\"center\"]\n<h2 style=\"margin-bottom:14px\">Comprá por categoría</h2>\n[/col]\n[/row]\n[row row_spacing=\"small\"]\n";
foreach ($defs as $d) {
    [$slug, $label, $link] = $d;
    $q = get_posts(['post_type' => 'attachment', 'title' => "tarvo-$slug", 'numberposts' => 1]);
    if ($q) {
        $u = wp_get_attachment_url($q[0]->ID);
    } else {
        $u = media_sideload_image("$raw/$slug.jpg", 0, "tarvo-$slug", 'src');
        if (is_wp_error($u)) { echo "ERR $slug: " . $u->get_error_message() . "\n"; exit; }
    }
    echo "$slug ok\n";
    $cards .= "[col span=\"3\" span__sm=\"6\" padding=\"0px\"]\n"
        . "[ux_banner height=\"150px\" height__sm=\"110px\" bg=\"$u\" bg_size=\"original\" bg_overlay=\"rgba(15,20,38,.45)\" hover=\"zoom\" link=\"$link\"]\n"
        . "[text_box width=\"90\" position_x=\"50\" position_y=\"50\"]\n"
        . "<h4 style=\"color:#fff;margin:0;text-shadow:0 1px 8px rgba(0,0,0,.5)\">$label</h4>\n"
        . "[/text_box]\n[/ux_banner]\n[/col]\n";
}
$cards .= "[/row]\n[/section]";

// 2) reemplazar el bloque de botones planos por las tarjetas
$pg = get_page_by_path('inicio');
if (!$pg) { echo "ERR: no hay pagina inicio\n"; exit; }
$c = $pg->post_content;
$nuevo = preg_replace('/\[section padding="18px".*?\[\/section\]/s', $cards, $c, 1, $n);
if ($n === 1) {
    wp_update_post(['ID' => $pg->ID, 'post_content' => $nuevo]);
    echo "tarjetas aplicadas en inicio {$pg->ID}\n";
} else {
    echo "AVISO: no encontre el bloque de botones (n=$n) — sin cambios en inicio\n";
}

// 3) CSS global pro: botones redondeados, precios marcados, hover con vida
$css = <<<CSS
/* Tarvo — pulido global */
.button, button, input[type="submit"] { border-radius: 99px !important; text-transform: none; letter-spacing: 0; }
.button.is-outline { border-width: 2px; }
.button.primary:not(.is-outline):hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(36,69,255,.30); }
.button.success:not(.is-outline):hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(37,211,102,.35); }
.price, .product-small .price { font-weight: 700; color: #151c32; }
.product-small .box-text { padding-top: 8px; padding-bottom: 12px; }
.product-small .title { font-size: .95em; }
h1, h2, h3 { letter-spacing: -0.02em; }
.header-main { box-shadow: 0 1px 8px rgba(0,0,0,.06); }
CSS;
wp_update_custom_css_post($css);
echo "css aplicado\n";
echo "FIN FASE 6 OK\n";
