<?php
// SSE mock: emite linii de "log" periodic, ca un tail -f
// URL: /api/system/logs.php

// Antete SSE
header("Content-Type: text/event-stream");
header("Cache-Control: no-cache");
header("Connection: keep-alive");

// anti-buffering
@apache_setenv("no-gzip", "1");
@ini_set("zlib.output_compression", 0);
@ini_set("output_buffering", 0);
@ini_set("implicit_flush", 1);
while (ob_get_level() > 0) { @ob_end_flush(); }
ob_implicit_flush(1);

// o mica "umplutură" pentru a forta flush-ul la unele servere
echo ":" . str_repeat(" ", 2048) . "\n\n"; flush();

// utilitar de trimitere evenimente
function sse_send($data, $event = null) {
  if ($event) echo "event: $event\n";
  echo "data: " . json_encode($data) . "\n\n";
  @ob_flush(); flush();
}

// siruri mock de log
$levels = ["INFO", "DEBUG", "WARN", "ERROR"];
$mods   = ["SVXLink", "DXlink", "Audio", "Net", "DTMF", "RO/RW"];
$start  = microtime(true);

ignore_user_abort(true);

// trimitem ~200 mesaje apoi iesim (clientul poate reconecta)
for ($i = 0; $i < 200; $i++) {
  $ts = date("H:i:s");
  $lvl = $levels[array_rand($levels)];
  $mod = $mods[array_rand($mods)];
  $msg = "$ts [$lvl] [$mod] Example message #" . str_pad((string)$i, 3, "0", STR_PAD_LEFT);

  sse_send(["line" => $msg], "log");

  // ritm 6001200ms
  usleep(rand(600, 1200) * 1000);
}

// finalizare
sse_send(["line" => "---- stream ended (demo) ----"], "log");