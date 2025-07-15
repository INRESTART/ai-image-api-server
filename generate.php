<?php
// Вставь свои ключи здесь
$openai_key = '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';
$leonardo_key = 'c70f6c43-36ee-4ef3-9d67-4a1524c73c78';

// Получаем текст услуги от пользователя (через POST)
$text = $_POST['text'] ?? '';

if (empty($text)) {
    echo "Ошибка: текст пустой";
    exit;
}

// --- Шаг 1: Получаем промт от GPT ---
$promptRequest = [
    "model" => "gpt-4",
    "messages" => [
        ["role" => "system", "content" => "You are a prompt generator for image AI."],
        ["role" => "user", "content" => "Create a detailed prompt for Leonardo AI to generate an image based on this service: \"$text\""]
    ]
];

echo "<h3>Отправляем запрос к GPT...</h3>";
$gptResponse = sendRequestToGPT($openai_key, $promptRequest);

if (!$gptResponse) {
    echo "Ошибка: нет ответа от GPT.";
    exit;
}

echo "<pre>Ответ GPT:\n";
print_r($gptResponse);
echo "</pre>";

$prompt = $gptResponse['choices'][0]['message']['content'] ?? null;
if (!$prompt) {
    echo "Ошибка: не удалось получить промт из ответа GPT.";
    exit;
}

echo "<h3>Промт для Leonardo:</h3><p>$prompt</p>";

// --- Шаг 2: Отправляем промт в Leonardo ---
echo "<h3>Отправляем запрос к Leonardo AI...</h3>";
$imageGenerationId = sendRequestToLeonardo($leonardo_key, $prompt);

if (!$imageGenerationId) {
    echo "Ошибка: не удалось получить ID генерации изображения от Leonardo.";
    exit;
}

echo "<p>Получен ID генерации: $imageGenerationId</p>";

// --- Шаг 3: Ждём и получаем URL изображения ---
echo "<h3>Ожидаем 10 секунд, чтобы получить изображение...</h3>";
$imageUrl = pollLeonardoForImage($leonardo_key, $imageGenerationId);

if ($imageUrl) {
    echo "<h2>Готовое изображение:</h2><img src='$imageUrl' style='max-width: 100%;'>";
} else {
    echo "<p>Изображение ещё не готово. Попробуйте обновить страницу позже.</p>";
}


// --- Функции ---

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
        echo "CURL ошибка при запросе к GPT: " . curl_error($ch);
        curl_close($ch);
        exit;
    }
    curl_close($ch);

    $decoded = json_decode($result, true);

    if (!$decoded) {
        echo "Ошибка декодирования JSON от GPT. Ответ сервера: $result";
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

    if (curl_errno($ch)) {
        echo "CURL ошибка при запросе к Leonardo: " . curl_error($ch);
        curl_close($ch);
        exit;
    }

    curl_close($ch);

    $data = json_decode($result, true);

    if (!$data) {
        echo "Ошибка декодирования JSON от Leonardo. Ответ сервера: $result";
        exit;
    }

    return $data['sdGenerationJob']['generationId'] ?? null;
}

function pollLeonardoForImage($key, $generationId) {
    sleep(10); // Ждём 10 секунд

    $url = "https://cloud.leonardo.ai/api/rest/v1/generations/$generationId";
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            "Authorization: Bearer $key"
        ]
    ]);
    $result = curl_exec($ch);

    if (curl_errno($ch)) {
        echo "CURL ошибка при получении изображения: " . curl_error($ch);
        curl_close($ch);
        exit;
    }

    curl_close($ch);

    $data = json_decode($result, true);

    if (!$data) {
        echo "Ошибка декодирования JSON при получении изображения. Ответ сервера: $result";
        exit;
    }

    return $data['generations_by_pk']['generated_images'][0]['url'] ?? null;
}
