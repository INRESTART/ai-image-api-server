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
  if (!userPrompt) {
    return res.status(400).json({ error: 'userPrompt обязателен' });
  }

  try {
    // 1. Запрос промта у ChatGPT
    const gptResponse = await axios.post(
      'https://api.openai.com/v1/chat/completions',
      {
        model: 'gpt-4o-mini', // или gpt-4.0 если доступно
        messages: [
          { role: 'system', content: 'Ты помощник, который пишет промты для генерации изображений в Leonardo AI.' },
          { role: 'user', content: `Создай промт для leonardo.ai по следующему описанию: ${userPrompt}` }
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

    const generatedPrompt = gptResponse.data.choices[0].message.content;
    console.log('Сгенерированный промт:', generatedPrompt);

    // 2. Отправка запроса на генерацию изображения в Leonardo AI
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

    const generationId = leonardoResponse.data.sdGenerationJob.generationId;
    console.log('ID задачи генерации:', generationId);

    // 3. Опрос результата с задержкой (polling)
    let imageUrl = null;
    for (let i = 0; i < 10; i++) { // максимум 10 попыток с паузой 3 сек
      await new Promise(r => setTimeout(r, 3000));

      const statusResponse = await axios.get(
        `https://cloud.leonardo.ai/api/rest/v1/generations/${generationId}`,
        {
          headers: {
            Authorization: `Bearer ${process.env.LEONARDO_API_KEY}`
          }
        }
      );

      const generation = statusResponse.data;

      if (generation.generated_images && generation.generated_images.length > 0) {
        imageUrl = generation.generated_images[0].url;
        break;
      }
      console.log(`Попытка ${i + 1}: изображение ещё не готово`);
    }

    if (!imageUrl) {
      return res.status(500).json({ error: 'Изображения не сгенерированы за время ожидания' });
    }

    // 4. Отправка ссылки клиенту
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
