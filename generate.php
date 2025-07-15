<?php
// Твои ключи
$openai_key = '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';
$leonardo_key = 'c70f6c43-36ee-4ef3-9d67-4a1524c73c78';

$text = $_POST['text'] ?? '';
if (empty($text)) {
    echo "Ошибка: текст пустой";
    exit;
}

$promptRequest = [
    "model" => "gpt-4",
    "messages" => [
        ["role" => "system", "content" => "You are a prompt generator for image AI."],
        ["role" => "user", "content" => "Create a detailed prompt for Leonardo AI to generate an image based on this service: \"$text\""]
    ]
];

$gptResponse = sendRequestToGPT($openai_key, $promptRequest);
$prompt = $gptResponse['choices'][0]['message']['content'] ?? null;
if (!$prompt) {
    echo "Ошибка: не удалось получить промт от GPT.";
    exit;
}

$imageGenerationId = sendRequestToLeonardo($leonardo_key, $prompt);
if (!$imageGenerationId) {
    echo "Ошибка: не удалось получить ID генерации изображения.";
    exit;
}

sleep(10); // ждем готовности

$imageUrl = pollLeonardoForImage($leonardo_key, $imageGenerationId);
if ($imageUrl) {
    // При успехе возвращаем только URL картинки
    echo $imageUrl;
} else {
    echo "Ошибка: изображение не готово.";
}


// --- Функции ---

function sendRequestToGPT($key, $payload) {
    // ... как ранее, без вывода debug
    $ch = curl_init('https://api.openai.com/v1/chat/completions');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            "Authorization: Bearer $key",
            "Content-Type: application/json"
        ],
        CURLOPT_POSTFIELDS => json_encode($payload)
    ]);
    $result = curl_exec($ch);
    curl_close($ch);
    return json_decode($result, true);
}

function sendRequestToLeonardo($key, $prompt) {
    $ch = curl_init('https://cloud.leonardo.ai/api/rest/v1/generations');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            "Authorization: Bearer $key",
            "Content-Type: application/json"
        ],
        CURLOPT_POSTFIELDS => json_encode([
            "prompt" => $prompt,
            "modelId" => "e1a5f06f-3f94-4c25-8b7f-5fa4e6c19d9b",
            "width" => 512,
            "height" => 512,
            "num_images" => 1
        ])
    ]);
    $result = curl_exec($ch);
    curl_close($ch);
    $data = json_decode($result, true);
    return $data['sdGenerationJob']['generationId'] ?? null;
}

function pollLeonardoForImage($key, $generationId) {
    $url = "https://cloud.leonardo.ai/api/rest/v1/generations/$generationId";
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            "Authorization: Bearer $key"
        ]
    ]);
    $result = curl_exec($ch);
    curl_close($ch);
    $data = json_decode($result, true);
    return $data['generations_by_pk']['generated_images'][0]['url'] ?? null;
}
