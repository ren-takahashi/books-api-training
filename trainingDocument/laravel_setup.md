# 🚀 Laravel API 環境構築ガイド（books-api-training）

この手順は、**親ディレクトリのdocker-compose.ymlと連携してLaravel APIの開発環境を構築する**ためのものです。

## このセットアップの目的
- 親ディレクトリ（booksService-training）のDocker環境と連携する
- PostgreSQLを使用したLaravel API環境を構築する
- React Frontend（後に実装）との連携を見据えた構成にする
- ポートフォリオとして完成度の高いプロジェクトにする

---

## 前提条件

### プロジェクト構成の確認

このプロジェクトは以下の構成になっています：

```
~/projects/booksService-training/        ← 親ディレクトリ
├── docker-compose.yml                   ← 全体のコンテナ管理（作成済み）
├── README.md
├── books-api-training/                  ← 現在のリポジトリ（ここ）
│   ├── Dockerfile                       ← これから作成
│   ├── app/                             ← Laravelプロジェクト（これから作成）
│   ├── trainingDocument/
│   └── README.md
└── books-frontend-training/             ← 後で作成予定
    ├── Dockerfile
    └── src/
```

### 必要なツール

以下のツールがインストールされていることを確認してください。

#### 1. Docker & Docker Compose
- Docker: コンテナ実行環境
- Docker Compose: 複数コンテナの管理ツール

**インストール確認コマンド:**
```bash
docker --version
docker compose version
```

**インストールされていない場合:**
- Linux/WSL環境: [docker_cli.md](../setup/docker/docker_cli.md) を参照
- Windows/Mac: Docker Desktop をインストール
  - 公式サイト: https://www.docker.com/products/docker-desktop

#### 2. Composer（オプション）
Laravelプロジェクトの作成に必要ですが、Dockerコンテナ内で実行可能です。

**インストール確認コマンド:**
```bash
composer --version
```

---

## Laravel API 環境構築手順

### ステップ1: Dockerfileの作成

`books-api-training`ディレクトリ直下に`Dockerfile`を作成します。

**作成場所:** `/home/takahashiren/projects/booksService-training/books-api-training/Dockerfile`

```dockerfile
FROM php:8.3-fpm-alpine

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
    echo 'if [ -d /srv/app/app/storage ]; then' >> /entrypoint.sh && \
    echo '  chmod -R 775 /srv/app/app/storage /srv/app/app/bootstrap/cache 2>/dev/null || true' >> /entrypoint.sh && \
    echo '  chown -R www-data:www-data /srv/app/app/storage /srv/app/app/bootstrap/cache 2>/dev/null || true' >> /entrypoint.sh && \
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
```

### ステップ2: nginx設定ファイルの作成

`books-api-training`ディレクトリ直下に`nginx.conf`を作成します。

**作成場所:** `/home/takahashiren/projects/booksService-training/books-api-training/nginx.conf`

```nginx
server {
    listen 80;
    server_name localhost;
    root /srv/app/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php index.html;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass 127.0.0.1:9000;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

### ステップ3: Laravelプロジェクトの作成

`books-api-training/app`ディレクトリにLaravelプロジェクトを作成します。

**Composerがローカル環境にインストールされている場合（gitのインストールのようにシステム自体にインストールがされている場合）:**
```bash
cd /home/takahashiren/projects/booksService-training/books-api-training
composer create-project laravel/laravel app
```

**Composerがローカル環境にない場合（Dockerコンテナ経由でインストール可能）:**
```bash
cd /home/takahashiren/projects/booksService-training/books-api-training

# Docker の PHP イメージを使って Laravel プロジェクトを作成
docker run --rm \
    -u "$(id -u):$(id -g)" \
    -v $(pwd):/srv \
    -w /srv \
    composer:latest \
    create-project laravel/laravel app
```
- システムにComposerがない場合は、上記のDockerコマンドを使用してLaravelプロジェクトを作成する。

### ステップ4: .envファイルの設定

`books-api-training/app/.env`ファイルを編集します。

```bash
cd /home/takahashiren/projects/booksService-training/books-api-training/app
# エディタで.envを開く
code .env  # または vim .env
```

**重要な設定項目（PostgreSQL用）:**
```env
APP_NAME="BooksAPI"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8080

