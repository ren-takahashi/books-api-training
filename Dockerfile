FROM php:8.4-fpm-alpine

# 作業ディレクトリ
WORKDIR /srv/app

# 必要なパッケージのインストール
RUN apk add --no-cache \
    git \
    curl \
    libpng-dev \
    oniguruma-dev \
    libxml2-dev \
    zip \
    unzip \
    postgresql-dev \
    libzip-dev

# PHP拡張機能のインストール
RUN docker-php-ext-install \
    pdo_pgsql \
    pgsql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    zip

# Composerのインストール
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# nginxのインストール（Alpine用）
RUN apk add --no-cache nginx

# nginx設定ファイルのコピー
COPY nginx.conf /etc/nginx/http.d/default.conf

# エントリーポイントスクリプトの作成（起動時に権限設定も実行）
RUN \
    # シェルスクリプトのヘッダー作成
    echo '#!/bin/sh' > /entrypoint.sh && \
    \
    # Laravel の storage と bootstrap/cache に書き込み権限を設定
    echo '# Laravel の storage と bootstrap/cache に書き込み権限を設定' >> /entrypoint.sh && \
    echo 'if [ -d /srv/app/storage ]; then' >> /entrypoint.sh && \
    echo '  chmod -R 775 /srv/app/storage /srv/app/bootstrap/cache 2>/dev/null || true' >> /entrypoint.sh && \
    echo '  chown -R www-data:www-data /srv/app/storage /srv/app/bootstrap/cache 2>/dev/null || true' >> /entrypoint.sh && \
    echo 'fi' >> /entrypoint.sh && \
    echo '' >> /entrypoint.sh && \
    \
    # PHP-FPM と nginx の起動設定
    echo '# PHP-FPM と nginx を起動' >> /entrypoint.sh && \
    echo 'php-fpm -D' >> /entrypoint.sh && \
    echo 'nginx -g "daemon off;"' >> /entrypoint.sh && \
    \
    # スクリプトに実行権限を付与
    chmod +x /entrypoint.sh

# ポート公開
EXPOSE 80

# エントリーポイント
ENTRYPOINT ["/entrypoint.sh"]
