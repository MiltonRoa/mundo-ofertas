#!/bin/bash
# Fase 2 MaxDominios: Flatsome + identidad Tarvo + portada
set -uo pipefail
export PATH=$HOME/bin:$PATH
PH=$HOME/public_html
WP="wp --path=$PH"
RAW=https://raw.githubusercontent.com/MiltonRoa/mundo-ofertas/main/web

echo "== temas =="
$WP theme install $HOME/flatsome-3.20.9.zip 2>&1 | tail -1
$WP theme install $HOME/flatsome-child.zip --activate 2>&1 | tail -1
$WP theme list --fields=name,status

echo "== logo e identidad =="
curl -sSLo /tmp/tarvo.png $RAW/tarvo.png
curl -sSLo /tmp/tarvo_icon.png $RAW/tarvo_icon.png
LOGO=$($WP media import /tmp/tarvo.png --title="Logo Tarvo" --porcelain | tail -1)
ICON=$($WP media import /tmp/tarvo_icon.png --title="Icono Tarvo" --porcelain | tail -1)
$WP theme mod set site_logo "$LOGO" >/dev/null
$WP theme mod set logo_max_width 160 >/dev/null
$WP option update site_icon "$ICON" >/dev/null
$WP theme mod set color_primary '#2445ff' >/dev/null
$WP theme mod set color_success '#25D366' >/dev/null
$WP theme mod set topbar_left '<strong>Compra gestionada por Tarvo</strong> — 📲 0992 805 800' >/dev/null
$WP theme mod set topbar_bg '#151c32' >/dev/null
$WP theme mod set footer_left_text '© 2026 <strong>Tarvo</strong> — Compra gestionada en Paraguay · 📲 <a href="https://wa.me/595992805800">0992 805 800</a>' >/dev/null
$WP eval 'set_theme_mod("topbar_elements_right", array()); set_theme_mod("payment_icons", array()); set_theme_mod("footer_right_text", " "); echo "mods ok\n";'
echo "logo=$LOGO icon=$ICON"

echo "== paginas =="
COMO=$($WP post create --post_type=page --post_status=publish --post_title="Cómo comprar" --porcelain --post_content='<h2>Comprar en Tarvo es simple</h2>
<ol>
<li><strong>Elegí tu producto</strong> en la tienda: el precio que ves es el precio final en guaraníes.</li>
<li><strong>Pedilo por WhatsApp</strong> con el botón de cada producto (o agregalo al carrito y finalizá el pedido).</li>
<li><strong>Confirmamos tu pedido</strong> y coordinamos el pago en guaraníes.</li>
<li><strong>Recibís tu compra</strong>:
<ul>
<li><em>Productos nacionales</em>: entrega rápida en Paraguay, con delivery incluido (zona de cobertura y recargo a coordinar).</li>
<li><em>Productos importados de USA</em>: te los traemos del exterior, llegan en 2 a 3 semanas. Precio final, todo incluido.</li>
</ul></li>
</ol>
<p>Toda compra es gestionada por Tarvo. ¿Dudas? Escribinos al <a href="https://wa.me/595992805800">0992 805 800</a>.</p>')
CONTACTO=$($WP post create --post_type=page --post_status=publish --post_title="Contacto" --porcelain --post_content='<h2>Hablemos</h2>
<p>📲 WhatsApp: <a href="https://wa.me/595992805800">0992 805 800</a> (pedidos y consultas)</p>
<p>📣 Canal de ofertas en Telegram: <a href="https://t.me/tarvopy">t.me/tarvopy</a></p>
<p>Atendemos en horario comercial, Asunción, Paraguay.</p>')
curl -sSLo /tmp/inicio.txt $RAW/inicio.txt
INICIO=$($WP post create /tmp/inicio.txt --post_type=page --post_status=publish --post_title='Inicio' --porcelain)
$WP post meta update "$INICIO" _wp_page_template page-blank.php >/dev/null
$WP option update show_on_front page >/dev/null
$WP option update page_on_front "$INICIO" >/dev/null
$WP rewrite flush >/dev/null 2>&1 || true
echo "como=$COMO contacto=$CONTACTO inicio=$INICIO"
echo "== FIN FASE 2 =="
