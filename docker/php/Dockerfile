FROM php:8.2-fpm
ARG TIMEZONE

RUN curl -sL https://deb.nodesource.com/setup_18.x | bash - \
  && apt-get update && apt-get install -y \
    openssl \
    libicu-dev \
    git \
    unzip \
    wget \
    mc \
    nodejs \
    libpng-dev \
    libzip-dev \
    librabbitmq-dev \
    bash-completion \
    graphviz

RUN echo "memory_limit = 1024M" >> /usr/local/etc/php/conf.d/docker-php-memlimit.ini \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
    && composer --version

RUN ln -snf /usr/share/zoneinfo/${TIMEZONE} /etc/localtime && echo ${TIMEZONE} > /etc/timezone \
    && printf '[PHP]\ndate.timezone = "%s"\n', ${TIMEZONE} > /usr/local/etc/php/conf.d/tzone.ini \
    && "date"

RUN docker-php-ext-install pdo pdo_mysql zip intl opcache pcntl gd \
    && docker-php-ext-configure intl \
    && pecl install amqp \
    && docker-php-ext-enable amqp

RUN wget https://get.symfony.com/cli/installer -O - | bash \
    && echo 'export PATH="$HOME/.symfony5/bin:/var/www/bin:$PATH"' >> ~/.bashrc

COPY .bash_aliases /root/.bash_aliases

RUN echo ". ~/.bash_aliases" >> ~/.bashrc

WORKDIR /var/www