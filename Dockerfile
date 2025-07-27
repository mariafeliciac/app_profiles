FROM php:8.3 as php

# Aggiorniamo il gestore pacchetti e installiamo le dipendenze
RUN apt-get update -y && apt-get install -y \
    unzip \
    libpq-dev \
    libcurl4-gnutls-dev \
    ca-certificates \
    openssl \
    curl && \
    update-ca-certificates

# Installiamo le estensioni PHP richieste per Laravel
RUN docker-php-ext-install pdo pdo_mysql bcmath
RUN docker-php-ext-enable pdo pdo_mysql

# Installiamo e abilitiamo Xdebug per il debug delle nostre applicazioni PHP
RUN pecl install xdebug && docker-php-ext-enable xdebug

# Copiamo l'applicazione e Composer
WORKDIR /var/www
COPY . .
COPY --from=composer:2.7.6 /usr/bin/composer /usr/bin/composer

# Entry point custom
ENTRYPOINT [ "Docker/entrypoint.sh" ]
