FROM php:7.3-apache 
RUN docker-php-ext-install mysqli
RUN apt-get update \ 
    && apt-get install -y libzip-dev \ 
    && apt-get install -y zlib1g-dev \ 
    && apt-get install -y libicu-dev g++ \ 
    && rm -rf /var/lib/apt/lists/* \ 
    && docker-php-ext-install zip \ 
    && docker-php-ext-configure intl \ 
    && docker-php-ext-install intl

RUN a2enmod rewrite