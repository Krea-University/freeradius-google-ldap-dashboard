FROM php:8.2-apache

# Install minimal dependencies and PHP extensions
RUN apt-get update && apt-get install -y \
    git curl unzip ca-certificates \
    libmariadb-dev \
    && rm -rf /var/lib/apt/lists*

# Install PHP PDO MySQL extension
RUN docker-php-ext-install pdo_mysql

# Enable Apache mod_rewrite and headers
RUN a2enmod rewrite headers

# Install envsubst for environment variable substitution
RUN apt-get update && apt-get install -y gettext-base && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html/radius-gui

# Copy composer files first for better caching
COPY composer.json composer.lock* ./

# Install PHP dependencies (TCPDF)
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Copy rest of application files
COPY . /var/www/html/radius-gui/

# Create logs directory
RUN mkdir -p /var/www/html/radius-gui/logs

# Set permissions
RUN chown -R www-data:www-data /var/www/html/radius-gui \
    && chmod -R 755 /var/www/html/radius-gui \
    && chmod -R 775 /var/www/html/radius-gui/logs

# Configure Apache virtual host with environment variable passing
RUN echo '<VirtualHost *:80>\n\
    ServerName localhost\n\
    DocumentRoot /var/www/html/radius-gui/public\n\
\n\
    # Pass environment variables to PHP\n\
    SetEnv DB_HOST $DB_HOST\n\
    SetEnv DB_PORT $DB_PORT\n\
    SetEnv DB_NAME $DB_NAME\n\
    SetEnv DB_USER $DB_USER\n\
    SetEnv DB_PASSWORD $DB_PASSWORD\n\
    SetEnv DB_ROOT_PASSWORD $DB_ROOT_PASSWORD\n\
    SetEnv APP_TIMEZONE $APP_TIMEZONE\n\
    SetEnv APP_URL $APP_URL\n\
    SetEnv APP_ENV $APP_ENV\n\
    SetEnv APP_DEBUG $APP_DEBUG\n\
\n\
    <Directory /var/www/html/radius-gui/public>\n\
        Options -Indexes +FollowSymLinks\n\
        AllowOverride All\n\
        Require all granted\n\
\n\
        # Rewrite rules for clean URLs\n\
        RewriteEngine On\n\
        RewriteCond %{REQUEST_FILENAME} !-f\n\
        RewriteCond %{REQUEST_FILENAME} !-d\n\
        RewriteRule ^(.*)$ index.php?page=$1 [QSA,L]\n\
    </Directory>\n\
\n\
    # Security headers\n\
    Header always set X-Content-Type-Options "nosniff"\n\
    Header always set X-Frame-Options "SAMEORIGIN"\n\
    Header always set X-XSS-Protection "1; mode=block"\n\
\n\
    ErrorLog ${APACHE_LOG_DIR}/radius-gui-error.log\n\
    CustomLog ${APACHE_LOG_DIR}/radius-gui-access.log combined\n\
</VirtualHost>' > /etc/apache2/sites-available/000-default.conf

# Create a PHP configuration to pass environment variables
RUN mkdir -p /usr/local/etc/php/conf.d && \
    echo 'auto_prepend_file=/var/www/html/radius-gui/app/bootstrap.php' > /usr/local/etc/php/conf.d/bootstrap.ini

# Expose port 80
EXPOSE 80

# Start Apache in foreground
CMD ["apache2-foreground"]
