ARG COMPOSER_VERSION=2.8

FROM composer:${COMPOSER_VERSION} AS vendor

FROM registry.scaleweb.dk/koereskole-base:latest

ARG USER_ID=1000
ARG GROUP_ID=1000
ARG USER=laravel

RUN userdel --remove --force www-data || true \
    && groupadd --force -g ${GROUP_ID} ${USER} \
    && useradd -ms /bin/bash --no-log-init --no-user-group -g ${GROUP_ID} -u ${USER_ID} ${USER}

ENV ROOT="/var/www/html"
ENV USER=${USER}
ENV WITH_HORIZON=false
ENV WITH_SCHEDULER=false
ENV WITH_REVERB=false
ENV WITH_SSR=false

COPY --link --from=vendor /usr/bin/composer /usr/bin/composer
COPY --link deployment/supervisord.conf /etc/
COPY --link deployment/supervisord.frankenphp.conf /etc/supervisor/conf.d/
COPY --link deployment/supervisord.services.conf /etc/supervisor/conf.d/
COPY --link deployment/start-container /usr/local/bin/start-container
COPY --link deployment/healthcheck /usr/local/bin/healthcheck
COPY --link deployment/php.ini ${PHP_INI_DIR}/conf.d/99-php.ini
COPY --link composer.json composer.lock ./

RUN composer install \
    --no-interaction \
    --no-ansi \
    --no-scripts \
    --no-progress

COPY --link . .

RUN mkdir -p storage/framework/{sessions,views,cache}
RUN mkdir -p storage/logs
RUN mkdir -p bootstrap/cache
RUN chown -R ${USER_ID}:${GROUP_ID} storage bootstrap/cache public
RUN chmod +x /usr/local/bin/start-container /usr/local/bin/healthcheck

RUN composer dump-autoload --optimize

USER ${USER}

EXPOSE 8000
EXPOSE 2019

ENTRYPOINT ["start-container"]

HEALTHCHECK --start-period=5s --interval=2s --timeout=3s --retries=10 CMD healthcheck || exit 1
