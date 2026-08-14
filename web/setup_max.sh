#!/bin/bash
# Instalación WordPress+WooCommerce Tarvo en MaxDominios (cPanel Terminal)
# Uso: bash setup_max.sh   — idempotente, credenciales generadas en ~/tarvo_creds.txt
set -uo pipefail
H=$HOME; PH=$H/public_html
DB=tarvocompy_wp; DBU=tarvocompy_wp

echo "== 1. wp-cli =="
mkdir -p $H/bin
if [ ! -x $H/bin/wp ]; then
  curl -sSLo $H/bin/wp https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar
  chmod +x $H/bin/wp
fi
export PATH=$H/bin:$PATH
wp --version

echo "== 2. base de datos (uapi) =="
PW=$(openssl rand -hex 16)
uapi Mysql create_database name=$DB >/dev/null 2>&1 || true
uapi Mysql create_user name=$DBU password=$PW >/dev/null 2>&1 && echo "db-pass: $PW" >> $H/tarvo_creds.txt
uapi Mysql set_privileges_on_database user=$DBU database=$DB privileges=ALL%20PRIVILEGES >/dev/null 2>&1 || true

echo "== 3. WordPress =="
rm -f $PH/index.html
if [ ! -f $PH/wp-load.php ]; then
  wp core download --locale=es_ES --path=$PH
fi
if [ ! -f $PH/wp-config.php ]; then
  DBPW=$(grep '^db-pass:' $H/tarvo_creds.txt | tail -1 | cut -d' ' -f2)
  wp config create --path=$PH --dbname=$DB --dbuser=$DBU --dbpass="$DBPW" \
    --extra-php <<'PHPX'
define( 'FS_METHOD', 'direct' );
define( 'WP_MEMORY_LIMIT', '256M' );
PHPX
fi
if ! wp core is-installed --path=$PH 2>/dev/null; then
  APW=$(openssl rand -base64 12 | tr -d '/+=' | head -c 14)
  wp core install --path=$PH --url=http://tarvo.com.py --title=Tarvo \
    --admin_user=milton --admin_password="$APW" \
    --admin_email=milton_roa@live.com --skip-email
  echo "wp-admin: milton / $APW" >> $H/tarvo_creds.txt
fi
WP="wp --path=$PH"

echo "== 4. ajustes + WooCommerce =="
$WP option update blogdescription "Compra gestionada por Tarvo" >/dev/null
$WP option update timezone_string "America/Asuncion" >/dev/null
$WP option update permalink_structure '/%postname%/' >/dev/null
$WP plugin install woocommerce --activate 2>&1 | tail -1
$WP language plugin install woocommerce es_ES >/dev/null 2>&1 || true
$WP option update woocommerce_currency PYG >/dev/null
$WP option update woocommerce_default_country PY >/dev/null
$WP option update woocommerce_currency_pos left_space >/dev/null
$WP option update woocommerce_price_thousand_sep '.' >/dev/null
$WP option update woocommerce_price_num_decimals 0 >/dev/null
$WP option update woocommerce_calc_taxes no >/dev/null
$WP option update woocommerce_manage_stock no >/dev/null
$WP option update woocommerce_enable_coupons no >/dev/null
$WP option update woocommerce_enable_guest_checkout yes >/dev/null
$WP option update woocommerce_coming_soon no >/dev/null
$WP option update woocommerce_store_city "Asuncion" >/dev/null
$WP option update woocommerce_cod_settings --format=json '{"enabled":"yes","title":"Pago a coordinar por WhatsApp","description":"Confirmamos tu pedido por WhatsApp y te pasamos el medio de pago en guaraníes.","instructions":"Te contactamos por WhatsApp para confirmar el pedido y coordinar el pago.","enable_for_methods":[],"enable_for_virtual":"yes"}' >/dev/null
$WP option update woocommerce_permalinks --format=json '{"product_base":"/producto","category_base":"categoria-producto","tag_base":"etiqueta-producto","attribute_base":"","use_verbose_page_rules":false}' >/dev/null
$WP eval 'WC_Install::create_pages();' >/dev/null 2>&1
# renombrar slugs de las páginas Woo al español
for fila in "shop:tienda:Tienda" "cart:carrito:Carrito" "checkout:finalizar-compra:Finalizar compra" "my-account:mi-cuenta:Mi cuenta"; do
  OLD=${fila%%:*}; resto=${fila#*:}; NEW=${resto%%:*}; TIT=${resto#*:}
  PID=$($WP post list --post_type=page --name=$OLD --field=ID | head -1)
  [ -n "$PID" ] && $WP post update $PID --post_name=$NEW --post_title="$TIT" >/dev/null
done
$WP rewrite flush >/dev/null 2>&1 || true
$WP post delete 1 2 --force >/dev/null 2>&1 || true

echo "== 5. mu-plugin WhatsApp =="
mkdir -p $PH/wp-content/mu-plugins
curl -sSLo $PH/wp-content/mu-plugins/tarvo-whatsapp.php \
  https://raw.githubusercontent.com/MiltonRoa/mundo-ofertas/main/web/tarvo-whatsapp.php

echo "== 6. claves REST para woo_sync =="
if ! grep -q '^ck_' $H/tarvo_creds.txt 2>/dev/null; then
  $WP eval 'global $wpdb; $ck="ck_".wc_rand_hash(); $cs="cs_".wc_rand_hash();
  $wpdb->insert($wpdb->prefix."woocommerce_api_keys",["user_id"=>1,"description"=>"woo_sync","permissions"=>"read_write","consumer_key"=>wc_api_hash($ck),"consumer_secret"=>$cs,"truncated_key"=>substr($ck,-7)]);
  echo $ck."|".$cs."\n";' >> $H/tarvo_creds.txt
fi
chmod 600 $H/tarvo_creds.txt

echo "== LISTO =="
$WP core version; $WP plugin list --fields=name,status | grep -i woo
echo "-- credenciales:"; cat $H/tarvo_creds.txt
