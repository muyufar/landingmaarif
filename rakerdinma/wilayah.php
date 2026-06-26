<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: public, max-age=86400');

$level = trim($_GET['level'] ?? '');
$code = trim($_GET['code'] ?? '');

$endpoints = [
    'provinces' => 'https://wilayah.id/api/provinces.json',
    'regencies' => 'https://wilayah.id/api/regencies/' . rawurlencode($code) . '.json',
    'districts' => 'https://wilayah.id/api/districts/' . rawurlencode($code) . '.json',
    'villages' => 'https://wilayah.id/api/villages/' . rawurlencode($code) . '.json',
];

if (!isset($endpoints[$level])) {
    http_response_code(400);
    echo json_encode(['error' => 'Level wilayah tidak valid.']);
    exit;
}

if ($level !== 'provinces' && $code === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Parameter code wajib diisi.']);
    exit;
}

$url = $endpoints[$level];

$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'timeout' => 10,
        'header' => "Accept: application/json\r\n",
    ],
    'ssl' => [
        'verify_peer' => true,
        'verify_peer_name' => true,
    ],
]);

$response = @file_get_contents($url, false, $context);

if ($response === false) {
    http_response_code(502);
    echo json_encode(['error' => 'Gagal mengambil data dari Wilayah.id.']);
    exit;
}

$data = json_decode($response, true);

if (!is_array($data) || !isset($data['data'])) {
    http_response_code(502);
    echo json_encode(['error' => 'Format data wilayah tidak valid.']);
    exit;
}

echo json_encode($data);
