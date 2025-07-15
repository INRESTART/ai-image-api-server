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
