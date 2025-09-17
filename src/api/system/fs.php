<?php
// Endpoint mock pentru status & toggle RO/RW
// GET  -> returneaza statusul curent
// POST -> action=toggle -> comuta intre RW si RO

header("Content-Type: application/json");

$stateFile = __DIR__ . "/../../data/fs_state.json";

// Citeste starea curenta (default RW)
function read_state($file) {
  if (!file_exists($file)) return ["fs" => "RW"];
  $raw = @file_get_contents($file);
  $json = @json_decode($raw, true);
  if (!is_array($json) || !isset($json["fs"])) return ["fs" => "RW"];
  return ["fs" => ($json["fs"] === "RO" ? "RO" : "RW")];
}

// Scrie starea
function write_state($file, $state) {
  @file_put_contents($file, json_encode($state));
}

$method = $_SERVER["REQUEST_METHOD"] ?? "GET";

if ($method === "POST") {
  // citeste actiunea (ex. application/x-www-form-urlencoded)
  $action = $_POST["action"] ?? "";
  $state = read_state($stateFile);
  if ($action === "toggle") {
    $state["fs"] = ($state["fs"] === "RW") ? "RO" : "RW";
    write_state($stateFile, $state);
    echo json_encode(["ok" => true, "fs" => $state["fs"]]);
    exit;
  }
  http_response_code(400);
  echo json_encode(["ok" => false, "error" => "unknown action"]);
  exit;
}

// GET
echo json_encode(read_state($stateFile));