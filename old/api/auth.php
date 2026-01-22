<?php

// Endpoint deprecado. La autenticación de API debe realizarse mediante JWT (ver api/token.php).
header('Content-Type: application/json');
http_response_code(410); // Gone
echo json_encode([
    'success' => false,
    'message' => 'Endpoint deprecado. Use autenticación JWT mediante api/token.php.',
]);
