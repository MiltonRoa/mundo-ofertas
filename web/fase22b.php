<?php
/**
 * Fase 22b (30/08): hero responsive — slider de banners PANORÁMICOS solo en
 * desktop/tablet + slider de banners VERTICALES (1000x800, centrados) solo
 * en el celular. Correr: php ~/bin/wp eval-file ~/fase22b.php
 */
$fid = (int) get_option('page_on_front');
$p = get_post($fid);
$c = $p->post_content;

$i = strpos($c, '[section label="tarvo-hero-banners"');
$j = ($i !== false) ? strpos($c, '[/section]', $i) : false;
if ($i === false || $j === false) { echo "no encontré el hero — abortando\n"; return; }

$B = 'https://miltonroa.github.io/mundo-ofertas/web';
$sl = '[ux_slider style="container" slide_width="100%" slide_align="center" '
    . 'nav_style="simple" nav_color="light" bullet_style="simple" '
    . 'timer="5000" auto_slide="true" pause_hover="true"]';
$slides_desk =
      '[ux_banner height="420px" bg="' . $B . '/web_heroA.jpg" bg_size="orginal" link="/tienda/"][/ux_banner]'
    . '[ux_banner height="420px" bg="' . $B . '/web_heroB.jpg" bg_size="orginal" link="/categoria-producto/tecnologia/"][/ux_banner]'
    . '[ux_banner height="420px" bg="' . $B . '/web_heroC.jpg" bg_size="orginal" link="/categoria-producto/hogar-y-cocina/"][/ux_banner]';
$slides_mov =
      '[ux_banner height="480px" bg="' . $B . '/web_heroA_m.jpg" link="/tienda/"][/ux_banner]'
    . '[ux_banner height="480px" bg="' . $B . '/web_heroB_m.jpg" link="/categoria-producto/tecnologia/"][/ux_banner]'
    . '[ux_banner height="480px" bg="' . $B . '/web_heroC_m.jpg" link="/categoria-producto/hogar-y-cocina/"][/ux_banner]';

$hero = '[section label="tarvo-hero-banners" padding="0px"]'
    . '[row visibility="hide-for-small"][col span="12" padding="0px"]'
    . $sl . $slides_desk . '[/ux_slider][/col][/row]'
    . '[row visibility="show-for-small"][col span="12" padding="0px"]'
    . $sl . $slides_mov . '[/ux_slider][/col][/row]'
    . '[/section]';

$c = substr($c, 0, $i) . $hero . substr($c, $j + 10);
wp_update_post(['ID' => $fid, 'post_content' => $c]);
wp_cache_flush();
echo "hero responsive instalado (desktop panoramico / movil vertical)\n";
echo "== FASE 22b LISTA ==\n";
