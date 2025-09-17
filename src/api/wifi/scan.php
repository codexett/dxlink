<?php
header("Content-Type: application/json");
// listă mock; în prod vei rula `iw dev wlan0 scan` și parsezi
$nets = [
  ["ssid"=>"DXlink-Home","rssi"=>-42,"secure"=>"WPA2"],
  ["ssid"=>"Repeater-AP","rssi"=>-60,"secure"=>"Open"],
  ["ssid"=>"YO3THC-Lab","rssi"=>-68,"secure"=>"WPA3"],
  ["ssid"=>"CoffeeBar","rssi"=>-79,"secure"=>"WPA2"],
];
echo json_encode(["networks"=>$nets, "iface"=>"wlan0"]);