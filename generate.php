<?php
require 'vendor/autoload.php';

$openai_key = $_ENV['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'];
$leonardo_key = $_ENV['c70f6c43-36ee-4ef3-9d67-4a1524c73c78'];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $input = trim($_POST['text'] ?? '');
    if ($input === '') {
        echo "Ошибка: пустой ввод.";
        exit;
    }

    // 1. Получаем промт от GPT
    $prompt_request = [
        "model" => "gpt-4.1",
        "messages" => [
            ["role" => "user", "content" => "Сгенерируй краткий промт на английском для нейросети Leonardo AI, чтобы создать изображение услуги: '$input'. Стиль — рекламное фото, реалистичное."]
        ]
    ];

    $ch = curl_init("https://api.openai.com/v1/chat/completions");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer $openai_key",
        "Content-Type: application/json"
    ]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($prompt_request));

    $response = curl_exec($ch);
    if (curl_errno($ch)) {
        echo "Ошибка OpenAI: " . curl_error($ch);
        exit;
    }
    curl_close($ch);

    $result = json_decode($response, true);
    $final_prompt = $result['choices'][0]['message']['content'] ?? null;
    if (!$final_prompt) {
        echo "Ошибка: не удалось получить промт от GPT.";
        exit;
    }

    // 2. Генерация изображения Leonardo AI
    $generation_request = [
        "prompt" => $final_prompt,
        "modelId" => "1dd50843-d653-4516-a8e3-f0238ee453ff",
        "width" => 1280,
        "height" => 960,
        "num_images" => 1
    ];

    $ch2 = curl_init("https://cloud.leonardo.ai/api/rest/v1/generations");
    curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch2, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer $leonardo_key",
        "Content-Type: application/json"
    ]);
    curl_setopt($ch2, CURLOPT_POST, true);
    curl_setopt($ch2, CURLOPT_POSTFIELDS, json_encode($generation_request));

    $image_response = curl_exec($ch2);
    if (curl_errno($ch2)) {
        echo "Ошибка Leonardo: " . curl_error($ch2);
        exit;
    }
    curl_close($ch2);

    $image_data = json_decode($image_response, true);
    $url = $image_data['generations_by_pk']['generated_images'][0]['url'] ?? null;

    if ($url) {
        echo $url;
    } else {
        echo "Не удалось получить изображение.";
    }
}
?>
