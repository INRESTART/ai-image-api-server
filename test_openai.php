<?php
$openai_key = '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';

$text = $_POST['text'] ?? 'ремонт холодильников';

$payload = [
    "model" => "gpt-4",
    "messages" => [
        ["role" => "system", "content" => "You are a prompt generator for image AI."],
        ["role" => "user", "content" => "Create a detailed prompt for Leonardo AI to generate an image based on this service: \"$text\""]
    ]
];

$ch = curl_init('https://api.openai.com/v1/chat/completions');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        "Authorization: Bearer $openai_key",
        "Content-Type: application/json"
    ],
    CURLOPT_POSTFIELDS => json_encode($payload)
]);

$result = curl_exec($ch);

if (curl_errno($ch)) {
    echo "CURL error: " . curl_error($ch);
    exit;
}

curl_close($ch);

$decoded = json_decode($result, true);

if (!$decoded) {
    echo "JSON decode error. Response was: $result";
    exit;
}

echo "<pre>Response from OpenAI API:\n";
print_r($decoded);
echo "</pre>";

// Если есть нужное поле — выведем
if (!empty($decoded['choices'][0]['message']['content'])) {
    echo "\nПолученный промт:\n";
    echo $decoded['choices'][0]['message']['content'];
} else {
    echo "\nНе удалось получить промт из ответа.";
}
?>
