<?php
// FASE 37 (25/11, Milton "al apretar estos botones no se puede
// compartir en ningun lado"): el share de Flatsome en fichas usaba
// whatsapp:// (muerto en PC sin la app) y lo escondía en móvil
// (show-for-medium), + Twitter/Pinterest/LinkedIn que acá son ruido.
// (1) redes del share: WhatsApp + Facebook + Telegram.
// (2) mu-plugin tarvo-share.php: WhatsApp via api.whatsapp.com
//     (abre WhatsApp Web en PC y la app en el celular), visible SIEMPRE.
// (3) menú móvil: los "Follow" con placeholder http://url de fábrica
//     → Instagram real; FB/Twitter/email/snapchat vacíos.
// Uso: php ~/bin/wp --path=$HOME/public_html eval-file ~/fase37.php
set_theme_mod('social_icons', array('whatsapp', 'facebook', 'telegram'));
set_theme_mod('follow_instagram', 'https://www.instagram.com/tarvopy');
set_theme_mod('follow_facebook', '');
set_theme_mod('follow_twitter', '');
set_theme_mod('follow_email', '');
set_theme_mod('follow_snapchat', '');

$mu = WP_CONTENT_DIR . '/mu-plugins';
if (!is_dir($mu)) { mkdir($mu, 0755, true); }
$code = <<<'PHP'
<?php
// tarvo-share.php (fase37, 25/11) — arregla el botón de compartir por
// WhatsApp de Flatsome: link universal en vez de whatsapp:// y sin la
// clase show-for-medium (que lo ocultaba justo donde más se usa).
add_filter('flatsome_share_links', function ($links, $extra) {
    if (isset($links['whatsapp'])) {
        $links['whatsapp']['atts']['href'] =
            'https://api.whatsapp.com/send?text=' . $extra['post_title']
            . '%20-%20' . rawurlencode($extra['link']);
        $links['whatsapp']['atts']['class'] = str_replace(
            ' show-for-medium', '', $links['whatsapp']['atts']['class']);
        $links['whatsapp']['atts']['target'] = '_blank';
        $links['whatsapp']['atts']['rel'] = 'noopener nofollow';
        unset($links['whatsapp']['atts']['data-action']);
    }
    // Instagram no tiene "compartir link" web — el icono lleva al
    // perfil @tarvopy (pedido Milton 25/11 "falto el icono de instagram")
    $cls = isset($links['facebook']['atts']['class'])
        ? str_replace(' facebook', ' instagram',
                      $links['facebook']['atts']['class'])
        : 'icon button circle is-outline tooltip instagram';
    $links['instagram'] = array(
        'enabled' => true,
        'atts' => array(
            'href' => 'https://www.instagram.com/tarvopy',
            'target' => '_blank',
            'rel' => 'noopener nofollow',
            'class' => $cls,
            'title' => 'Seguinos en Instagram',
            'aria-label' => 'Seguinos en Instagram',
        ),
        'icon' => get_flatsome_icon('icon-instagram'),
        'priority' => 25,
    );
    return $links;
}, 10, 2);
PHP;
file_put_contents($mu . '/tarvo-share.php', $code);

echo "FASE37-OK share=" . implode(',', get_theme_mod('social_icons'))
    . " mu=" . substr(md5_file($mu . '/tarvo-share.php'), 0, 10) . "\n";
