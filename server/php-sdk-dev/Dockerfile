FROM eu.gcr.io/nektria/php:8.4
USER root
WORKDIR /app
RUN unset APP_ENV

#RUN apk del php84
#RUN apk del php
#RUN rm /usr/bin/php
#RUN rm /usr/bin/php84
#RUN cp /usr/bin/php84 /usr/bin/php
#RUN cp /usr/bin/php84 /usr/bin/php84

ENV PATH ~/.composer/vendor/bin:$PATH

RUN apk add php-yaml

RUN rm -i -f /usr/bin/php
RUN ln -sf /usr/bin/php84 /usr/bin/php

RUN composer.phar global require phpstan/phpstan phpstan/phpstan-mockery phpstan/phpstan-phpunit phpstan/phpstan-strict-rules phpstan/phpstan-symfony phpstan/phpstan-doctrine shipmonk/phpstan-rules spaze/phpstan-disallowed-calls ergebnis/phpstan-rules
RUN composer.phar global require friendsofphp/php-cs-fixer:dev-master
RUN composer.phar global require squizlabs/php_codesniffer
RUN composer.phar global require phpunit/phpunit

ADD server/php-sdk-dev/php.ini /etc/php84/conf.d/nektria_custom.ini
COPY composer.json /app/composer.json
COPY composer.lock /app/composer.lock
#COPY symfony.lock /app/symfony.lock
COPY public /app/public

RUN composer.phar install

ENV PHP_CS_FIXER_IGNORE_ENV=1

CMD sh server/php-sdk-dev/web.sh
