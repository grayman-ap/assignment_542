FROM php:8.3-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        default-mysql-client \
        unzip \
    && docker-php-ext-install pdo_mysql mysqli \
    && a2enmod headers rewrite \
    && rm -rf /var/lib/apt/lists/*

COPY docker/apache-vhost.conf /etc/apache2/sites-available/000-default.conf

RUN printf 'ServerSignature Off\nServerTokens Prod\n' > /etc/apache2/conf-available/security-hardening.conf \
    && a2enconf security-hardening

WORKDIR /var/www/html

# Pre-create writable runtime directories so the named volumes (logs, uploads)
# are owned by the web server user.
RUN mkdir -p /var/www/html/app/logs /var/www/html/app/storage/documents \
    && chown -R www-data:www-data /var/www/html/app

EXPOSE 80
