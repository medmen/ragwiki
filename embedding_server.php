<?php
// Requires: composer require php-ai/php-ml rubix/tensor
// + Python FastAPI server (dockerized below)

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit(json_encode(['error' => 'POST only']));
}

$input = json_decode(file_get_contents('php://input'), true);
$text = trim($input['input'] ?? '');
$lang = $input['language'] ?? 'de';

if (empty($text)) {
    http_response_code(400);
    exit(json_encode(['error' => 'Missing input']));
}

// Call Python FastAPI server (runs on same machine)
$ch = curl_init('http://localhost:8041/embed');
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode([
        'texts' => [$text],
        'model' => $lang === 'de' ? 'paraphrase-multilingual-MiniLM-L12-v2' : 'all-MiniLM-L6-v2'
    ]),
    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 10
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    http_response_code(500);
    exit(json_encode(['error' => 'Embedding service failed']));
}

$data = json_decode($response, true);
echo json_encode([
    'embedding' => $data[0] ?? [],
    'model' => $data['model_name'] ?? 'unknown'
]);
?>
