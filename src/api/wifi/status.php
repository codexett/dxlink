<?php
header("Content-Type: application/json");
$stateFile = __DIR__ . "/../../data/wifi_state.json";
if (!file_exists($stateFile)) {
  echo json_encode(["connected"=>false,"ssid"=>null,"ip"=>null,"rssi"=>null,"secure"=>null]);
  exit;
}
$st = json_decode(@file_get_contents($stateFile), true) ?: [];
$st = array_merge(["connected"=>false,"ssid"=>null,"ip"=>null,"rssi"=>null,"secure"=>null], $st);
echo json_encode($st);