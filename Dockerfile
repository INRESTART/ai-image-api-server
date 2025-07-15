FROM php:8.1-cli

WORKDIR /app

# Устанавливаем Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Копируем описание зависимостей
COPY composer.json composer.lock* /app/

# Устанавливаем библиотеки
RUN composer install --no-dev --optimize-autoloader

# Копируем весь проект
COPY . .

EXPOSE 10000

CMD ["php", "-S", "0.0.0.0:10000", "-t", "."]
