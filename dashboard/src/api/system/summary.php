<?php
header('Content-Type: application/json');
echo json_encode([
  'uptime'   => 'mock: 12m 03s',
  'cpu_temp' => 'mock: 41.2 C',
  'fs'       => 'RW'
]);