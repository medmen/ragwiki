<?php

require_once __DIR__ . '/../../embedding_response.php';

it('extracts vector when top-level response is an array of vectors', function () {
    $response = json_encode([[0.11, -0.22, 0.33]]);

    $embedding = extractEmbeddingFromResponse($response);

    expect($embedding)->toBe([0.11, -0.22, 0.33]);
});

it('extracts vector when response has embedding key', function () {
    $response = json_encode(['embedding' => [1.0, 2.0, 3.0]]);

    $embedding = extractEmbeddingFromResponse($response);

    expect($embedding)->toEqual([1.0, 2.0, 3.0]);
});

it('extracts vector from openai-style response', function () {
    $response = json_encode(['data' => [['embedding' => [9.9, 8.8]]]]);

    $embedding = extractEmbeddingFromResponse($response);

    expect($embedding)->toBe([9.9, 8.8]);
});

it('throws on invalid json response', function () {
    extractEmbeddingFromResponse('not-json');
})->throws(RuntimeException::class);
