<?php
require __DIR__ . '/config.php';
$dir = __DIR__ .'/../dokuwiki/data/pages';
$pdo = new PDO('sqlite:' . __DIR__ . '/rag.sqlite');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

function dokuwikiToText(string $raw): string {
    // Very naive cleanup; refine as needed.
    $text = preg_replace('/======(.*?)======/','\1', $raw);
    $text = preg_replace('/=====?(.*?)=====?/', '\1', $text);
    $text = preg_replace('/\*\*(.*?)\*\*/', '\1', $text);   // bold
    $text = preg_replace('/\/\/(.*?)\/\//', '\1', $text);   // italic
    $text = preg_replace('/\[\[(.*?)(\|(.*?))?\]\]/', '\3', $text); // links
    $text = strip_tags($text);
    return trim($text);
}

function chunkByHeading(string $raw): array {
    $lines = preg_split('/\R/', $raw);
    $chunks = [];
    $currentHeading = '';
    $current = [];

    foreach ($lines as $line) {
        if (preg_match('/^(=+)\s*(.*?)\s*\1$/', $line, $m)) {
            if ($current) {
                $chunks[] = ['heading' => $currentHeading, 'content' => implode("\n", $current)];
                $current = [];
            }
            $currentHeading = $m[2];
        } else {
            $current[] = $line;
        }
    }
    if ($current) {
        $chunks[] = ['heading' => $currentHeading, 'content' => implode("\n", $current)];
    }
    return $chunks;
}

function createEmbedding(string $text): array {
    // Call out to your embedding model over HTTP; return float[].
    // For example: POST to a local service that wraps a CPU‑only model.
    $resp = file_get_contents(EMBEDDING_SERVER_URL, false, stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/json\r\n",
            'content' => json_encode(['input' => $text, 'language' => 'de'])
        ]
    ]));
    $data = json_decode($resp, true);
    return $data['embedding']; // float[]
}

$insertPage = $pdo->prepare("INSERT INTO pages (path, title) VALUES (:path, :title)");
$insertChunk = $pdo->prepare("INSERT INTO chunks (page_id, heading, content, embedding) VALUES (:page_id, :heading, :content, :embedding)");

foreach (glob($dir . '/*.txt') as $file) {
    $raw = file_get_contents($file);
    $title = basename($file, '.txt');
    print "Ingesting $title\n";
    $insertPage->execute([':path' => $file, ':title' => $title]);
    $pageId = (int)$pdo->lastInsertId();

    $sections = chunkByHeading($raw);
    foreach ($sections as $sec) {
        $plain = dokuwikiToText($sec['content']);
        if (mb_strlen($plain) < 50) continue;
        $emb = createEmbedding($plain);
        $insertChunk->execute([
            ':page_id'   => $pageId,
            ':heading'   => $sec['heading'],
            ':content'   => $plain,
            ':embedding' => json_encode($emb)
        ]);
    }
}
