FROM php:8.1-cli

WORKDIR /app

# Установка Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Копируем composer.json (и lock-файл, если есть)
COPY composer.json /app/

# Устанавливаем зависимости
RUN composer install --no-dev

# Копируем всё остальное
COPY . .

EXPOSE 10000

CMD ["php", "-S", "0.0.0.0:10000", "-t", "."]