DB_CONNECTION=pgsql
DB_HOST=db                    # docker-compose.ymlのサービス名
DB_PORT=5432
DB_DATABASE=books_app
DB_USERNAME=books_user
DB_PASSWORD=books_pass
```

### ステップ5: Dockerコンテナの起動

親ディレクトリに移動して、docker-composeで起動します。

```bash
cd /home/takahashiren/projects/booksService-training
docker compose up -d
```

### ステップ6: アプリケーションキーの生成
プロジェクトファイル生成時には自動で生成されている場合はスキップでOK。
（.envファイルのAPP_KEYが空かどうかを確認）

```bash
# APIコンテナに入る
docker exec -it books-api sh

# アプリケーションキーを生成
cd app
php artisan key:generate

# コンテナから退出
exit
```

### ステップ7: データベースマイグレーション

```bash
# APIコンテナに入る
docker exec -it books-api sh

# マイグレーション実行
cd app
php artisan migrate

# コンテナから退出
exit
```

### ステップ8: 動作確認

ブラウザで以下にアクセス:
```
http://localhost:8080
```

Laravel のウェルカムページが表示されれば成功。
権限エラーが出た場合は、storageとbootstrap/cacheの権限を確認。

---

## 開発時の基本コマンド

### コンテナ操作

```bash
# コンテナ起動（親ディレクトリで実行）
cd /home/takahashiren/projects/booksService-training
docker compose up -d

# コンテナ停止
docker compose down

# コンテナのログ確認
docker compose logs -f api

# APIコンテナに入る
docker exec -it books-api sh
```

### Laravel操作（コンテナ内）

```bash
# コンテナに入る
docker exec -it books-api sh
cd app

# マイグレーション
php artisan migrate

# マイグレーションのロールバック
php artisan migrate:rollback

# コントローラー作成
php artisan make:controller BookController

# モデル作成（マイグレーションも同時作成）
php artisan make:model Book -m

# キャッシュクリア
php artisan cache:clear
php artisan config:clear
php artisan route:clear
```

---

## トラブルシューティング

### 1. ポート8080が使用中

```bash
# 使用中のプロセスを確認
sudo lsof -i :8080

# docker-compose.ymlのポートを変更
# api:
#   ports:
#     - "8081:80"  # 8080 → 8081に変更
```

### 2. データベース接続エラー

```bash
# DBコンテナの状態確認
docker compose ps

# DBコンテナのログ確認
docker compose logs db

# .envファイルの設定を再確認
# DB_HOST=db （サービス名と一致しているか）
```

### 3. パーミッションエラー

```bash
# storage/logs へのエラーの場合
docker exec -it books-api sh
cd app
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

### 4. Composerパッケージのインストール

```bash
docker exec -it books-api sh
cd app
composer install
```

---

## 親ディレクトリのdocker-compose.yml（参考）

現在の親ディレクトリには以下の`docker-compose.yml`が配置されています：

```yaml
version: '3.8'

services:
  # PostgreSQL データベース
  db:
    image: postgres:15-alpine
    container_name: books-db
    environment:
      POSTGRES_DB: books_app
      POSTGRES_USER: books_user
      POSTGRES_PASSWORD: books_pass
    ports:
      - "5433:5432"
    volumes:
      - db_data:/var/lib/postgresql/data
    networks:
      - books-network

  # Laravel API
  api:
    build:
      context: ./books-api-training
      dockerfile: Dockerfile
    container_name: books-api
    ports:
      - "8080:80"
    volumes:
      - ./books-api-training:/srv/app
    environment:
      DB_CONNECTION: pgsql
      DB_HOST: db
      DB_PORT: 5432
      DB_DATABASE: books_app
      DB_USERNAME: books_user
      DB_PASSWORD: books_pass
    depends_on:
      - db
    networks:
      - books-network

  # React Frontend（後で追加予定）
  # frontend:
  #   ...

volumes:
  db_data:

networks:
  books-network:
```

---

## 次のステップ

環境構築が完了したら、以下のステップに進みます：

1. ✅ 環境構築完了
2. ⏭️ **マイグレーション作成（booksテーブル）**
3. ⏭️ Bookモデル作成
4. ⏭️ BookController作成（CRUD）
5. ⏭️ ルート定義
6. ⏭️ Postmanでテスト

