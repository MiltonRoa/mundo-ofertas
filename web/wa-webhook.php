<?php
/**
 * Tarvo — Bot de WhatsApp (Cloud API + Claude Haiku). v7
 * Config con secretos en ~/wa_config.php (FUERA de public_html).
 * Piloto: número de prueba de Meta; producción: mismo código, otro phone_id.
 * v7: reconoce productos compartidos (wa.me/p → og:title público) + tono formal.
 * v8: al confirmar pedido habla como el equipo (instrucciones de pago en breve),
 *     nunca "hable con una persona"; <<HUMANO>> queda como aviso interno.
 */
$cfg = @include dirname(__DIR__) . '/wa_config.php';
if (!$cfg) { http_response_code(500); exit('sin config'); }

function wlog($cfg, $m) {
    @file_put_contents($cfg['state_dir'] . '/log.txt',
        date('H:i:s ') . $m . "\n", FILE_APPEND);
}

// lector de diagnóstico protegido: ?debug=<verify_token>
if (isset($_GET['debug'])) {
    if ($_GET['debug'] !== $cfg['verify_token']) { http_response_code(403); exit('no'); }
    header('Content-Type: text/plain; charset=utf-8');
    echo "php=" . PHP_VERSION . " curl=" . (extension_loaded('curl') ? 'si' : 'NO')
        . " mbstring=" . (extension_loaded('mbstring') ? 'si' : 'NO')
        . " ssl=" . (extension_loaded('openssl') ? 'si' : 'NO') . "\n---\n";
    $log = @file_get_contents($cfg['state_dir'] . '/log.txt') ?: '(sin log)';
    echo implode("\n", array_slice(explode("\n", $log), -60));
    exit;
}

// ---------- verificación del webhook (Meta manda GET al suscribir) ----------
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (($_GET['hub_mode'] ?? '') === 'subscribe'
        && ($_GET['hub_verify_token'] ?? '') === $cfg['verify_token']) {
        echo $_GET['hub_challenge'] ?? '';
        exit;
    }
    http_response_code(403); exit('no');
}

// ---------- eventos POST ----------
$data = json_decode(file_get_contents('php://input'), true);
$value = $data['entry'][0]['changes'][0]['value'] ?? [];
$msg = $value['messages'][0] ?? null;
http_response_code(200); // contestar rápido; Meta reintenta si no
if (!$msg) { echo 'ok'; exit; } // statuses (entregado/leído) se ignoran

$from = preg_replace('/\D/', '', $msg['from'] ?? '');
$mid  = $msg['id'] ?? '';
$text = trim($msg['text']['body']
    ?? ($msg['interactive']['button_reply']['title'] ?? ($msg['button']['text'] ?? '')));
if (!$from || !$mid) { echo 'ok'; exit; }

@mkdir($cfg['state_dir'], 0700, true);

// dedupe: Meta reenvía si tardamos; no responder dos veces al mismo msg
$ddf = $cfg['state_dir'] . '/dedupe.json';
$dd = json_decode(@file_get_contents($ddf), true) ?: [];
if (in_array($mid, $dd)) { echo 'ok'; exit; }
$dd = array_slice(array_merge($dd, [$mid]), -200);
file_put_contents($ddf, json_encode($dd));

if ($text === '') {
    wa_send($cfg, $from, 'Por ahora solo puedo leer mensajes de texto 🙏 Cuénteme qué producto busca.');
    echo 'ok'; exit;
}

