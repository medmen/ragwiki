<?php
$pdo = new PDO('sqlite:' . __DIR__ . '/rag.db');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

require __DIR__ . '/functions_rag.php'; // contains searchChunks, createEmbedding, answerWithLLM

$query = $_GET['q'] ?? '';
$answer = '';
$matches = [];

if ($query !== '') {
    $matches = searchChunks($pdo, $query, 5);
    $answer = answerWithLLM($query, $matches);
}
?>
<!doctype html>
<html lang="de">
<head>
  <meta charset="utf-8">
  <title>DokuWiki RAG-Suche</title>
  <style>
    body { font-family: sans-serif; margin: 2rem; }
    .snippet { border: 1px solid #ddd; padding: .5rem 1rem; margin-bottom: .5rem; }
    .score { color: #666; font-size: 0.8rem; }
  </style>
</head>
<body>
  <h1>DokuWiki-Assistent</h1>
  <form method="get">
    <label for="q">Frage (Deutsch):</label><br>
    <input type="text" id="q" name="q" value="<?= htmlspecialchars($query) ?>" style="width: 80%;">
    <button type="submit">Suchen</button>
  </form>

  <?php if ($query !== ''): ?>
    <h2>Antwort</h2>
    <p><?= nl2br(htmlspecialchars($answer)) ?></p>

    <h3>Genutzte Textstellen</h3>
    <?php foreach ($matches as $m): ?>
      <div class="snippet">
        <div class="score">Score: <?= round($m['score'], 3) ?></div>
        <strong><?= htmlspecialchars($m['heading'] ?: 'Ohne Überschrift') ?></strong><br>
        <small>(Chunk #<?= (int)$m['id'] ?>)</small>
        <p><?= nl2br(htmlspecialchars(mb_substr($m['content'], 0, 500))) ?>…</p>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</body>
</html>
