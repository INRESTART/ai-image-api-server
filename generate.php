<?php
// 🔐 ВПИШИ СЮДА СВОИ КЛЮЧИ:
$openai_key = 'твой_ключ_OpenAI';
$leonardo_key = 'твой_ключ_LeonardoAI';

// Получаем текст услуги от пользователя
$text = $_POST['text'] ?? '';

if (empty($text)) {
    echo "Ошибка: текст пустой";
    exit;
}

// Шаг 1: Получаем промт для Leonardo AI от GPT
$promptRequest = [
    "model" => "gpt-4",
    "messages" => [
        ["role" => "system", "content" => "You are a prompt generator for image AI."],
        ["role" => "user", "content" => "Create a detailed prompt for Leonardo AI to generate an image based on this service: \"$text\""]
    ]
];

// Отправляем запрос к GPT и получаем ответ
$gptResponse = sendRequestToGPT($openai_key, $promptRequest);
$prompt = $gptResponse['choices'][0]['message']['content'] ?? null;

if (!$prompt) {
    echo "Ошибка: не удалось получить промт от GPT.";
    exit;
}

// Шаг 2: Отправляем промт в Leonardo и получаем ID генерации
$imageGenerationId = sendRequestToLeonardo($leonardo_key, $prompt);

if (!$imageGenerationId) {
    echo "Ошибка: не удалось сгенерировать изображение.";
    exit;
}

// Шаг 3: Получаем URL сгенерированного изображения
$imageUrl = pollLeonardoForImage($leonardo_key, $imageGenerationId);

if ($imageUrl) {
    echo "<h2>Готовое изображение:</h2><img src='$imageUrl' style='max-width: 100%;'>";
} else {
    echo "Изображение ещё не готово. Подождите немного и обновите страницу.";
}


// ---------------- ВСПОМОГАТЕЛЬНЫЕ ФУНКЦИИ ----------------

function sendRequestToGPT($key, $payload) {
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

    if (curl_errno($ch)) {
        echo "CURL ошибка: " . curl_error($ch);
        exit;
    }

    curl_close($ch);

    $decoded = json_decode($result, true);

    if (!$decoded) {
        echo "Ошибка JSON декодирования. Ответ сервера: $result";
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
    sleep(10); // Ждём 10 секунд (можно увеличить, если надо)

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
