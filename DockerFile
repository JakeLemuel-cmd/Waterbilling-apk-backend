FROM php:8.2-apache

# Enable Apache modules
RUN a2enmod rewrite headers

# Install MySQL PDO
RUN docker-php-ext-install pdo pdo_mysql

# Copy backend into Apache root
COPY backend/ /var/www/html/

# Allow .htaccess (optional)
RUN sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

EXPOSE 80
