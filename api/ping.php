<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

echo json_encode([
    'response' => 'pong',
    'timestamp' => date('Y-m-d H:i:s')
]); 