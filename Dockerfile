FROM php:8.2-apache

# Copiar archivos al directorio de Apache
COPY . /var/www/html/

# Habilitar mod_rewrite de Apache
RUN a2enmod rewrite

# Exponer puerto 80
EXPOSE 80
