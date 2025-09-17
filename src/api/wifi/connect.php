<?php
header("Content-Type: application/json");
$stateFile = __DIR__ . "/../../data/wifi_state.json";
$method = $_SERVER["REQUEST_METHOD"] ?? "GET";

function write_state($file, $data){ @file_put_contents($file, json_encode($data)); }

if ($method === "POST") {
  $action = $_POST["action"] ?? "";
  if ($action === "disconnect") {
    write_state($stateFile, ["connected"=>false,"ssid"=>null,"ip"=>null,"rssi"=>null,"secure"=>null]);
    echo json_encode(["ok"=>true,"connected"=>false]); exit;
  }
  if ($action === "connect") {
    $ssid = trim($_POST["ssid"] ?? "");
    $pass = $_POST["pass"] ?? "";
    if ($ssid === "") { http_response_code(400); echo json_encode(["ok"=>false,"error"=>"ssid missing"]); exit; }
    // mock "validare": dacă parola are >=8 caractere sau rețeaua e Open -> conectare reușită
    $secure = "Open";
    if (isset($_POST["secure"]) && $_POST["secure"] !== "") $secure = $_POST["secure"];
    $ok = ($secure === "Open") || (strlen($pass) >= 8);
    if ($ok) {
      // ip și rssi random
      $ip = "192.168.1." . rand(20,250);
      $rssi = -1 * rand(35,85);
      write_state($stateFile, ["connected"=>true,"ssid"=>$ssid,"ip"=>$ip,"rssi"=>$rssi,"secure"=>$secure]);
      echo json_encode(["ok"=>true,"connected"=>true,"ssid"=>$ssid,"ip"=>$ip,"rssi"=>$rssi,"secure"=>$secure]); exit;
    } else {
      echo json_encode(["ok"=>false,"error"=>"authentication failed"]); exit;
    }
  }
  http_response_code(400); echo json_encode(["ok"=>false,"error"=>"unknown action"]); exit;
}

http_response_code(405); echo json_encode(["ok"=>false,"error"=>"method not allowed"]);