<?php
header("Content-Type: application/json");
$cfg = __DIR__ . "/../../data/svxlink.conf";
$method = $_SERVER["REQUEST_METHOD"] ?? "GET";

if ($method === "GET") {
  if (!file_exists($cfg)) {
    // config de start (mock)
    $default = "[GLOBAL]\nCARD=alsa\ndevice=default\n[Logic]\nMODULES=ModuleEchoLink\n";
    file_put_contents($cfg, $default);
  }
  echo json_encode(["ok"=>true,"content"=>file_get_contents($cfg)]);
  exit;
}

if ($method === "POST") {
  $content = $_POST["content"] ?? "";
  if ($content === "") { http_response_code(400); echo json_encode(["ok"=>false,"error"=>"empty"]); exit; }
  file_put_contents($cfg, $content);
  echo json_encode(["ok"=>true]);
  exit;
}

http_response_code(405); echo json_encode(["ok"=>false,"error"=>"method"]);