詳細は[laravel_training_plan.md](laravel_training_plan.md)を参照してください。

---

## 重要な注意点

### volumesのマウント先

親ディレクトリの`docker-compose.yml`では以下のようにマウントしています：

```yaml
volumes:
  - ./books-api-training:/srv/app
```

これにより、`books-api-training`ディレクトリ全体が`/srv/app`にマウントされます。  
**Laravelプロジェクトは`books-api-training/app`配下に作成**してください。

### データベースの接続先

- **ホストからDB接続**: `localhost:5433` （ポート5433にマッピング）
- **コンテナ間通信**: `db:5432` （サービス名でアクセス）

Laravel（APIコンテナ）からは`db:5432`でアクセスします。

---

## まとめ

この環境構築により、以下が実現できます：

✅ PostgreSQLを使用したLaravel API環境  
✅ React連携を見据えた構成  
✅ 親ディレクトリで一元管理されたDocker環境  
✅ ポートフォリオとして見せられる構成  

次は実際のAPI実装（CRUD操作）に進みましょう！
```

#### ステップ4: Sail の初期設定
```bash
# Sail の設定ファイルを生成
php artisan sail:install
```

**選択するサービス（必要に応じて選択）:**
- `mysql` - MySQLデータベース（推奨）
- `pgsql` - PostgreSQLデータベース
- `mariadb` - MariaDBデータベース
- `redis` - キャッシュ・セッション管理
- `memcached` - キャッシュ管理
- `meilisearch` - 全文検索エンジン
- `minio` - S3互換オブジェクトストレージ
- `mailpit` - メール送信テスト
- `selenium` - ブラウザテスト

**初学者におすすめの構成:**
```
mysql, redis, mailpit
```

上記を選択すると、`docker-compose.yml` ファイルが自動生成されます。

#### ステップ5: 環境変数の設定
`.env` ファイルを確認・編集します。

```bash
# エディタで開く
code .env
# または
vim .env
```

**主な設定項目:**
```env
APP_NAME=LaravelApp
APP_ENV=local
APP_KEY=base64:... # 自動生成される
APP_DEBUG=true
APP_URL=http://localhost

DB_CONNECTION=mysql
DB_HOST=mysql  # Sailの場合はサービス名
DB_PORT=3306
DB_DATABASE=laravel
DB_USERNAME=sail
DB_PASSWORD=password
```

#### ステップ6: Dockerコンテナの起動
```bash
# コンテナをバックグラウンドで起動
./vendor/bin/sail up -d
```

**エイリアスの設定（便利）:**
```bash
# ~/.bashrc または ~/.zshrc に追加
alias sail='[ -f sail ] && sh sail || sh vendor/bin/sail'

# 設定を反映
source ~/.bashrc  # または source ~/.zshrc
```

エイリアス設定後は、`sail up -d` だけで起動できます。

#### ステップ7: 動作確認
ブラウザで以下にアクセス:
```
http://localhost
```

Laravel のウェルカムページが表示されれば成功です！

---

### 方法2: 既存のDockerfileを使った構築

より細かい制御が必要な場合は、独自の Dockerfile と docker-compose.yml を作成します。

#### 基本構成例

**ディレクトリ構造:**
```
my-laravel-app/
├── docker/
│   ├── php/
│   │   └── Dockerfile
│   └── nginx/
│       └── default.conf
├── docker-compose.yml
├── src/  # Laravelプロジェクトのソースコード
└── .env
```

**docker-compose.yml の例:**
```yaml
version: '3.8'

services:
  app:
    build:
      context: ./docker/php
      dockerfile: Dockerfile
    container_name: laravel-app
    restart: unless-stopped
    working_dir: /var/www/html
    volumes:
      - ./src:/var/www/html
    networks:
      - laravel

  nginx:
    image: nginx:alpine
    container_name: laravel-nginx
    restart: unless-stopped
    ports:
      - "8080:80"
    volumes:
      - ./src:/var/www/html
      - ./docker/nginx/default.conf:/etc/nginx/conf.d/default.conf
    networks:
      - laravel

  mysql:
    image: mysql:8.0
    container_name: laravel-mysql
    restart: unless-stopped
    environment:
      MYSQL_DATABASE: laravel
      MYSQL_ROOT_PASSWORD: root
      MYSQL_USER: laravel
      MYSQL_PASSWORD: laravel
    ports:
      - "3306:3306"
    volumes:
      - mysql-data:/var/lib/mysql
    networks:
      - laravel

