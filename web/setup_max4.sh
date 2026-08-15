#!/bin/bash
# Fase 4: portada PRO con carrusel (revierte tienda-como-portada, que Woo no soporta)
set -uo pipefail
export PATH=$HOME/bin:$PATH
PH=$HOME/public_html
WP="wp --path=$PH"
RAW=https://raw.githubusercontent.com/MiltonRoa/mundo-ofertas/main/web

echo "== fondos del carrusel =="
for f in hero1 hero2 hero3; do curl -sSLo /tmp/$f.jpg $RAW/$f.jpg; done
F1=$($WP media import /tmp/hero1.jpg --title="Hero living" --porcelain | tail -1)
F2=$($WP media import /tmp/hero2.jpg --title="Hero escritorio" --porcelain | tail -1)
F3=$($WP media import /tmp/hero3.jpg --title="Hero cocina" --porcelain | tail -1)
U1=$($WP eval "echo wp_get_attachment_url($F1);")
U2=$($WP eval "echo wp_get_attachment_url($F2);")
U3=$($WP eval "echo wp_get_attachment_url($F3);")
echo "F1=$F1 F2=$F2 F3=$F3"

echo "== contenido Inicio =="
curl -sSLo /tmp/inicio2.txt $RAW/inicio2.txt
sed -i "s|{{FONDO1}}|$U1|; s|{{FONDO2}}|$U2|; s|{{FONDO3}}|$U3|" /tmp/inicio2.txt
INICIO=$($WP post list --post_type=page --name=inicio --field=ID | head -1)
if [ -z "$INICIO" ]; then
  INICIO=$($WP post create /tmp/inicio2.txt --post_type=page --post_status=publish --post_title='Inicio' --porcelain)
else
  php -r '$c=file_get_contents("/tmp/inicio2.txt"); file_put_contents("/tmp/inicio2.txt",$c);'
  $WP post update "$INICIO" /tmp/inicio2.txt >/dev/null
fi
$WP post meta update "$INICIO" _wp_page_template page-blank.php >/dev/null
$WP option update show_on_front page >/dev/null
$WP option update page_on_front "$INICIO" >/dev/null
echo "inicio=$INICIO"

echo "== footer y franja (re-fix, sin tipeo: acentos seguros) =="
$WP theme mod set footer_left_text '© 2026 <strong>Tarvo</strong> — Compra gestionada en Paraguay · 📲 <a href="https://wa.me/595992805800">0992 805 800</a>' >/dev/null
$WP eval 'set_theme_mod("footer_right_text"," "); set_theme_mod("payment_icons", array()); set_theme_mod("topbar_elements_right", array()); echo "mods ok\n";'
$WP theme mod set html_shop_page_content '<div style="text-align:center;padding:10px 0;font-size:.95em">🇵🇾 <b>Nacionales</b> con delivery incluido &nbsp;·&nbsp; ✈️ <b>Importados de USA</b> precio final todo incluido &nbsp;·&nbsp; 📲 Pedís por <b>WhatsApp</b></div>' >/dev/null
$WP rewrite flush >/dev/null 2>&1 || true
$WP cache flush >/dev/null 2>&1 || true
echo "== FIN FASE 4 =="
