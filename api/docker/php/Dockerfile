
FROM php:8.2-fpm

WORKDIR /var/www/html

# Установка рекомендуемых расширений (по желанию)
RUN apt-get update && apt-get install -y \
    libzip-dev \
    zip \
    && docker-php-ext-install zip

# Копируем исходный код
COPY public/ /var/www/html/

# Устанавливаем права (если нужно)
RUN chown -R www-data:www-data /var/www/html
