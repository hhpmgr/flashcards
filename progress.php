<?php
// Tiny progress store for the flashcard pages (Option A).
// One small JSON file per user per deck. No accounts: the "user" is a random
// 128-bit token that lives in the page's #k=... fragment and is sent as a header.
//
// CONFIGURE:
//  1. DATA_DIR: where the files go. Put it OUTSIDE the web root if you can.
//     (If it is inside the web root, a .htaccess that denies access is created.)
//  2. DECKS: the deck ids this endpoint will accept (the deckId in each page).

define('DATA_DIR', dirname(__FILE__) . '/../flashcard-data');
define('DECKS', serialize(array('year8-science', 'year8-history', 'osai-ai-security')));
define('MAX_BODY', 32768);       // bytes
define('MAX_ID', 20000);         // highest card id accepted
define('MAX_USERS_PER_DECK', 20000);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

function fail($code, $msg) {
    http_response_code($code);
    echo json_encode(array('error' => $msg));
    exit;
}

function respond($s, $t) {
    echo json_encode(array('v' => 1, 's' => (object)$s, 't' => (object)$t));
    exit;
}

// Keep only well-formed entries: id 1..MAX_ID, status 0/1/2, or non-negative integer timestamps.
function clean_map($in, $isTs) {
    $out = array();
    if (!is_array($in)) return $out;
    foreach ($in as $k => $v) {
        if (!is_int($k) && !(is_string($k) && ctype_digit($k))) continue;
        $id = (int)$k;
        if ($id < 1 || $id > MAX_ID) continue;
        if ($isTs) {
            if (!(is_int($v) || is_float($v)) || $v < 0) continue;
            $out[$id] = (int)$v;
        } else {
            if ($v !== 0 && $v !== 1 && $v !== 2) continue;
            $out[$id] = $v;
        }
    }
    return $out;
}

function parse_state($raw) {
    $d = json_decode($raw, true);
    if (!is_array($d)) return array(array(), array());
    return array(
        clean_map(isset($d['s']) ? $d['s'] : array(), false),
        clean_map(isset($d['t']) ? $d['t'] : array(), true)
    );
}

$token = isset($_SERVER['HTTP_X_TOKEN']) ? $_SERVER['HTTP_X_TOKEN'] : '';
$deck  = isset($_SERVER['HTTP_X_DECK']) ? $_SERVER['HTTP_X_DECK'] : '';
if (!preg_match('/^[A-Za-z0-9_-]{22}$/', $token)) fail(400, 'bad token');
if (!in_array($deck, unserialize(DECKS), true)) fail(400, 'unknown deck');

$method = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'GET';
if ($method !== 'GET' && $method !== 'POST') fail(405, 'method not allowed');

$dir = DATA_DIR . '/' . $deck;
$file = $dir . '/' . hash('sha256', $deck . '|' . $token) . '.json';

if ($method === 'GET') {
    if (!is_file($file)) respond(array(), array());
    $st = parse_state((string)file_get_contents($file));
    respond($st[0], $st[1]);
}

// POST: merge the client's marks into the stored ones. For each card the newer timestamp wins.
$body = file_get_contents('php://input', false, null, 0, MAX_BODY + 1);
if ($body === false || strlen($body) > MAX_BODY) fail(413, 'body too large');
$in = json_decode($body, true);
if (!is_array($in)) fail(400, 'bad json');
$inS = clean_map(isset($in['s']) ? $in['s'] : array(), false);
$inT = clean_map(isset($in['t']) ? $in['t'] : array(), true);

if (!is_dir($dir)) {
    if (!@mkdir($dir, 0700, true) && !is_dir($dir)) fail(500, 'storage unavailable');
}
$ht = DATA_DIR . '/.htaccess';
if (!is_file($ht)) @file_put_contents($ht, "Require all denied\n");

if (!is_file($file)) {
    $existing = glob($dir . '/*.json');
    if ($existing !== false && count($existing) >= MAX_USERS_PER_DECK) fail(507, 'storage full');
}

$fh = fopen($file, 'c+');
if (!$fh) fail(500, 'storage unavailable');
flock($fh, LOCK_EX);
$st = parse_state((string)stream_get_contents($fh));
$curS = $st[0];
$curT = $st[1];
foreach ($inT as $id => $t) {
    $have = isset($curT[$id]) ? $curT[$id] : 0;
    if ($t > $have) {
        $curT[$id] = $t;
        $curS[$id] = isset($inS[$id]) ? $inS[$id] : 0;
    }
}
ftruncate($fh, 0);
rewind($fh);
fwrite($fh, json_encode(array('s' => (object)$curS, 't' => (object)$curT)));
fflush($fh);
flock($fh, LOCK_UN);
fclose($fh);

respond($curS, $curT);
