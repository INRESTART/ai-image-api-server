<?php
// 🔐 УКАЖИТЕ СВОИ КЛЮЧИ
$openrouter_key = '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'; // например: or-xxxxxxxxxx
$leonardo_key = 'c70f6c43-36ee-4ef3-9d67-4a1524c73c78';     // можно получить на https://app.leonardo.ai

$text = $_POST['text'] ?? '';

if (empty($text)) {
    echo "Ошибка: текст пустой";
    exit;
}

// 🧠 Шаг 1: Получаем промт от OpenRouter (GPT-4)
$promptRequest = [
    "model" => "openai/gpt-4o", // или openai/gpt-3.5-turbo
    "messages" => [
        ["role" => "system", "content" => "You are a prompt generator for image AI."],
        ["role" => "user", "content" => "Create a detailed prompt for Leonardo AI to generate an image based on this service: \"$text\""]
    ]
];

$gptResponse = sendRequestToGPT($openrouter_key, $promptRequest);
$prompt = $gptResponse['choices'][0]['message']['content'] ?? null;

if (!$prompt) {
    echo "Ошибка: не удалось получить промт от GPT.";
    exit;
}

// 🖼️ Шаг 2: Отправляем промт в Leonardo и получаем ID генерации
$imageGenerationId = sendRequestToLeonardo($leonardo_key, $prompt);

if (!$imageGenerationId) {
    echo "Ошибка: не удалось сгенерировать изображение.";
    exit;
}

// ⏳ Шаг 3: Ждём картинку
$imageUrl = pollLeonardoForImage($leonardo_key, $imageGenerationId);

if ($imageUrl) {
    echo $imageUrl;
} else {
    echo "Изображение ещё не готово. Обновите страницу позже.";
}

// ---------------- ВСПОМОГАТЕЛЬНЫЕ ФУНКЦИИ ----------------

function sendRequestToGPT($key, $payload) {
    $ch = curl_init('https://openrouter.ai/api/v1/chat/completions');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            "Authorization: Bearer $key",
            "Content-Type: application/json",
            "HTTP-Referer: https://ВАШ_ДОМЕН", // ОБЯЗАТЕЛЕН, иначе ошибка 401
            "X-Title: Avito Image Generator"
        ],
        CURLOPT_POSTFIELDS => json_encode($payload)
    ]);
    $result = curl_exec($ch);

    if (curl_errno($ch)) {
        echo "CURL ошибка: " . curl_error($ch);
        exit;
    }

    curl_close($ch);
    $decoded = json_decode($result, true);

    if (isset($decoded['error'])) {
        echo "OpenRouter ошибка: " . $decoded['error']['message'];
        exit;
    }

    return $decoded;
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
            "modelId" => "e1a5f06f-3f94-4c25-8b7f-5fa4e6c19d9b", // стандартная модель
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
    sleep(10); // Примитивная задержка

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
