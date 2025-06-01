FROM php:8.3-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git unzip curl libzip-dev libpng-dev libonig-dev libxml2-dev \
    supervisor nginx

# Install PHP extensions
RUN docker-php-ext-install pdo pdo_mysql zip intl

# PHP config
COPY ./docker/php/php.ini /usr/local/etc/php/php.ini

# Install Composer v2
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Supervisor
COPY ./docker/supervisor/supervisord.conf /etc/supervisord.conf


# Installe Node.js et npm dans le conteneur PHP (optionnel)
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - && \
    apt-get install -y nodejs

# Copier le projet
WORKDIR /var/www

# Copier tout le projet en une fois (afin que bin/console soit présent)
COPY . .

# Exposer le port 80
EXPOSE 80

# Supervisord comme point d’entrée
CMD ["/usr/bin/supervisord", "-n", "-c", "/etc/supervisord.conf"]
