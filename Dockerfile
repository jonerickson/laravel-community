############################################
# Base Image
############################################
FROM serversideup/php:8.4-fpm-nginx as base

USER root

RUN install-php-extensions intl bcmath soap gd sockets gmp imap

RUN apt-get update \
    && apt-get install -y gnupg default-mysql-client \
    && curl -fsSL https://deb.nodesource.com/setup_22.x | bash - \
    && apt-get install -y nodejs \
    && mkdir -p /etc/mysql \
    && echo "[client]\nskip-ssl=true" > /etc/my.cnf \
    && rm -rf /var/lib/apt/lists/*

############################################
# CLI Image
############################################
FROM serversideup/php:8.4-cli as cli

USER root

RUN install-php-extensions intl bcmath soap gd sockets gmp imap

RUN curl -fsSL https://deb.nodesource.com/setup_22.x | bash - \
    && apt-get install -y nodejs \
    && rm -rf /var/lib/apt/lists/*

COPY --chown=www-data:www-data . /var/www/html

RUN composer install --no-interaction --prefer-dist --optimize-autoloader \
    && npm install \
    && npm run build \
    && rm -rf node_modules

USER www-data

############################################
# Development Image
############################################
FROM base AS development

# We can pass USER_ID and GROUP_ID as build arguments
# to ensure the www-data user has the same UID and GID
# as the user running Docker.
ARG USER_ID
ARG GROUP_ID

USER root

# Update the image www-data UID/GID to match host UID/GID
RUN if [ -n "$USER_ID" ] && [ -n "$GROUP_ID" ]; then \
        docker-php-serversideup-set-id www-data $USER_ID:$GROUP_ID && \
        docker-php-serversideup-set-file-permissions --owner $USER_ID:$GROUP_ID \
    else \
        echo "⚠ USER_ID or GROUP_ID not set, skipping permissions setup"; \
    fi

USER www-data

############################################
# Devcontainer Image
############################################
FROM base AS devcontainer

ARG USER_ID=1000
ARG GROUP_ID=1000
ARG USERNAME=vscode

USER root

RUN addgroup --gid $GROUP_ID $USERNAME || echo "Group exists" \
    && adduser --uid $USER_ID --gid $GROUP_ID --disabled-password --gecos "" $USERNAME \
    && usermod -aG www-data $USERNAME

RUN docker-php-serversideup-set-file-permissions --owner $USER_ID:$GROUP_ID

USER $USERNAME

############################################
# Build Image
############################################
FROM base AS build

USER root

ARG VITE_APP_NAME
ARG VITE_APP_ENV
ARG VITE_APP_VERSION
ARG VITE_FINGERPRINT_PUBLIC_KEY
ARG VITE_FINGERPRINT_ENDPOINT
ARG VITE_FINGERPRINT_SCRIPT_URL_PATTERN
ARG VITE_SENTRY_ORG
ARG VITE_SENTRY_REACT_PROJECT
ARG VITE_SENTRY_REACT_DSN
ARG VITE_SENTRY_AUTH_TOKEN
ARG VITE_STRIPE_KEY
ARG VITE_SSR_PORT

ENV VITE_APP_NAME=${VITE_APP_NAME}
ENV VITE_APP_ENV=${VITE_APP_ENV}
ENV VITE_APP_VERSION=${VITE_APP_VERSION}
ENV VITE_FINGERPRINT_PUBLIC_KEY=${VITE_FINGERPRINT_PUBLIC_KEY}
ENV VITE_FINGERPRINT_ENDPOINT=${VITE_FINGERPRINT_ENDPOINT}
ENV VITE_FINGERPRINT_SCRIPT_URL_PATTERN=${VITE_FINGERPRINT_SCRIPT_URL_PATTERN}
ENV VITE_SENTRY_ORG=${VITE_SENTRY_ORG}
ENV VITE_SENTRY_REACT_PROJECT=${VITE_SENTRY_REACT_PROJECT}
ENV VITE_SENTRY_REACT_DSN=${VITE_SENTRY_REACT_DSN}
ENV VITE_SENTRY_AUTH_TOKEN=${VITE_SENTRY_AUTH_TOKEN}
ENV VITE_STRIPE_KEY=${VITE_STRIPE_KEY}
ENV VITE_SSR_PORT=${VITE_SSR_PORT}

COPY . /var/www/html

RUN composer install --no-interaction --prefer-dist --optimize-autoloader \
    && npm install \
    && npm run build \
    && rm -rf node_modules

USER www-data

############################################
# CI Image
############################################
FROM build AS ci

USER root

COPY --from=build --chown=www-data:www-data /var/www/html /var/www/html

############################################
# Production Image
############################################
FROM build AS production

USER root

COPY --chmod=755 ./docker/etc/entrypoint.d/ /etc/entrypoint.d/
COPY --from=build --chown=www-data:www-data /var/www/html /var/www/html

RUN composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev

USER www-data
