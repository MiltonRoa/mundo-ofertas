<?php
// Fase 10 Tarvo — registro de clientes + estados de seguimiento. wp eval-file fase10.php
$raw = 'https://raw.githubusercontent.com/MiltonRoa/mundo-ofertas/main/web';

// 1) registro habilitado en Mi cuenta y en el checkout
update_option('woocommerce_enable_myaccount_registration', 'yes');
update_option('woocommerce_enable_signup_and_login_from_checkout', 'yes');
update_option('woocommerce_registration_generate_password', 'no'); // el cliente elige su contraseña
update_option('woocommerce_enable_checkout_login_reminder', 'yes');
echo "registro habilitado\n";

// 2) mu-plugin de estados (En trámite / En tránsito / Entregado + mails)
$dir = WP_CONTENT_DIR . '/mu-plugins';
if (!is_dir($dir)) mkdir($dir, 0755, true);
$resp = wp_remote_get("$raw/tarvo-estados.php", ['timeout' => 30]);
$code = wp_remote_retrieve_body($resp);
if (strlen($code) < 200 || strpos($code, 'Tarvo') === false) { echo "ERR bajando plugin\n"; exit; }
file_put_contents("$dir/tarvo-estados.php", $code);
echo "mu-plugin estados instalado\n";

// 3) renombrar ACCEDER -> Mi cuenta en el menú si existe como item suelto
$menu = wp_get_nav_menu_object('Principal');
if ($menu) {
    foreach (wp_get_nav_menu_items($menu->term_id) as $it) {
        if (strtolower(trim($it->title)) === 'acceder') {
            wp_update_nav_menu_item($menu->term_id, $it->ID, ['menu-item-title' => 'Mi cuenta']);
            echo "menu: Acceder -> Mi cuenta\n";
        }
    }
}
echo "FIN FASE 10 OK\n";