// ---------- catálogo (cache 15 min) ----------
function http_get($url, $ua = null) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 25, CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_USERAGENT => $ua ?: 'TarvoBot/1.0']);
    $r = curl_exec($ch);
    curl_close($ch);
    return $r;
}
function catalogo($cfg) {
    $cache = $cfg['state_dir'] . '/catalog.csv';
    if (!file_exists($cache) || time() - filemtime($cache) > 900) {
        $csv = http_get('https://miltonroa.github.io/mundo-ofertas/catalog/catalog.csv');
        if ($csv && strlen($csv) > 500) file_put_contents($cache, $csv);
    }
    $rows = [];
    if (($h = @fopen($cache, 'r')) !== false) {
        $head = fgetcsv($h);
        while (($r = fgetcsv($h)) !== false) {
            if (count($r) < 6) continue;
            $rows[] = ['id' => $r[0], 'title' => $r[1], 'price' => $r[5]];
        }
        fclose($h);
    }
    return $rows;
}
function sin_acentos($s) {
    return strtr(mb_strtolower($s, 'UTF-8'),
        ['á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ñ'=>'n','ü'=>'u']);
}
const STOPWORDS = ['que','tenes','tienes','tiene','hay','para','con','algo',
    'quiero','busco','dame','todas','todos','opciones','opcion','ese','esa',
    'este','esta','esto','como','cual','cuales','precio','precios','cuanto',
    'cuesta','sale','del','las','los','una','uno','unos','unas','ver','mas',
    'por','favor','hola','buenas','buenos','dias','tardes','noches','quisiera',
    'necesito','vende','venden','sobre','otro','otra','otros','otras','algun',
    'alguna','sigue','enlace','articulo','whatsapp','https','comparto','producto'];
function buscar($rows, $q) {
    $palabras = array_filter(preg_split('/\W+/u', sin_acentos($q)),
        fn($w) => mb_strlen($w) >= 4 && !in_array($w, STOPWORDS));
    $scored = [];
    foreach ($rows as $r) {
        $t = sin_acentos($r['title']);
        $score = 0;
        foreach ($palabras as $w) {
            // plural crudo es: "bicicletas"→"bicicleta", "aires"→"aire"
            $stem = (mb_strlen($w) > 4 && str_ends_with($w, 's'))
                ? rtrim(mb_substr($w, 0, -1), 'e') : $w;
            if (str_contains($t, $w) || str_contains($t, $stem)
                || ($stem !== $w && str_contains($t, mb_substr($w, 0, -1)))) {
                $score += mb_strlen($stem); // palabras largas pesan más
            }
        }
        if ($score > 0) $scored[] = [$score, $r];
    }
    usort($scored, fn($a, $b) => $b[0] <=> $a[0]);
    return array_slice(array_column($scored, 1), 0, 8);
}
function fmt_gs($price) {
    return number_format((float) preg_replace('/[^\d.]/', '', $price), 0, ',', '.');
}

