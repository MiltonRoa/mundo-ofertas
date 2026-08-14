#!/bin/bash
# Fase 3 MaxDominios: menú + widgets (correr DESPUÉS del primer woo_sync)
set -uo pipefail
export PATH=$HOME/bin:$PATH
WP="wp --path=$HOME/public_html"

echo "== menu Principal =="
$WP menu delete principal 2>/dev/null || true
$WP menu create "Principal" 2>/dev/null || true
TIENDA=$($WP post list --post_type=page --name=tienda --field=ID | head -1)
COMO=$($WP post list --post_type=page --name=como-comprar --field=ID | head -1)
CONTACTO=$($WP post list --post_type=page --name=contacto --field=ID | head -1)
$WP menu item add-post Principal "$TIENDA" --title="Tienda" >/dev/null
for slug in nacionales importados; do
  TID=$($WP term list product_cat --slug=$slug --field=term_id | head -1)
  [ -n "$TID" ] && $WP menu item add-term Principal product_cat "$TID" >/dev/null
done
CATP=$($WP menu item add-custom Principal 'Categorías' '#' --porcelain)
$WP term list product_cat --fields=term_id,slug,count | while read -r TID SLUG COUNT; do
  case "$SLUG" in term_id|nacionales|importados|otros|uncategorized|sin-categorizar) continue;; esac
  [ "${COUNT:-0}" -gt 0 ] 2>/dev/null && $WP menu item add-term Principal product_cat "$TID" --parent-id="$CATP" >/dev/null
done
[ -n "$COMO" ] && $WP menu item add-post Principal "$COMO" >/dev/null
[ -n "$CONTACTO" ] && $WP menu item add-post Principal "$CONTACTO" >/dev/null
$WP menu location assign Principal primary
$WP menu location assign Principal primary_mobile

echo "== widgets =="
$WP widget add woocommerce_product_search shop-sidebar --title='Buscar' 2>/dev/null || true
$WP widget add woocommerce_product_categories shop-sidebar --title='Categorías' --count=1 --hierarchical=1 --hide_empty=1 2>/dev/null || true
$WP widget add woocommerce_price_filter shop-sidebar --title='Filtrar por precio' 2>/dev/null || true
$WP widget list shop-sidebar --fields=name,position

$WP rewrite flush >/dev/null 2>&1 || true
echo "== FIN FASE 3 =="
