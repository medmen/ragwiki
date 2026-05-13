<?php
require_once __DIR__.'/../../config.php';

function postJson(string $url, array $payload): array
{
    $ch = curl_init($url);

    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Accept: application/json',
        ],
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_TIMEOUT => 10,
    ]);

    $responseBody = curl_exec($ch);
    $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if ($responseBody === false) {
        $error = curl_error($ch);
        curl_close($ch);
        throw new RuntimeException($error);
    }

    curl_close($ch);

    return [
        'status' => $statusCode,
        'body' => json_decode($responseBody, true),
    ];
}

test('returns an embedding from the docker container', function () {
    $result = postJson(EMBEDDING_SERVER_URL, [
        'texts' => 'Ich möchte Pizza bestellen.',
    ]);

    expect($result['status'])->toBe(200);
    expect($result['body'])->toHaveKey('embedding');
    expect($result['body']['embedding'])->toBeArray();
    expect($result['body']['embedding'])->not->toBeEmpty();
    expect($result['body']['embedding'][0])->toBeNumeric();
});


function cosineSimilarity(array $a, array $b): float
{
    $dot = 0.0;
    $normA = 0.0;
    $normB = 0.0;

    $count = count($a);
    for ($i = 0; $i < $count; $i++) {
        $dot += $a[$i] * $b[$i];
        $normA += $a[$i] * $a[$i];
        $normB += $b[$i] * $b[$i];
    }

    return $dot / (sqrt($normA) * sqrt($normB));
}

test('gives higher similarity for related texts', function () {
    $a = postJson(EMBEDDING_SERVER_URL, [
        'texts' => 'Ich möchte Pizza bestellen.',
    ])['body']['embedding'];

    $b = postJson(EMBEDDING_SERVER_URL, [
        'texts' => 'Heute bestelle ich eine Pizza.',
    ])['body']['embedding'];

    $c = postJson(EMBEDDING_SERVER_URL, [
        'texts' => 'Der Server läuft auf Port 8000.',
    ])['body']['embedding'];

    $simAB = cosineSimilarity($a, $b);
    $simAC = cosineSimilarity($a, $c);

    expect($simAB)->toBeGreaterThan($simAC);
});