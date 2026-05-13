<?php
require __DIR__ . '/config.php';

$dir = DOKUWIKI_PAGES_DIR;
$pdo = new PDO('sqlite:' . __DIR__ . '/rag.sqlite');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$chunkColumns = $pdo->query('PRAGMA table_info(chunks)')->fetchAll(PDO::FETCH_ASSOC);
$isLegacyChunksSchema = false;
foreach ($chunkColumns as $column) {
    if (($column['name'] ?? '') === 'page_id' && (int)($column['pk'] ?? 0) === 1) {
        $isLegacyChunksSchema = true;
        break;
    }
}

if ($isLegacyChunksSchema) {
    $pdo->exec('ALTER TABLE chunks RENAME TO chunks_legacy');
    $pdo->exec('CREATE TABLE chunks (id INTEGER PRIMARY KEY, page_id INTEGER NOT NULL, heading TEXT, content TEXT, embedding BLOB, FOREIGN KEY (page_id) REFERENCES pages(id))');
    $pdo->exec('DROP TABLE chunks_legacy');
}

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
    $payload = json_encode(['texts' => [$text], 'language' => 'de'], JSON_UNESCAPED_UNICODE);

    $context = stream_context_create([
        'http' => [
            'method'  => 'POST',
            'header'  => "Content-Type: application/json\r\n",
            'content' => $payload,
            'ignore_errors' => true,
        ]
    ]);
    
    $response = file_get_contents(EMBEDDING_SERVER_URL, false, $context);

    return $data[0] ?? []; // float[]
}

$insertPage = $pdo->prepare("INSERT INTO pages (path, title) VALUES (:path, :title)");
$selectPage = $pdo->prepare("SELECT id FROM pages WHERE path = :path LIMIT 1");
$updatePage = $pdo->prepare("UPDATE pages SET title = :title WHERE id = :id");
$deleteChunksByPage = $pdo->prepare("DELETE FROM chunks WHERE page_id = :page_id");
$insertChunk = $pdo->prepare("INSERT INTO chunks (page_id, heading, content, embedding) VALUES (:page_id, :heading, :content, :embedding)");

foreach (glob($dir . '/*.txt') as $file) {
    $raw = file_get_contents($file);
    $title = basename($file, '.txt');
    print "Ingesting $title\n";

    $selectPage->execute([':path' => $file]);
    $pageId = (int)($selectPage->fetchColumn() ?: 0);
    if ($pageId === 0) {
        $insertPage->execute([':path' => $file, ':title' => $title]);
        $pageId = (int)$pdo->lastInsertId();
    } else {
        $updatePage->execute([':title' => $title, ':id' => $pageId]);
    }

    $deleteChunksByPage->execute([':page_id' => $pageId]);

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
