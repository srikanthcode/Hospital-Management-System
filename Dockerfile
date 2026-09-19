FROM php:8.3-apache

# Install MariaDB server + PHP extensions
RUN apt-get update && apt-get install -y \
        mariadb-server \
        libpng-dev libjpeg-dev libfreetype6-dev libzip-dev libicu-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        mysqli pdo pdo_mysql gd zip intl \
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

# Copy startup script
COPY docker-start.sh /usr/local/bin/docker-start.sh
RUN chmod +x /usr/local/bin/docker-start.sh

# Create directory for MariaDB data persistence within the container
RUN mkdir -p /run/mysqld && chown mysql:mysql /run/mysqld

# Writable uploads directory
RUN mkdir -p /var/www/html/uploads && chown www-data:www-data /var/www/html/uploads

EXPOSE 80

ENTRYPOINT ["/usr/local/bin/docker-start.sh"]
