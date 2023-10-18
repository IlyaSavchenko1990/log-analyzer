FROM php:8.2-cli

# Add configuration files
COPY image-files/min/ /
COPY / /var/www/html/

ENV LOG_LINE_REGEXP='(\d{2}\/\d{2}\/\d{4}):(\d{2}:\d{2}:\d{2}\s).+(HTTP\/\d\.?\d?)\"(\s[1-5][0-9][0-9]\s)\d+\s(\d+\.\d+)\s' \
    DATE_RGXP_GROUP=1 \
    DATETIME_RGXP_GROUP=2 \
    RESPONSE_CODE_RGXP_GROUP=4 \
    RESPONSE_TIME_RGXP_GROUP=5

# Application environment
WORKDIR /var/www/html