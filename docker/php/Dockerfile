FROM php:8.2-fpm-alpine

# Install SQLite + PHP extensions
RUN apk add --no-cache \
    sqlite \
    sqlite-dev \
    && docker-php-ext-install pdo pdo_sqlite

# Create www user (security)
RUN addgroup -g 1000 www && \
    adduser -u 1000 -D www -G www

# Working directory
WORKDIR /var/www/html

# Permissions
RUN chown -R www:www /var/www/html

USER www

EXPOSE 9000

CMD ["php-fpm"]
