

ARG ALPINE_VERSION=3.15

FROM hyperf/hyperf:8.0-alpine-v${ALPINE_VERSION}-base

LABEL maintainer="Hyperf Developers <group@hyperf.io>" version="1.0" license="MIT"

ARG SW_VERSION
ARG COMPOSER_VERSION

##
# ---------- env settings ----------
##
ENV SW_VERSION=${SW_VERSION:-"develop"} \
    COMPOSER_VERSION=${COMPOSER_VERSION:-"2.2.1"} \
    TIMEZONE=${timezone:-"Asia/Shanghai"} \
    #  install and remove building packages
    PHPIZE_DEPS="autoconf automake gcc g++ make php8-dev php8-pear file re2c openssl-dev curl-dev"

# update
RUN set -ex \
    && apk update \
    # for swow extension libaio linux-headers
    && apk add --no-cache openssl git bash \
    && apk add --no-cache --virtual .build-deps $PHPIZE_DEPS \
    # download
    && cd /tmp \
    && git clone "https://github.com/swow/swow" \
    && ls -alh \
    # php extension:swow
    && ln -s /usr/bin/phpize8 /usr/local/bin/phpize \
    && ln -s /usr/bin/php-config8 /usr/local/bin/php-config \
    && ( \
        cd swow/ext \
        && phpize \
        && ./configure --enable-swow --enable-swow-ssl --enable-swow-curl \
        && make -s -j$(nproc) && make install \
    ) \
    && echo "memory_limit=1G" > /etc/php8/conf.d/00_default.ini \
    && echo "opcache.enable_cli = 'On'" >> /etc/php8/conf.d/00_opcache.ini \
    && echo "extension=swow.so" > /etc/php8/conf.d/50_swow.ini \
    # php extension:simdjson_php
    && cd /tmp \
    && git clone  "https://github.com/crazyxman/simdjson_php"  \
    && ls -alh \
    && cd /simdjson_php \
    && phpize \
    && ./configure \
    && make -s -j$(nproc) && make install \
    && echo "extension=simdjson.so" > /etc/php8/conf.d/50_simdjson.ini \
    # install composer
    && wget -nv -O /usr/local/bin/composer https://github.com/composer/composer/releases/download/${COMPOSER_VERSION}/composer.phar \
    && chmod u+x /usr/local/bin/composer \
    # php info
    && php -v \
    && php -m \
    && php --ri swow \
    && composer \
    # ---------- clear works ----------
    && apk del .build-deps \
    && rm -rf /var/cache/apk/* /tmp/* /usr/share/man /usr/local/bin/php* \
    && echo -e "\033[42;37m Build Completed :).\033[0m\n"
RUN ln -sf /usr/share/zoneinfo/${TIMEZONE} /etc/localtime \
    && echo "${TIMEZONE}" > /etc/timezone

WORKDIR /opt/www


COPY . /opt/www
RUN composer install --no-dev -o

EXPOSE 9501 9502 9503
