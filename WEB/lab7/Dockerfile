FROM php:8.2-fpm

RUN apt-get update && apt-get install -y \
    git \
    unzip \
    curl \
    wget \
    && docker-php-ext-install pdo pdo_mysql sockets \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

# Просто копируем исходный код, зависимости установим позже
COPY ./code /var/www/html

# Устанавливаем RabbitMQ клиент вручную (если нужно)
RUN echo "<?php // No external dependencies needed for basic RabbitMQ ?>" > /var/www/html/dependencies.php

CMD ["php-fpm"]