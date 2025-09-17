<?php
/**
 * status.php — API pentru tab-ul STATUS (DXlink)
 *
 * Returnează JSON cu:
 *  - System: hostname, uptime, dxlink version, fs ro/rw
 *  - Resources: cpu load %, cpu temp, free space (GB)
 *  - Network: wlan ip, ssid, external ip (opțional)
 *  - Services: SA818 detected, SVXLink running+pid+reflector, APRS active
 *  - Identity: callsign, node type (dacă pot fi citite din config)
 *  - GPIO: RX/TX/FAN (dacă există pini exportați)
 *
 * NOTĂ: Funcționează „best effort”. Dacă anumite comenzi/fisiere nu există,
 *       câmpurile respective vor fi null/unknown. Nu necesită sudo.
 */

header('Content-Type: application/json; charset=utf-8');

/* ===== Config ===== */
$CFG = [
  'dxlink_version_file' => '/opt/dxlink/VERSION',
  'svxlink_service'     => 'svxlink',
  'aprs_service'        => 'direwolf',
  'svxlink_conf'        => '/etc/svxlink/svxlink.conf',   // folosit pt. CALLSIGN/reflector (dacă există)
  'profiles_dir'        => '/opt/dxlink/profiles',        // opțional: JSON profile curente
  'current_profile'     => null,                           // dacă știi profilul curent (ex: dintr-un fișier), pune-l aici
  'external_ip'         => false,                          // pune true dacă vrei să încerce `curl -s ifconfig.me`
  'gpio' => [ 'rx' => null, 'tx' => null, 'fan' => null ], // setează pinii dacă îi știi (ex: 17, 27, 22)
];

/* ===== Helpers ===== */
function read_first_line($path) {
  if (!is_file($path)) return null;
  $h = @fopen($path, 'r');
  if (!$h) return null;
  $line = fgets($h);
  fclose($h);
  return $line !== false ? trim($line) : null;
}
function read_file($path) {
  if (!is_file($path)) return null;
  $c = @file_get_contents($path);
  return $c === false ? null : $c;
}
function cmd($command) {
  // rulează comandă doar dacă există binarul principal (primul cuvânt)
  $parts = preg_split('/\s+/', trim($command));
  if (!$parts || !isset($parts[0])) return null;
  $bin = $parts[0];
  $which = trim(@shell_exec('command -v '.escapeshellarg($bin).' 2>/dev/null'));
  if ($which === '') return null;
  $out = @shell_exec($command.' 2>/dev/null');
  return $out !== null ? trim($out) : null;
}
function seconds_to_dhms($sec) {
  $sec = (int)$sec;
  $d = intdiv($sec, 86400);
  $sec %= 86400;
  $h = intdiv($sec, 3600);
  $sec %= 3600;
  $m = intdiv($sec, 60);
  return sprintf('%dd %02dh %02dm', $d, $h, $m);
}
function fs_rw_state() {
  // Detectează dacă rădăcina / este montată ro/rw
  $m = cmd('mount');
  if (!$m) return null;
  foreach (explode("\n", $m) as $line) {
    if (strpos($line, ' on / ') !== false) {
      // ex: /dev/root on / type ext4 (rw,relatime,...)
      if (preg_match('/\(([^)]*)\)/', $line, $mm)) {
        $opts = $mm[1];
        if (strpos($opts, 'ro') !== false && strpos($opts, 'rw') === false) return 'RO';
        if (strpos($opts, 'rw') !== false) return 'RW';
      }
    }
  }
  return null;
}
function cpu_load_percent() {
  $avg = sys_getloadavg();
  if (!$avg || !isset($avg[0])) return null;
  $load1 = (float)$avg[0];
  $nproc = (int)cmd('nproc');
  if ($nproc <= 0) $nproc = 1;
  $pct = min(100, max(0, ($load1 / $nproc) * 100.0));
  return round($pct, 1);
}
function cpu_temp_c() {
  // Cele mai comune căi pentru temperatură pe Linux embedded
  $paths = [
    '/sys/class/thermal/thermal_zone0/temp',
    '/sys/devices/virtual/thermal/thermal_zone0/temp'
  ];
  foreach ($paths as $p) {
    $v = read_first_line($p);
    if ($v !== null && is_numeric($v)) {
      $c = ((float)$v) / 1000.0;
      return round($c, 1);
    }
  }
  // fallback: vcgencmd (Raspberry Pi)
  $t = cmd('vcgencmd measure_temp');
  if ($t && preg_match('/temp=([0-9.]+)/', $t, $m)) return (float)$m[1];
  return null;
}
function free_space_gb($path = '/') {
  $bytes = @disk_free_space($path);
  if ($bytes === false) return null;
  return round($bytes / 1_000_000_000, 1);
}
function wlan_ip() {
  // încearcă wlan0, apoi hostname -I
  $ip = null;
  $out = cmd('ip -4 addr show wlan0');
  if ($out && preg_match('/inet\s+([0-9.]+)\//', $out, $m)) $ip = $m[1];
  if (!$ip) {
    $h = cmd('hostname -I');
    if ($h) {
      $first = trim(explode(' ', trim($h))[0]);
      if (filter_var($first, FILTER_VALIDATE_IP)) $ip = $first;
    }
  }
  return $ip;
}
function wlan_ssid() {
  $s = cmd('iwgetid -r');
  return $s ?: null;
}
function external_ip($enabled) {
  if (!$enabled) return null;
  $e = cmd('curl -s ifconfig.me');
  if ($e && filter_var($e, FILTER_VALIDATE_IP)) return $e;
  return null;
}
function service_active($name) {
  $a = cmd('systemctl is-active '.escapeshellarg($name));
  return $a === 'active';
}
function pidof_bin($name) {
  $p = cmd('pidof '.escapeshellarg($name));
  return $p ?: null;
}
function parse_svxlink_reflector($confPath) {
  $c = read_file($confPath);
  if (!$c) return [ 'callsign' => null, 'reflector' => null ];
  $callsign = null; $ref = null;
  foreach (explode("\n", $c) as $line) {
    $line = trim($line);
    if ($line === '' || $line[0] === '#') continue;
    if (stripos($line, 'CALLSIGN=') === 0) {
      $callsign = trim(substr($line, 9));
    }
    if (stripos($line, 'HOST=') === 0 || stripos($line, 'REFLECTOR_HOST=') === 0) {
      $ref = trim(substr($line, strpos($line,'=')+1));
    }
  }
  return [ 'callsign' => $callsign, 'reflector' => $ref ];
}
function sa818_detected() {
  // Heuristică simplă: există unul din aceste device-uri seriale?
  $candidates = glob('/dev/ttyUSB*') ?: [];
  $candidates = array_merge($candidates, glob('/dev/ttyAMA*') ?: []);
  $candidates = array_merge($candidates, glob('/dev/ttyS*') ?: []);
  return !empty($candidates);
}
function gpio_state($pin) {
  if ($pin === null) return null;
  $base = "/sys/class/gpio/gpio{$pin}/value";
  $v = read_first_line($base);
  if ($v === null) return null;
  return (trim($v) === '1');
}

