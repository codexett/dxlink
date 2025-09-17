<?php
/**
 * svx.php — API stub pentru tab-ul SVXLink
 * 
 * Acțiuni suportate (parametrul ?action=):
 *  - load   (GET)    : ?action=load&profile=<nume>
 *  - save   (POST)   : body JSON sau form cu 'data' JSON
 *  - start  (GET)
 *  - stop   (GET)
 *  - restart(GET)
 *  - status (GET)    : verifică systemctl/serviciu + PID
 * 
 * NOTĂ:
 *  - Pentru demo, fișierele de profil sunt JSON: <BASE>/<profile>.json
 *  - Integrarea reală poate transforma structura în svxlink.conf (INI) separat.
 */

header('Content-Type: application/json; charset=utf-8');

/* ====== CONFIGURARE ====== */
$BASE_DIR = '/opt/dxlink/profiles';     // schimbă după nevoie (ex: /etc/dxlink/profiles)
$SERVICE  = 'svxlink';                   // numele serviciului systemd
$ALLOW_SERVICE_CMDS = false;             // setează true DOAR când rulezi pe device-ul producție

/* ====== UTILITARE ====== */
function respond($ok, $data = null, $http = 200) {
  http_response_code($http);
  echo json_encode([ 'success' => $ok ? true : false, 'data' => $data ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
  exit;
}
function sanitize_profile($name) {
  // Doar litere, cifre, minus, underscore, punct. Limitează lungimea.
  $name = trim($name ?? '');
  if ($name === '') return '';
  if (strlen($name) > 64) $name = substr($name, 0, 64);
  if (!preg_match('/^[A-Za-z0-9._-]+$/', $name)) return '';
  return $name;
}
function ensure_base($dir) {
  if (!is_dir($dir)) {
    @mkdir($dir, 0755, true);
  }
  if (!is_dir($dir)) {
    respond(false, [ 'error' => 'Cannot create/access base dir', 'dir' => $dir ], 500);
  }
}
function read_body_json() {
  $raw = file_get_contents('php://input');
  if (!$raw) {
    // fallback la form field "data"
    if (isset($_POST['data'])) $raw = $_POST['data'];
  }
  if (!$raw) return null;
  $j = json_decode($raw, true);
  return $j;
}
function has_systemctl() {
  $out = [];
  @exec('command -v systemctl 2>/dev/null', $out, $code);
  return ($code === 0) && !empty($out);
}
function svc($cmd) {
  global $SERVICE, $ALLOW_SERVICE_CMDS;
  $cmd = trim($cmd);
  if (!$ALLOW_SERVICE_CMDS) {
    // Demo: nu executăm comenzi reale
    return [ 'executed' => false, 'message' => 'Service commands are disabled in this environment' ];
  }
  if (!has_systemctl()) {
    return [ 'executed' => false, 'message' => 'systemctl not available' ];
  }
  $out = []; $ret = 0;
  @exec("sudo systemctl $cmd " . escapeshellarg($SERVICE) . " 2>&1", $out, $ret);
  return [ 'executed' => true, 'return_code' => $ret, 'output' => $out ];
}
function svc_status() {
  global $SERVICE;
  $out = []; $ret = 0;
  $status = 'unknown';
  if (has_systemctl()) {
    @exec("systemctl is-active " . escapeshellarg($SERVICE) . " 2>/dev/null", $out, $ret);
    $status = ($ret === 0 && isset($out[0])) ? trim($out[0]) : 'unknown';
  }
  $pid = null;
  $pout = []; $pret = 0;
  @exec("pidof " . escapeshellarg($SERVICE) . " 2>/dev/null", $pout, $pret);
  if ($pret === 0 && isset($pout[0]) && trim($pout[0]) !== '') {
    $pid = trim($pout[0]);
  }
  return [ 'status' => $status, 'pid' => $pid ];
}

/* ====== ROUTARE ====== */
$action = $_GET['action'] ?? $_POST['action'] ?? '';
$action = strtolower(trim($action));

switch ($action) {
  case 'load': {
    $profile = sanitize_profile($_GET['profile'] ?? '');
    if ($profile === '') respond(false, [ 'error' => 'Invalid or missing profile' ], 400);
    ensure_base($BASE_DIR);
    $file = $BASE_DIR . DIRECTORY_SEPARATOR . $profile . '.json';
    if (!file_exists($file)) {
      // fallback: template implicit
      $template = [
        'profile' => $profile,
        'reflector' => 'svx.dxlink.ro',
        'port' => 5300,
        'callsign' => '',
        'auth' => '',
        'beacon' => '',
        'roger' => 'yes',
        'voice' => 'ro_RO-alexandra',
        'node' => 'portabil',
        'shortIdent' => 10,
        'longIdent' => 60,
        'modules' => ['ModuleEchoLink', 'ModuleHelp'],
        'el' => [
          'callsign' => '',
          'pass' => '',
          'sysop' => '',
          'location' => '',
          'timeout' => 180,
          'idle' => 600,
          'proxyHost' => '',
          'proxyPort' => '',
          'proxyUser' => '',
          'proxyPass' => ''
        ],
        'gpio' => [
          'rxPin' => '17',
          'txPin' => '27',
          'squelchDelay' => 120
        ]
      ];
      respond(true, [ 'profile' => $profile, 'config' => $template, 'created' => false ]);
    }
    $json = @file_get_contents($file);
    if ($json === false) respond(false, [ 'error' => 'Cannot read profile file', 'file' => $file ], 500);
    $data = json_decode($json, true);
    if (!is_array($data)) respond(false, [ 'error' => 'Invalid JSON in profile', 'file' => $file ], 500);
    respond(true, [ 'profile' => $profile, 'config' => $data, 'created' => true ]);
  }

  case 'save': {
    $body = read_body_json();
    if (!$body || !is_array($body)) respond(false, [ 'error' => 'Missing JSON body' ], 400);
    // Așteptăm { profile: string, data: object } sau tot obiectul config care include profile
    $profile = sanitize_profile($body['profile'] ?? ($body['data']['profile'] ?? ''));
    if ($profile === '') respond(false, [ 'error' => 'Missing/invalid profile' ], 400);
    $config = $body['data'] ?? $body; // acceptăm fie {profile, data:{...}}, fie direct config
    ensure_base($BASE_DIR);
    $file = $BASE_DIR . DIRECTORY_SEPARATOR . $profile . '.json';
    $ok = @file_put_contents($file, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    if ($ok === false) respond(false, [ 'error' => 'Write failed', 'file' => $file ], 500);
    @chmod($file, 0644);
    respond(true, [ 'saved' => basename($file) ]);
  }

  case 'start': {
    $res = svc('start');
    $st  = svc_status();
    respond(true, [ 'service' => $res, 'status' => $st ]);
  }

  case 'stop': {
    $res = svc('stop');
    $st  = svc_status();
    respond(true, [ 'service' => $res, 'status' => $st ]);
  }

  case 'restart': {
    $res = svc('restart');
    $st  = svc_status();
    respond(true, [ 'service' => $res, 'status' => $st ]);
  }

  case 'status': {
    $st = svc_status();
    respond(true, $st);
  }

  default:
    respond(false, [ 'error' => 'Unknown action', 'hint' => 'use action=load|save|start|stop|restart|status' ], 400);
}
