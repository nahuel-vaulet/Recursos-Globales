FROM php:8.2-apache

# ─── Install PostgreSQL + CURL drivers ──────────────────
RUN apt-get update && apt-get install -y \
    libpq-dev \
    libcurl4-openssl-dev \
    libzip-dev \
    unzip \
    && docker-php-ext-install pdo pdo_pgsql pdo_mysql curl zip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# ─── Enable Apache mod_rewrite ─────────────────────────
RUN a2enmod rewrite

# ─── Apache config: allow .htaccess overrides ──────────
RUN sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

# ─── Set working directory ─────────────────────────────
WORKDIR /var/www/html

# ─── Copy backend application ─────────────────────────
COPY backend/ ./backend/
COPY frontend/ ./frontend/

# ─── Install Composer dependencies ─────────────────────
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
RUN cd backend && composer install --no-dev --optimize-autoloader --no-interaction

# ─── Copy root entry point that routes to backend/frontend ──
COPY docker/apache-vhost.conf /etc/apache2/sites-available/000-default.conf

# ─── Permissions ───────────────────────────────────────
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80