// ---------- producto compartido (wa.me/p → página pública con og:title) ----------
// La "tarjeta" linda del chat es una vista previa del cliente: al webhook solo
// llega el texto "Sigue este enlace... https://wa.me/p/<id>/<tel>". Esa página
// es pública y trae og:title + og:description (con el precio) del producto.
function producto_compartido($cfg, $url) {
    if (!preg_match('~wa\.me/p/(\d+)~', $url, $m)) return null;
    $pid = $m[1];
    $pcf = $cfg['state_dir'] . '/pcache.json';
    $pc = json_decode(@file_get_contents($pcf), true) ?: [];
    if (isset($pc[$pid])) return $pc[$pid];
    $html = http_get($url, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
    if (!$html) return null;
    $og = function ($prop) use ($html) {
        return preg_match('~<meta property="og:' . $prop . '" content="([^"]*)"~', $html, $mm)
            ? trim(html_entity_decode($mm[1], ENT_QUOTES | ENT_HTML5, 'UTF-8')) : '';
    };
    $title = preg_replace('~\s*(from|de)\s+Tarvo\s+(on|en)\s+WhatsApp\.?$~iu', '', $og('title'));
    $title = trim(preg_replace('/\s+/u', ' ', $title));
    if ($title === '') return null;
    $p = ['title' => $title, 'desc' => mb_substr($og('description'), 0, 300)];
    $pc = array_slice([$pid => $p] + $pc, 0, 100, true);
    file_put_contents($pcf, json_encode($pc, JSON_UNESCAPED_UNICODE));
    return $p;
}

$compartido = null;
if (preg_match('~(https://wa\.me/p/\d+(?:/\d+)?)~i', $text, $mm)
    || preg_match('~wa\.me/p/|enlace para ver el art~iu', $text)) {
    $compartido = isset($mm[1]) ? producto_compartido($cfg, $mm[1]) : null;
    if (!$compartido) { wlog($cfg, "link compartido de $from sin resolver — ignorado"); echo 'ok'; exit; }
    $text = 'Le comparto este producto: ' . $compartido['title'];
}

// ---------- historial ----------
$hf = $cfg['state_dir'] . "/h_$from.json";
$hist = json_decode(@file_get_contents($hf), true) ?: [];
$hist[] = ['role' => 'user', 'content' => mb_substr($text, 0, 600)];
$hist = array_slice($hist, -12);

// contexto de productos
if ($compartido) {
    // buscar la ficha exacta en el catálogo (mismo título, viene del mismo feed)
    $tnorm = sin_acentos(preg_replace('/\s+/u', ' ', $compartido['title']));
    $fila = null;
    foreach (catalogo($cfg) as $r) {
        $rn = sin_acentos(preg_replace('/\s+/u', ' ', $r['title']));
        if ($rn === $tnorm || str_contains($rn, $tnorm) || str_contains($tnorm, $rn)) { $fila = $r; break; }
    }
    if ($fila) {
        $ctx = "EL CLIENTE ACABA DE COMPARTIR LA FICHA DE ESTE PRODUCTO (responda directamente sobre él):\n"
            . "- {$fila['title']} | Gs " . fmt_gs($fila['price']) . " | código {$fila['id']}\n";
    } else {
        $ctx = "EL CLIENTE ACABA DE COMPARTIR LA FICHA DE ESTE PRODUCTO (responda directamente sobre él):\n"
            . "- {$compartido['title']}\n  Detalle: {$compartido['desc']}\n";
    }
    wlog($cfg, "msg de $from: [producto compartido] " . mb_substr($compartido['title'], 0, 50) . ($fila ? " | en catalogo" : " | solo og"));
} else {
    // buscar con el mensaje actual (+ el anterior del cliente)
    $prev = '';
    for ($i = count($hist) - 2; $i >= 0; $i--) {
        if ($hist[$i]['role'] === 'user') { $prev = $hist[$i]['content']; break; }
    }
    $matches = buscar(catalogo($cfg), $text . ' ' . $prev);
    $ctx = "";
    foreach ($matches as $m) {
        $ctx .= "- {$m['title']} | Gs " . fmt_gs($m['price']) . " | código {$m['id']}\n";
    }
    if ($ctx === '') $ctx = "(sin resultados para esta búsqueda)\n";
    wlog($cfg, "msg de $from: " . mb_substr($text, 0, 60) . " | catalogo=" . count(catalogo($cfg)) . " matches=" . count($matches));
}

$system = <<<TXT
Es usted el asistente de ventas de Tarvo (tarvo.com.py), tienda paraguaya de compra gestionada. Responde por WhatsApp.
REGLAS:
- Español FORMAL y cordial, trato de "usted" SIEMPRE. PROHIBIDO el voseo y las muletillas informales ("che", "dale", "capo", "genial"). Tono profesional y cálido, mensajes CORTOS estilo WhatsApp (máximo ~100 palabras, algún emoji con moderación).
- Precios SIEMPRE en guaraníes: "Gs 1.234.567". Son precios finales.
- Productos nacionales: delivery incluido, entrega rápida en Paraguay. Productos importados de USA: precio final todo incluido, llegan en 2 a 3 semanas.
- PROHIBIDO mencionar Amazon o la tienda de origen de un producto. Diga "importado de USA".
- Solo ofrezca productos del CONTEXTO de abajo. Si no hay nada que encaje, dígalo con honestidad y ofrezca buscar con otras palabras o ver todo en https://tarvo.com.py
- Si hay VARIAS opciones relevantes en el CONTEXTO, muéstrelas en lista (hasta 6, con nombre corto y precio) — no elija una sola salvo que el cliente pida algo específico.
- Si el CONTEXTO indica que el cliente COMPARTIÓ un producto, responda directamente sobre ESE producto: confirme precio y condiciones de entrega y pregunte si desea encargarlo. JAMÁS diga que no puede abrir, ver o leer un enlace, imagen o archivo — si le falta información, pregunte con naturalidad cuál producto le interesa.
- NUNCA invente stock, precios, descuentos ni plazos. No prometa nada fuera de estas reglas.
- Si el cliente quiere COMPRAR o confirmar un pedido: confirme como parte del equipo, en el estilo de: "¡Perfecto! Su pedido de <producto> quedó registrado. En breve le enviaremos las instrucciones de pago y le guiaremos en todo el proceso 🙌", y agregue EXACTAMENTE la marca <<HUMANO>> al final. JAMÁS le diga al cliente que "hable con una persona", que será "derivado" ni nada parecido — para el cliente, el mismo equipo de Tarvo sigue el chat.
- Si el cliente pregunta algo que usted no sabe o pide expresamente hablar con alguien: responda breve y natural ("Enseguida le confirmamos ese detalle 👍") y agregue la marca <<HUMANO>> al final. La marca es interna: el sistema avisa a Milton, el dueño, que continúa la conversación en este mismo chat.
- Puede compartir el link https://tarvo.com.py para ver fotos y todo el catálogo.
CONTEXTO (productos disponibles que coinciden con la consulta):
{$ctx}
TXT;

// ---------- Claude Haiku ----------
$payload = json_encode([
    'model' => 'claude-haiku-4-5',
    'max_tokens' => 300,
    'system' => $system,
    'messages' => $hist,
], JSON_UNESCAPED_UNICODE);
$ch = curl_init('https://api.anthropic.com/v1/messages');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 45,
    CURLOPT_POST => true, CURLOPT_POSTFIELDS => $payload,
    CURLOPT_HTTPHEADER => [
        'x-api-key: ' . $cfg['anthropic_key'],
        'anthropic-version: 2023-06-01',
        'content-type: application/json',
    ],
]);
$rawresp = curl_exec($ch);
$hcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$herr = curl_error($ch);
curl_close($ch);
wlog($cfg, "haiku http=$hcode err=" . ($herr ?: '-') . " body=" . mb_substr((string) $rawresp, 0, 120));
$resp = json_decode($rawresp, true);
$reply = trim($resp['content'][0]['text'] ?? '');
if ($reply === '') {
    $reply = 'Disculpe, tuvimos un inconveniente técnico 🙏 Intente de nuevo en unos minutos o escríbanos al 0992 805 800.';
}

