<?php
function cosine(array $a, array $b): float {
    $dot = 0.0; $na = 0.0; $nb = 0.0;
    $n = min(count($a), count($b));
    for ($i = 0; $i < $n; $i++) {
        $dot += $a[$i] * $b[$i];
        $na  += $a[$i] * $a[$i];
        $nb  += $b[$i] * $b[$i];
    }
    if ($na == 0 || $nb == 0) return 0.0;
    return $dot / (sqrt($na) * sqrt($nb));
}

function searchChunks(PDO $pdo, string $query, int $k = 5): array {
    $qEmb = createEmbedding($query); // same model as ingestion

    $stmt = $pdo->query("SELECT id, page_id, heading, content, embedding FROM chunks");
    $results = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $emb = json_decode($row['embedding'], true);
        $score = cosine($qEmb, $emb);
        $row['score'] = $score;
        $results[] = $row;
    }
    usort($results, fn($a, $b) => $b['score'] <=> $a['score']);
    return array_slice($results, 0, $k);
}