/* ===== Build payload ===== */
$hostname = php_uname('n');
$uptime = null;
if (is_file('/proc/uptime')) {
  $u = read_first_line('/proc/uptime');
  if ($u && preg_match('/^([0-9.]+)/', $u, $m)) $uptime = seconds_to_dhms((int)$m[1]);
}
$dxver = read_first_line($CFG['dxlink_version_file']);
$fs = fs_rw_state();
$cpuLoad = cpu_load_percent();
$cpuTemp = cpu_temp_c();
$free = free_space_gb('/');

$wip = wlan_ip();
$ssid = wlan_ssid();
$wan = external_ip($CFG['external_ip']);

$svx_active = service_active($CFG['svxlink_service']);
$svx_pid = $svx_active ? pidof_bin($CFG['svxlink_service']) : null;
$aprs_active = service_active($CFG['aprs_service']);

$svx_meta = parse_svxlink_reflector($CFG['svxlink_conf']);
$callsign = $svx_meta['callsign'];
$reflector = $svx_meta['reflector'];

$node_type = null;
if ($CFG['current_profile']) {
  $pf = $CFG['profiles_dir'].'/'.$CFG['current_profile'].'.json';
  $pj = read_file($pf);
  if ($pj) {
    $jd = json_decode($pj, true);
    if (is_array($jd) && isset($jd['node'])) $node_type = $jd['node'];
    if (!$callsign && isset($jd['callsign'])) $callsign = $jd['callsign'];
  }
}

$gpio_rx = gpio_state($CFG['gpio']['rx']);
$gpio_tx = gpio_state($CFG['gpio']['tx']);
$gpio_fan = gpio_state($CFG['gpio']['fan']);

$payload = [
  'system' => [
    'hostname' => $hostname,
    'uptime'   => $uptime,
    'version'  => $dxver,
    'fs'       => $fs
  ],
  'resources' => [
    'cpuLoadPct' => $cpuLoad,
    'cpuTempC'   => $cpuTemp,
    'freeSpaceGB'=> $free
  ],
  'network' => [
    'wlanIp'  => $wip,
    'ssid'    => $ssid,
    'wanIp'   => $wan
  ],
  'services' => [
    'sa818Detected' => sa818_detected(),
    'svxlink' => [
      'running'  => $svx_active,
      'pid'      => $svx_pid,
      'reflector'=> $reflector
    ],
    'aprsActive' => $aprs_active
  ],
  'identity' => [
    'callsign' => $callsign,
    'nodeType' => $node_type
  ],
  'gpio' => [
    'rx'  => $gpio_rx,
    'tx'  => $gpio_tx,
    'fan' => $gpio_fan
  ],
  'updatedAt' => date('Y-m-d H:i:s')
];

echo json_encode([ 'success' => true, 'data' => $payload ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
