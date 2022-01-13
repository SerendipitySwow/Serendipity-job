FROM hyperf/hyperf:8.0-alpine-v3.15-swow

LABEL maintainer="Hyperf Developers <group@hyperf.io>" version="1.0" license="MIT"

##
# ---------- env settings ----------
##
# --build-arg timezone=Asia/Shanghai
ARG timezone

ENV TIMEZONE=${timezone:-"Asia/Shanghai"}
#    COMPOSER_VERSION=2.2.4
# update
RUN set -ex \
    && apk update \
    # install composer \
#    https://github.com/composer/composer/releases/download/${COMPOSER_VERSION}/composer.phar
    && wget -nv -O /usr/local/bin/composer https://gitee.com/H_Peter/composer/repository/archive/2.2.4 \
    && chmod u+x /usr/local/bin/composer \
    # show php version and extensions
    && php -v \
    && php -m \
    #  ---------- some config ----------
    && cd /etc/php8 \
    # - config PHP
    && { \
        echo "upload_max_filesize=100M"; \
        echo "post_max_size=108M"; \
        echo "memory_limit=1024M"; \
        echo "date.timezone=${TIMEZONE}"; \
    } | tee conf.d/99-overrides.ini \
    # - config timezone
    && ln -sf /usr/share/zoneinfo/${TIMEZONE} /etc/localtime \
    && echo "${TIMEZONE}" > /etc/timezone \
    # ---------- clear works ----------
    && rm -rf /var/cache/apk/* /tmp/* /usr/share/man \
    && echo -e "\033[42;37m Build Completed :).\033[0m\n"

COPY . /opt/www

WORKDIR /opt/www

#RUN composer install --no-dev -o

EXPOSE 9501

