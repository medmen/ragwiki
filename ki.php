<?php
function answerWithLLM(string $question, array $chunks): string {
    $context = "";
    foreach ($chunks as $c) {
        $context .= "### Abschnitt: " . ($c['heading'] ?: 'Ohne Überschrift') . "\n";
        $context .= $c['content'] . "\n\n";
    }

    $prompt = "Du bist ein hilfreicher Assistent. Beantworte Fragen nur auf Basis des folgenden internen DokuWiki-Wissens. "
            . "Wenn die Information fehlt, sage ehrlich, dass sie nicht vorhanden ist.\n\n"
            . "WISSEN:\n" . $context . "\n\n"
            . "FRAGE:\n" . $question;

    $resp = file_get_contents('http://localhost:8000/generate', false, stream_context_create([
        'http' => [
            'method'  => 'POST',
            'header'  => "Content-Type: application/json\r\n",
            'content' => json_encode([
                'prompt' => $prompt,
                'max_tokens' => 512,
                'temperature' => 0.1,
                'language' => 'de'
            ])
        ]
    ]));
    $data = json_decode($resp, true);
    return $data['answer'] ?? '';
}
