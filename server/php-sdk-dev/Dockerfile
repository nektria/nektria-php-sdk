FROM eu.gcr.io/nektria/nektria-be:php8.5
USER root
WORKDIR /app
RUN unset APP_ENV

ENV PATH ~/.composer/vendor/bin:$PATH

RUN apk add php-yaml

RUN rm -i -f /usr/bin/php
RUN ln -sf /usr/bin/php85 /usr/bin/php

RUN composer.phar global require phpstan/phpstan phpstan/phpstan-mockery phpstan/phpstan-phpunit phpstan/phpstan-strict-rules phpstan/phpstan-symfony phpstan/phpstan-doctrine shipmonk/phpstan-rules spaze/phpstan-disallowed-calls ergebnis/phpstan-rules
RUN composer.phar global require friendsofphp/php-cs-fixer:dev-master
RUN composer.phar global require squizlabs/php_codesniffer
RUN composer.phar global require phpunit/phpunit

ADD server/php-sdk-dev/php.ini /etc/php85/conf.d/nektria_custom.ini
COPY composer.json /app/composer.json
COPY composer.lock /app/composer.lock
#COPY symfony.lock /app/symfony.lock
COPY public /app/public

RUN composer.phar install

ENV PHP_CS_FIXER_IGNORE_ENV=1

CMD sh server/php-sdk-dev/web.sh
