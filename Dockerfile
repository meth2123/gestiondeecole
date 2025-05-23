# Utiliser une image officielle PHP avec Apache
FROM php:8.2-apache

# Installer l'extension mysqli
RUN docker-php-ext-install mysqli && docker-php-ext-enable mysqli

# Activer mod_rewrite si vous utilisez .htaccess
RUN a2enmod rewrite

# Copier votre code dans le conteneur
COPY . /var/www/html/

# Donner les bons droits
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Exposer le port par défaut d'Apache
EXPOSE 8080
