<?php

if (!isset($_GET['url']) || empty($_GET['url'])) {
    header('Content-Type: application/json');
    http_response_code(400);
    echo json_encode(['error' => 'URL parameter is required']);
    exit;
}

function getFinalUrl($url, $timeout) {
    $context = stream_context_create([
        'http' => [
            'method'  => 'GET',
            'timeout' => $timeout,
            'follow_location' => 1
        ]
    ]);

    $response = @file_get_contents($url, false, $context);
    $status = $http_response_header ? (int)substr($http_response_header[0], 9, 3) : 502;

    if ($status >= 300 && $status < 400) {
        foreach ($http_response_header as $header) {
            if (stripos($header, 'Location:') === 0) {
                $newUrl = trim(substr($header, 9));
                return getFinalUrl($newUrl, $timeout);
            }
        }
    }

    return [$response, $status, $url];
}

$url = $_GET['url'];
$timeout = 7;
$start = microtime(true);

list($response, $status, $finalUrl) = getFinalUrl($url, $timeout);

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
    'url' => $url,
    'final_url' => $finalUrl,
    'response_time' => $responseTime
]);