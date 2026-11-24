<?php
// ACORTADOR TARVO con conteo de clics (22/11, pendiente viejo del spec:
// "acortador propio con tracking" — ahora que existe tarvo.com.py vive
// en el mismo hosting). Uso:
//   https://tarvo.com.py/r.php?c=TRV-XXXXXXXX   → cuenta y redirige a
//   la ficha del producto (lookup por SKU vía WooCommerce local).
//   https://tarvo.com.py/r.php?stats=<verify_token de wa_config>
//   → JSON {TRV-XXX: {"n": clics, "last": ts}} para el digest del EC2.
// Registro: wa_state/clicks.json (misma carpeta privada del bot WA).
$dir = __DIR__ . '/wa_state';
$f = $dir . '/clicks.json';

// --- 24/11 (Milton "no son mis clicks los que suman?"): AUTOEXCLUSIÓN —
// visitar /r.php?yo=1 UNA vez por dispositivo marca ese navegador con
// una cookie de 1 año y sus clics/visitas dejan de contarse (también
// lo respeta el mu-plugin tarvo-stats.php). Excluirse no es sensible:
// cualquiera que lo haga solo se borra a sí mismo de las métricas. ---
if (isset($_GET['yo'])) {
    setcookie('tarvo_yo', '1', time() + 86400 * 365, '/');
    header('Content-Type: text/plain; charset=utf-8');
    exit("Listo — este dispositivo ya NO suma clics ni visitas en las "
        . "métricas de Tarvo (válido 1 año en este navegador).");
}

// --- stats para el digest (protegido con el verify_token del bot) ---
if (isset($_GET['stats'])) {
    $cfg = @include dirname(__DIR__) . '/wa_config.php';
    $tok = is_array($cfg) ? ($cfg['verify_token'] ?? '') : '';
    if (!$tok || !hash_equals($tok, (string) $_GET['stats'])) {
        http_response_code(403);
        exit('no');
    }
    header('Content-Type: application/json');
    if (isset($_GET['full'])) {   // reporte diario (22/11): clics + visitas
        exit(json_encode(array(
            'clicks' => json_decode(@file_get_contents($f) ?: '{}', true),
            'views' => json_decode(
                @file_get_contents($dir . '/views.json') ?: '{}', true),
        )));
    }
    exit(@file_get_contents($f) ?: '{}');
}

$c = strtoupper(preg_replace('/[^A-Za-z0-9-]/', '',
                             (string) ($_GET['c'] ?? '')));
if (!$c || strpos($c, 'TRV-') !== 0) {
    header('Location: https://tarvo.com.py/');
    exit;
}

// contar (lock contra clics simultáneos) — salvo dispositivos marcados
// con la cookie de autoexclusión (Milton y Claude)
if (!is_dir($dir)) {
    @mkdir($dir, 0700);
}
$h = empty($_COOKIE['tarvo_yo']) ? @fopen($f, 'c+') : false;
if ($h && flock($h, LOCK_EX)) {
    $data = json_decode(stream_get_contents($h) ?: '{}', true) ?: array();
    $row = $data[$c] ?? array('n' => 0, 'last' => 0);
    $row['n']++;
    $row['last'] = time();
    $data[$c] = $row;
    // clics POR DIA para el reporte (clave _d; digest la ignora)
    $d = date('Y-m-d');
    $data['_d'][$d][$c] = ($data['_d'][$d][$c] ?? 0) + 1;
    foreach (array_keys($data['_d']) as $k) {
        if ($k < date('Y-m-d', strtotime('-35 days'))) {
            unset($data['_d'][$k]);
        }
    }
    ftruncate($h, 0);
    rewind($h);
    fwrite($h, json_encode($data));
    flock($h, LOCK_UN);
    fclose($h);
}

// resolver la ficha por SKU (WooCommerce local)
require_once __DIR__ . '/wp-load.php';
$pid = function_exists('wc_get_product_id_by_sku')
    ? wc_get_product_id_by_sku($c) : 0;
$dest = $pid ? get_permalink($pid) : '';
header('Location: ' . ($dest ?: 'https://tarvo.com.py/tienda/'));
exit;
