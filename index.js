import express from 'express';
import cors from 'cors';
import dotenv from 'dotenv';
import axios from 'axios';

dotenv.config();
const app = express();
app.use(cors());
app.use(express.json());

const PORT = process.env.PORT || 3000;

app.post('/generate', async (req, res) => {
  const { userPrompt } = req.body;

  try {
    // 1. Генерация промта через OpenAI (ChatGPT)
    const gptResponse = await axios.post(
      'https://api.openai.com/v1/chat/completions',
      {
        model: 'o4-mini-2025-04-16',
        messages: [
          {
            role: 'system',
            content: 'Ты помощник, который пишет промты для генерации изображений в Leonardo AI.'
          },
          {
            role: 'user',
            content: `Создай промт для leonardo.ai по следующему описанию: ${userPrompt}`
          }
        ],
        max_tokens: 500,
        temperature: 0.8
      },
      {
        headers: {
          Authorization: `Bearer ${process.env.OPENAI_API_KEY}`,
          'Content-Type': 'application/json'
        }
      }
    );

    const generatedPrompt = gptResponse.data.choices[0].message.content.trim();

// 2. Генерация изображения через Leonardo AI
const leonardoResponse = await axios.post(
  'https://cloud.leonardo.ai/api/rest/v1/generations',
  {
    prompt: generatedPrompt,
    width: 512,
    height: 512,
    num_images: 1,
    guidance_scale: 7,
    num_inference_steps: 30
  },
  {
    headers: {
      Authorization: `Bearer ${process.env.LEONARDO_API_KEY}`,
      'Content-Type': 'application/json'
    }
  }
);

// Проверим наличие сгенерированных изображений
const generations = leonardoResponse.data.generations;

if (!generations || generations.length === 0 || !generations[0].generated_images || generations[0].generated_images.length === 0) {
  return res.status(500).json({ error: 'Изображения не сгенерированы' });
}

// Получаем URL изображения
const imageUrl = generations[0].generated_images[0].url;

// Отправляем клиенту
res.json({ url: imageUrl });

  } catch (error) {
    console.error('Ошибка в /generate:', error.response?.data || error.message || error);
    res.status(500).json({ error: 'Ошибка при генерации изображения' });
  }
});

app.get('/', (req, res) => {
  res.send('AI Image API Server работает ✅');
});

app.listen(PORT, () => {
  console.log(`Сервер запущен на порту ${PORT}`);
});
