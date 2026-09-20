FROM php:8.3-apache

# libpq (PostgreSQL client lib) + PHP extensions.
# mysqli/mysql are intentionally NOT installed: the app talks to PostgreSQL
# through the PDO pdo_pgsql driver via includes/mysqli_compat.php.
RUN apt-get update && apt-get install -y \
        libpq-dev \
        libpng-dev libjpeg-dev libfreetype6-dev libzip-dev libicu-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo pdo_pgsql gd zip intl \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Apache config
RUN a2enmod rewrite
ENV APACHE_DOCUMENT_ROOT /var/www/html
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' \
        /etc/apache2/sites-available/*.conf \
        /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf
RUN sed -ri 's/AllowOverride None/AllowOverride All/g' \
        /etc/apache2/apache2.conf /etc/apache2/sites-available/*.conf

# Copy application
COPY hospital_management/ /var/www/html/

EXPOSE 80

CMD ["apache2-foreground"]