networks:
  laravel:
    driver: bridge

volumes:
  mysql-data:
    driver: local
```

**docker/php/Dockerfile の例:**
```dockerfile
FROM php:8.3-fpm

# 必要なパッケージのインストール
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip

# PHP拡張機能のインストール
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# Composerのインストール
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 作業ディレクトリの設定
WORKDIR /var/www/html

# 権限設定
RUN chown -R www-data:www-data /var/www/html
```

**docker/nginx/default.conf の例:**
```nginx
server {
    listen 80;
    server_name localhost;
    root /var/www/html/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass app:9000;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

---

## 基本的なSailコマンド

### コンテナ管理
```bash
# コンテナを起動
sail up

# バックグラウンドで起動
sail up -d

# コンテナを停止
sail stop

# コンテナを停止して削除
sail down

# コンテナを再起動
sail restart
```

### Artisan コマンド
```bash
# マイグレーション実行
sail artisan migrate

# シーダー実行
sail artisan db:seed

# キャッシュクリア
sail artisan cache:clear

# コントローラー作成
sail artisan make:controller UserController

# モデル作成
sail artisan make:model User -m  # -m はマイグレーションも同時作成
```

### Composer コマンド
```bash
# パッケージインストール
sail composer install

# パッケージ追加
sail composer require package/name

# オートロード再生成
sail composer dump-autoload
```

### npm/Node.js コマンド
```bash
# npm パッケージインストール
sail npm install

# アセットビルド（開発用）
sail npm run dev

# アセットビルド（本番用）
sail npm run build

# 監視モード
sail npm run watch
```

### データベース操作
```bash
# MySQLに接続
sail mysql

# または
sail exec mysql mysql -u sail -ppassword laravel

# マイグレーション実行
sail artisan migrate

# マイグレーションをロールバック
sail artisan migrate:rollback

# データベースをリセットして再実行
sail artisan migrate:fresh

# シーダーも同時実行
sail artisan migrate:fresh --seed
```

### テスト実行
```bash
# PHPUnitテスト実行
sail test

# 特定のテストを実行
sail test --filter UserTest
```

### ログ確認
```bash
# すべてのコンテナのログを表示
sail logs

# 特定のサービスのログを表示
sail logs mysql

# リアルタイムでログを監視
sail logs -f
```

---

## トラブルシューティング

### 1. ポート競合エラー
**エラー内容:**
```
Error: Port 80 is already in use
```

**解決方法:**
`.env` ファイルでポート番号を変更します。
```env
APP_PORT=8080
FORWARD_DB_PORT=3307
```

その後、コンテナを再起動:
```bash
sail down
sail up -d
```

### 2. パーミッションエラー
**エラー内容:**
```
Permission denied: storage/logs/laravel.log
```

**解決方法:**
```bash
# コンテナ内で権限を修正
sail exec app chmod -R 777 storage bootstrap/cache
```

### 3. Composer のメモリ不足
**エラー内容:**
```
Allowed memory size exhausted
```

**解決方法:**
```bash
# メモリ制限なしで実行
sail composer install --ignore-platform-reqs --no-scripts
```

### 4. データベース接続エラー
**エラー内容:**
```
SQLSTATE[HY000] [2002] Connection refused
```

**解決方法:**
- `.env` ファイルで `DB_HOST=mysql` になっているか確認
- コンテナが起動しているか確認: `sail ps`
- コンテナを再起動: `sail restart mysql`

---

## 次のステップ

環境構築が完了したら、以下の学習を進めましょう:

1. **Laravelの基礎**
   - ルーティング
   - コントローラー
   - ビュー（Blade テンプレート）

2. **データベース操作**
   - マイグレーション
   - Eloquent ORM
   - シーダー

3. **認証機能**
   - Laravel Breeze または Jetstream

4. **API開発**
   - RESTful API
   - API認証（Sanctum）

5. **テスト**
   - PHPUnit
   - Feature テスト
   - Unit テスト

---

## 参考リンク

- [Laravel 公式ドキュメント（日本語）](https://readouble.com/laravel/)
- [Laravel Sail 公式ドキュメント](https://laravel.com/docs/sail)
- [Docker 公式ドキュメント](https://docs.docker.com/)
- [Laracasts（動画学習サイト）](https://laracasts.com/)

---

## まとめ

✅ Docker環境でのLaravel開発環境が構築できた  
✅ Laravel Sailの基本的な使い方を理解した  
✅ 主要なArtisan・Composerコマンドを把握した  
✅ トラブルシューティングの方法を学んだ

これで Laravel の学習をスタートする準備が整いました！🎉

---
---

# 📦 独自Docker構成でのLaravel環境構築（実践編）

**Laravel Sailを使わず、CakePHPと同じように自分でDocker環境を構築します。**  
この方法は実務に直結し、Docker の理解を深めることができます。

## 構成概要

```
構成内容:
- Webサーバー: Apache（php:8.3-apache）
- PHP: 8.3
- データベース: PostgreSQL 15
- ポート: 8080（app）、5433（db）
```

**CakePHP環境との違いを意識しながら進めましょう！**

---

## プロジェクト構造

```
my-laravel-app/
├── Dockerfile                    # PHP + Apache の設定
├── docker-compose.yml            # コンテナ統合管理
├── docker/
│   └── init-db.sql              # DB初期化スクリプト（オプション）
└── app/                         # Laravelプロジェクト本体（後で作成）
    ├── app/
    ├── config/
    ├── public/                  # ← CakePHPの webroot/ に相当
    ├── routes/
    ├── storage/                 # ← 権限設定が必要
    ├── bootstrap/cache/         # ← 権限設定が必要
    └── ...
```

---

## ステップ1: プロジェクトディレクトリの作成

```bash
# プロジェクトディレクトリを作成
mkdir my-laravel-app
cd my-laravel-app

# 必要なディレクトリを作成
mkdir docker
mkdir app  # 一旦空ディレクトリ（後でLaravelプロジェクトを配置）
```

---

## ステップ2: Dockerfileの作成

### CakePHPとの比較ポイント

| 項目 | CakePHP | Laravel |
|------|---------|---------|
| ドキュメントルート | `/srv/app/webroot` | `/srv/app/public` |
| 権限が必要なディレクトリ | `tmp/`, `logs/` | `storage/`, `bootstrap/cache/` |
| 必須PHP拡張 | intl, pdo_pgsql | 同じ + tokenizer, xml |

### Dockerfile の内容

**ファイル名:** `Dockerfile`

```dockerfile
# PHP 8.3 + Apache をベースイメージとして使用
FROM php:8.3-apache

# 必要なパッケージとPHP拡張機能をインストール
RUN apt-get update && apt-get install -y \
    # 基本ツール
    git \
    unzip \
    # ZIP関連
    libzip-dev \
    # PostgreSQL関連
    libpq-dev \
    # 国際化サポート（Laravelでも有用）
    libicu-dev \
    # XML処理（Laravelで必要）
    libxml2-dev \
    # 画像処理（将来的に使うかもしれない）
    libjpeg-dev \
    libfreetype6-dev \
    libpng-dev \
    # Node.js & npm（アセットビルドに必要）
    curl \
    && curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs \
    # PHP拡張機能のインストール
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
        pdo \
        pdo_pgsql \
        pgsql \
        intl \
        zip \
        opcache \
        bcmath \
        xml \
        gd \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Composerのインストール（最新版）
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Apache設定
# DocumentRootをLaravelのpublicに設定（CakePHPはwebroot）
ENV APACHE_DOCUMENT_ROOT=/srv/app/public

RUN sed -ri -e "s!/var/www/html!${APACHE_DOCUMENT_ROOT}!g" /etc/apache2/sites-available/*.conf \
    && sed -ri -e "s!/var/www/!${APACHE_DOCUMENT_ROOT}!g" \
        /etc/apache2/apache2.conf \
        /etc/apache2/conf-available/*.conf

# mod_rewriteを有効化（LaravelのURL書き換えに必要）
RUN a2enmod rewrite

# 作業ディレクトリを設定
WORKDIR /srv/app

# PHP設定（開発用）
RUN echo "display_errors = On" >> /usr/local/etc/php/conf.d/custom.ini \
    && echo "error_reporting = E_ALL" >> /usr/local/etc/php/conf.d/custom.ini \
    && echo "memory_limit = 256M" >> /usr/local/etc/php/conf.d/custom.ini \
    && echo "upload_max_filesize = 20M" >> /usr/local/etc/php/conf.d/custom.ini \
    && echo "post_max_size = 20M" >> /usr/local/etc/php/conf.d/custom.ini

# opcache設定（パフォーマンス向上）
RUN echo "opcache.enable=1" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.memory_consumption=128" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.interned_strings_buffer=8" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.max_accelerated_files=10000" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.revalidate_freq=0" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.validate_timestamps=1" >> /usr/local/etc/php/conf.d/opcache.ini

# Apacheの実行ユーザーの権限設定
RUN chown -R www-data:www-data /srv/app

# ポート80を公開
EXPOSE 80

# Apacheをフォアグラウンドで実行
CMD ["apache2-foreground"]
```

### CakePHPとの主な違い

1. **ドキュメントルート**
   ```dockerfile
   # CakePHP
   ENV APACHE_DOCUMENT_ROOT=/srv/app/webroot
   
   # Laravel
   ENV APACHE_DOCUMENT_ROOT=/srv/app/public
   ```

2. **追加したPHP拡張**
   - `bcmath`: 数値計算（Laravel標準で使用）
   - `xml`: XML処理（Laravelで必要）
   - `gd`: 画像処理（configure付き）

3. **その他はほぼ同じ構成**

---

## ステップ3: docker-compose.yml の作成

### CakePHPとの比較ポイント

| 項目 | CakePHP | Laravel |
|------|---------|---------|
| コンテナ名 | `learning-cakephp-*` | `my-laravel-*` |
| ボリュームマウント | `/srv/app` | `/srv/app` |
| 環境変数 | CakePHP用 | Laravel用 |

### docker-compose.yml の内容

**ファイル名:** `docker-compose.yml`

```yaml
version: '3.9'

services:
  # PostgreSQL データベース
  db:
    image: postgres:15-alpine
    container_name: my-laravel-db
    environment:
      POSTGRES_DB: laravel_app
      POSTGRES_USER: laravel_user
      POSTGRES_PASSWORD: laravel_pass
      POSTGRES_INITDB_ARGS: "--encoding=UTF-8"
    ports:
      - "5433:5432"
    volumes:
      - db_data:/var/lib/postgresql/data
      # 初期化スクリプト（オプション）
      # - ./docker/init-db.sql:/docker-entrypoint-initdb.d/init.sql
    networks:
      - laravel-network
    healthcheck:
      test: ["CMD-SHELL", "pg_isready -U laravel_user -d laravel_app"]
      interval: 10s
      timeout: 5s
      retries: 5

  # Laravel & Apache (Laravel アプリケーション)
  app:
    build:
      context: .
      dockerfile: Dockerfile
    image: my-laravel-app:latest
    container_name: my-laravel-app
    ports:
      - "8080:80"
    volumes:
      - ./app:/srv/app
      - composer_cache:/root/.composer
    environment:
      # Laravel用環境変数
      APP_NAME: "MyLaravelApp"
      APP_ENV: "local"
      APP_DEBUG: "true"
      APP_URL: "http://localhost:8080"
      
      # データベース接続設定
      DB_CONNECTION: pgsql
      DB_HOST: db
      DB_PORT: 5432
      DB_DATABASE: laravel_app
      DB_USERNAME: laravel_user
      DB_PASSWORD: laravel_pass
      
      # セッション・キャッシュ設定
      CACHE_DRIVER: file
      SESSION_DRIVER: file
      QUEUE_CONNECTION: sync
      
      # メール設定（開発用）
      MAIL_MAILER: log
    depends_on:
      db:
        condition: service_healthy
    networks:
      - laravel-network

volumes:
  db_data:
    driver: local
  composer_cache:
    driver: local

networks:
  laravel-network:
    driver: bridge
```

### CakePHPとの主な違い

1. **コンテナ名・DB名**
   ```yaml
   # CakePHP
   container_name: learning-cakephp-app
   POSTGRES_DB: learning_app
   
   # Laravel
   container_name: my-laravel-app
   POSTGRES_DB: laravel_app
   ```

2. **環境変数の構造**
   - CakePHP: 独自の環境変数形式
   - Laravel: Laravel標準の `.env` 形式

3. **セキュリティキー**
   - CakePHP: `SECURITY_SALT`（docker-compose.ymlに記載）
   - Laravel: `APP_KEY`（後で自動生成）

---

## ステップ4: Laravelプロジェクトの作成

### オプションA: コンテナを使ってLaravelをインストール（推奨）

```bash
# まずコンテナをビルド（Laravelはまだない状態）
docker compose build

# 一時的にappコンテナを起動してLaravelをインストール
docker compose run --rm app composer create-project laravel/laravel .

# または、特定のバージョンを指定
docker compose run --rm app composer create-project laravel/laravel . "11.*"
```

**注意:** `app/` ディレクトリに直接Laravelがインストールされます。

### オプションB: ローカルにComposerがある場合

```bash
# ローカルでLaravelをインストール
composer create-project laravel/laravel app

# または
cd app
composer create-project laravel/laravel .
```

---

## ステップ5: Laravel の初期設定

### 5-1. APP_KEYの生成

```bash
# コンテナを起動
docker compose up -d

# APP_KEYを生成（.envファイルに自動で書き込まれる）
docker compose exec app php artisan key:generate
```

### 5-2. .env ファイルの確認

`app/.env` ファイルを開いて、データベース接続情報を確認：

```env
APP_NAME=MyLaravelApp
APP_ENV=local
APP_KEY=base64:... # key:generate で自動生成
APP_DEBUG=true
APP_URL=http://localhost:8080

LOG_CHANNEL=stack
LOG_LEVEL=debug

# データベース設定（docker-compose.ymlと一致させる）
DB_CONNECTION=pgsql
DB_HOST=db
DB_PORT=5432
DB_DATABASE=laravel_app
DB_USERNAME=laravel_user
DB_PASSWORD=laravel_pass

# その他の設定はデフォルトでOK
```

**重要:** docker-compose.ymlの環境変数よりも、`.env` ファイルの設定が優先されます。

### 5-3. ストレージディレクトリの権限設定

Laravelは `storage/` と `bootstrap/cache/` に書き込み権限が必要です。

```bash
# コンテナ内で権限を設定
docker compose exec app chmod -R 777 storage bootstrap/cache

# または、コンテナ外から（WSL/Linuxの場合）
chmod -R 777 app/storage app/bootstrap/cache
```

---

## ステップ6: データベースマイグレーション

```bash
# マイグレーションを実行（初期テーブル作成）
docker compose exec app php artisan migrate
```

成功すると、以下のようなメッセージが表示されます：

```
Migration table created successfully.
Migrating: 0001_01_01_000000_create_users_table
Migrated:  0001_01_01_000000_create_users_table
Migrating: 0001_01_01_000001_create_cache_table
Migrated:  0001_01_01_000001_create_cache_table
Migrating: 0001_01_01_000002_create_jobs_table
Migrated:  0001_01_01_000002_create_jobs_table
```

---

## ステップ7: 動作確認

### ブラウザでアクセス

```
http://localhost:8080
```

Laravelのウェルカムページが表示されれば**成功**です！🎉

### トラブルシューティング

#### エラー1: 500 Internal Server Error

**原因:** ストレージの権限不足

**解決方法:**
```bash
docker compose exec app chmod -R 777 storage bootstrap/cache
```

#### エラー2: Database connection error

**原因:** `.env` の設定ミス

**確認事項:**
```bash
# DBコンテナが起動しているか確認
docker compose ps

# DBに接続できるかテスト
docker compose exec app php artisan tinker
>>> DB::connection()->getPdo();
```

#### エラー3: Composer install が失敗する

**原因:** メモリ不足

**解決方法:**
```bash
docker compose exec app composer install --no-scripts --ignore-platform-reqs
docker compose exec app composer dump-autoload
```

---

## 基本的なコマンド集

### コンテナ管理

```bash
# コンテナを起動（バックグラウンド）
docker compose up -d

# コンテナを停止
docker compose stop

# コンテナを停止して削除
docker compose down

# コンテナの状態確認
docker compose ps

# ログを確認
docker compose logs app
docker compose logs db
```

### Artisan コマンド

```bash
# マイグレーション実行
docker compose exec app php artisan migrate

# マイグレーションをロールバック
docker compose exec app php artisan migrate:rollback

# データベースをリセットして再実行
docker compose exec app php artisan migrate:fresh

# シーダーも同時実行
docker compose exec app php artisan migrate:fresh --seed

# キャッシュクリア
docker compose exec app php artisan cache:clear
docker compose exec app php artisan config:clear
docker compose exec app php artisan route:clear

# コントローラー作成
docker compose exec app php artisan make:controller UserController

# モデル作成（マイグレーションも同時作成）
docker compose exec app php artisan make:model User -m

# シーダー作成
docker compose exec app php artisan make:seeder UserSeeder
```

### Composer コマンド

```bash
# パッケージインストール
docker compose exec app composer install

# パッケージ追加
docker compose exec app composer require package/name

# パッケージ削除
docker compose exec app composer remove package/name

# オートロード再生成
docker compose exec app composer dump-autoload
```

### npm コマンド

```bash
# npm パッケージインストール
docker compose exec app npm install

# アセットビルド（開発用）
docker compose exec app npm run dev

# アセットビルド（本番用）
docker compose exec app npm run build

# 監視モード
docker compose exec app npm run watch
```

### データベース操作

```bash
# PostgreSQLに接続
docker compose exec db psql -U laravel_user -d laravel_app

# または
docker compose exec app php artisan db

# データベースのテーブル一覧を確認
docker compose exec db psql -U laravel_user -d laravel_app -c "\dt"

# SQL実行例
docker compose exec db psql -U laravel_user -d laravel_app -c "SELECT * FROM users;"
```

### Tinker（対話型コンソール）

```bash
# Tinkerを起動
docker compose exec app php artisan tinker

# 使用例（Tinker内で実行）
>>> $user = App\Models\User::first();
>>> $user->name;
>>> DB::table('users')->count();
>>> exit
```

---

## CakePHP環境との比較まとめ

### 共通点
✅ Docker + Docker Compose を使用  
✅ Apache + PHP 8.3  
✅ PostgreSQL 15  
✅ ポート: 8080, 5433  
✅ Composer でパッケージ管理  

### 違い
| 項目 | CakePHP | Laravel |
|------|---------|---------|
| ドキュメントルート | `webroot/` | `public/` |
| 設定ファイル | `config/app.php` | `.env` |
| マイグレーション | CakePHP Migrations | Laravel Migrations |
| ORM | CakePHP ORM | Eloquent ORM |
| CLI | `bin/cake` | `php artisan` |
| 権限が必要 | `tmp/`, `logs/` | `storage/`, `bootstrap/cache/` |

### 学べたこと
🎯 フレームワークが違っても、Docker構成は似ている  
🎯 ドキュメントルートの違いに注意が必要  
🎯 環境変数の扱い方の違い  
🎯 Artisan と bin/cake の違い  

---

## 次のステップ

環境構築が完了したら、以下の学習を進めましょう：

### 1. Laravelの基礎
- [ ] ルーティング（`routes/web.php`）
- [ ] コントローラー
- [ ] ビュー（Blade テンプレート）

### 2. データベース操作
- [ ] マイグレーション
- [ ] Eloquent ORM の基本
- [ ] シーダー

### 3. 認証機能
- [ ] Laravel Breeze のインストール
- [ ] ユーザー登録・ログイン

### 4. CRUD操作
- [ ] Create（登録）
- [ ] Read（一覧・詳細）
- [ ] Update（更新）
- [ ] Delete（削除）

---

## 参考資料

- [Laravel 公式ドキュメント（日本語）](https://readouble.com/laravel/11.x/ja/)
- [Laravel 英語ドキュメント](https://laravel.com/docs)
- [PostgreSQL with Laravel](https://laravel.com/docs/database#postgresql)
- [Docker 公式ドキュメント](https://docs.docker.com/)

---

## 完成！

✅ CakePHPの経験を活かしてLaravel環境を構築できた  
✅ Docker構成の理解が深まった  
✅ フレームワーク間の違いを学べた  
✅ 実務に近い環境で開発をスタートできる  

**これでLaravelの学習を本格的に始められます！** 🚀
