
FROM php:8.4-fpm

WORKDIR /var/www/html

# Установка рекомендуемых расширений (по желанию)
RUN apt-get update && apt-get install -y \
    libzip-dev \
    zip \
    && docker-php-ext-install zip

    # Установка Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer


# Копируем исходный код
COPY . /var/www/html/

RUN composer install

# Устанавливаем права (если нужно)
RUN chown -R www-data:www-data /var/www/html
