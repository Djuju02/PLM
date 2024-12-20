FROM php:7.4-apache

# Installer mysqli
RUN apt-get update && docker-php-ext-install mysqli && docker-php-ext-enable mysqli

# Copier les fichiers sources
COPY src/ /var/www/html/

# Droits (optionnel)
RUN chown -R www-data:www-data /var/www/html
