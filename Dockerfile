FROM php:7.2-alpine3.9

WORKDIR /app
COPY . .
RUN curl -s https://getcomposer.org/installer | php;\
mv composer.phar /usr/local/bin/composer;\
composer install

WORKDIR /app/public

CMD [ "php", "-S", "localhost:8080" ]
