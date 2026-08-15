<?php
// Fase 9 Tarvo — pie de página pro (incluye lo de fase 7, por si no corrió)
// + letras grandes en tarjetas de categoría. wp eval-file fase9.php

// ---- pie de página: fuera widgets de blog, columnas útiles ----
$terms = get_terms(['taxonomy' => 'product_cat', 'hide_empty' => true]);
$origen = []; $rubros = [];
foreach ($terms as $t) {
    if (in_array($t->slug, ['uncategorized', 'sin-categorizar', 'otros'])) continue;
    $li = '<li><a href="' . esc_url(get_term_link($t)) . '">' . esc_html($t->name) . '</a></li>';
    if (in_array($t->slug, ['nacionales', 'importados'])) $origen[] = $li; else $rubros[] = $li;
}
$cats_html = '<ul class="tarvo-foot">' . implode('', array_merge($origen, $rubros)) . '</ul>';
$info_html = '<ul class="tarvo-foot">'
    . '<li><a href="/tienda/">Toda la tienda</a></li>'
    . '<li><a href="/como-comprar/">Cómo comprar</a></li>'
    . '<li><a href="/contacto/">Contacto</a></li>'
    . '<li><a href="/carrito/">Mi carrito</a></li></ul>';
$contacto_html = '<ul class="tarvo-foot">'
    . '<li>📲 <a href="https://wa.me/595992805800"><span style="white-space:nowrap">WhatsApp 0992 805 800</span></a></li>'
    . '<li>📣 <a href="https://t.me/tarvopy">Canal de ofertas en Telegram</a></li>'
    . '<li>🇵🇾 Asunción, Paraguay</li>'
    . '<li>Nacionales: delivery incluido</li>'
    . '<li>Importados de USA: 2 a 3 semanas</li></ul>';
update_option('widget_custom_html', [
    10 => ['title' => 'Categorías', 'content' => $cats_html],
    11 => ['title' => 'Tarvo', 'content' => $info_html],
    12 => ['title' => 'Hablemos', 'content' => $contacto_html],
    '_multiwidget' => 1,
]);
$sw = get_option('sidebars_widgets');
$sw['sidebar-main'] = [];
$sw['sidebar-footer-2'] = [];
$sw['sidebar-footer-1'] = ['custom_html-10', 'custom_html-11', 'custom_html-12'];
update_option('sidebars_widgets', $sw);
echo "footer pro ok\n";

// ---- CSS: footer navy + tarjetas con letra grande ----
$css = wp_get_custom_css();
if (strpos($css, 'footer pro') === false) {
    $css .= <<<CSS

/* Tarvo — footer pro */
.footer-widgets { background: #151c32; color: #cfd8ff; padding-top: 34px; padding-bottom: 20px; }
.footer-widgets .widget-title, .footer-widgets h4 { color: #fff; }
.footer-widgets a { color: #cfd8ff; }
.footer-widgets a:hover { color: #fff; }
ul.tarvo-foot { list-style: none; margin: 0; }
ul.tarvo-foot li { margin-bottom: 7px; }
.absolute-footer, .absolute-footer.dark { background: #10162a; color: #9aa4c7; }
CSS;
}
if (strpos($css, 'labels v2') === false) {
    $css .= <<<CSS

/* Tarvo — labels v2 (tarjetas de categoría) */
.banner h4 { font-size: 1.6em !important; font-weight: 700; text-shadow: 0 2px 12px rgba(0,0,0,.55); }
@media (max-width: 549px) { .banner h4 { font-size: 1.25em !important; } }
CSS;
}
wp_update_custom_css_post($css);
echo "css ok\n";
echo "FIN FASE 9 OK\n";