// ---------- derivación a humano (aviso por WhatsApp a Milton) ----------
if (str_contains($reply, '<<HUMANO>>')) {
    $reply = trim(str_replace('<<HUMANO>>', '', $reply));
    $aviso = "🤖➡️👤 Bot Tarvo: cliente pide atención\nDe: +$from\nÚltimo mensaje: \"$text\"\nSeguí la conversación con ese número.";
    if (!empty($cfg['owner_phone']) && $cfg['owner_phone'] !== $from) {
        wa_send($cfg, $cfg['owner_phone'], $aviso);
    }
}

$hist[] = ['role' => 'assistant', 'content' => $reply];
file_put_contents($hf, json_encode(array_slice($hist, -12), JSON_UNESCAPED_UNICODE));

wa_send($cfg, $from, $reply);
echo 'ok';

// ---------- envío WhatsApp ----------
function wa_send($cfg, $to, $body) {
    $ch = curl_init("https://graph.facebook.com/v21.0/{$cfg['phone_id']}/messages");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 30,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode([
            'messaging_product' => 'whatsapp', 'to' => $to,
            'type' => 'text', 'text' => ['body' => mb_substr($body, 0, 3900)],
        ], JSON_UNESCAPED_UNICODE),
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $cfg['wa_token'],
            'Content-Type: application/json',
        ],
    ]);
    $r = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);
    wlog($cfg, "wa_send a $to http=$code err=" . ($err ?: '-')
        . " body=" . mb_substr((string) $r, 0, 150));
}
