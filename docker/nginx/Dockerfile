FROM nginx:1.19.0

ARG TIMEZONE
RUN ln -snf /usr/share/zoneinfo/$TIMEZONE /etc/localtime && echo $TIMEZONE > /etc/timezone

RUN apt-get update && apt-get install -y \
    openssl

COPY nginx.conf /etc/nginx/
COPY symfony.ssl.conf /etc/nginx/conf.d/

RUN usermod -u 1000 www-data

RUN openssl req -x509 -newkey rsa:4086 \
    -keyout /etc/ssl/private/myssl.key -out /etc/ssl/certs/myssl.crt \
    -days 3650 -nodes -sha256 \
    -subj "/C=PL/ST=City/L=Location/O=localhost/OU=reservationapp.localhost/CN=reservationapp.localhost"