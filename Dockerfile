# Imagem oficial do PHP (versão de linha de comando)
FROM php:8.3-cli

# Dependências do sistema e extensões do PHP usadas pelo Laravel
RUN apt-get update && apt-get install -y unzip libzip-dev libonig-dev libsqlite3-dev \
    && docker-php-ext-install mbstring pdo_mysql pdo_sqlite zip

# Copia o Composer de outra imagem oficial
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
ENV COMPOSER_HOME=/tmp/composer

# Pasta onde o código do projeto vai ficar dentro do container
WORKDIR /var/www/html


# Porta em que o servidor do Laravel escuta
EXPOSE 8000


# Instala as dependências e sobe o servidor embutido do Laravel
CMD composer install && php artisan serve --host=0.0.0.0 --port=8000
