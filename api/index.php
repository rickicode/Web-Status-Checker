<?php

if (!isset($_GET['url']) || empty($_GET['url'])) {
    header('Content-Type: application/json');
    http_response_code(400);
    echo json_encode(['error' => 'URL parameter is required']);
    exit;
}

$url = $_GET['url'];
$timeout = 7;
$start = microtime(true);

$context = stream_context_create([
    'http' => [
        'method'  => 'GET',
        'timeout' => $timeout
    ]
]);

try {
    $response = @file_get_contents($url, false, $context);
    $status = $http_response_header ? (int)substr($http_response_header[0], 9, 3) : 502;
} catch (Exception $e) {
    $status = 502; // Bad Gateway
}

$end = microtime(true);
$totalTime = $end - $start;

if ($totalTime < 1) {
    $responseTime = round($totalTime * 1000) . ' ms'; // Dalam milidetik
} else {
    $responseTime = round($totalTime, 2) . ' s'; // Dalam detik
}

// Jika total waktu melebihi 7 detik, anggap server down
if ($totalTime >= $timeout) {
    $status = 504; // Gateway Timeout
}

header('Content-Type: application/json');
echo json_encode([
    'status' => $status,
    'response_time' => $responseTime
]);