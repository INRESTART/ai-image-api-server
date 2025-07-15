FROM php:8.1-cli

WORKDIR /app

# Установка Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

COPY composer.json composer.lock* /app/

RUN composer install --no-dev --optimize-autoloader

COPY . .

EXPOSE 10000

CMD ["php", "-S", "0.0.0.0:10000", "-t", "."]
