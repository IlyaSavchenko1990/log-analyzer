FROM php:8.2-cli

# Add configuration files
COPY image-files/min/ /
COPY / /var/www/html/

# Application environment
WORKDIR /var/www/html

# CMD php index.php --help
# CMD cat access.log | php index.php -t 34 -u 99.7 -s 1000