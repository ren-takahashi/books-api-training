# Laravel + React API開発トレーニング

## プロジェクト構成

### リポジトリ命名規則

**採用パターン: プロジェクト名 + 役割**

- `myproject-api` (Laravel)
- `myproject-frontend` (React)

※ GitHubなどで並べて表示されるとき、同じプロジェクトであることが一目瞭然になる

---

## ローカル開発環境の構成

### 採用方針：オプションB（親ディレクトリ + 独立リポジトリ）

**選択理由**:
- シンプルで理解しやすい
- 各リポジトリが完全に独立（ポートフォリオとして個別に見せられる）
- Git submoduleの複雑さを避ける
- 学習目的に最適

### ディレクトリ構造

```
~/projects/booksService-training/    ← 親ディレクトリ（Git管理なし）
├── docker-compose.yml               ← 全体のコンテナ管理
├── README.md                        ← プロジェクト全体の説明（任意）
├── books-api-training/              ← 独立したGitリポジトリ
│   ├── .git/
│   ├── Dockerfile
│   ├── docker-compose.yml (個別用)
│   └── app/ (Laravelファイル群)
└── books-frontend-training/         ← 独立したGitリポジトリ（後で作成）
    ├── .git/
    ├── Dockerfile
    └── src/ (Reactファイル群)
```

### セットアップ手順

```bash
# 1. 親ディレクトリに移動（すでに存在）
cd ~/projects/booksService-training

# 2. APIリポジトリをclone（すでに作成済み）
git clone https://github.com/yourusername/books-api-training.git

# 3. フロントリポジトリをclone（後で作成予定）
# git clone https://github.com/yourusername/books-frontend-training.git

# 4. docker-compose.ymlを作成（親ディレクトリ直下）
# この後の設定例を参照
```

### メリット

- ✅ コンテナ間通信が容易（同じDockerネットワーク内）
- ✅ 一度の`docker-compose up`で両方起動
- ✅ 環境変数の一元管理が可能
- ✅ 実際の本番環境に近い構成で開発できる
- ✅ 各リポジトリが独立（個別にGit管理）
- ✅ シンプルで分かりやすい構成

---

## Docker Compose設定例

**配置場所**: `~/projects/booksService-training/docker-compose.yml`

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
      POSTGRES_INITDB_ARGS: "--encoding=UTF-8"
    ports:
      - "5433:5432"
    volumes:
      - db_data:/var/lib/postgresql/data
    networks:
      - books-network
    healthcheck:
      test: ["CMD-SHELL", "pg_isready -U books_user -d books_app"]
      interval: 10s
      timeout: 5s
      retries: 5

  # Laravel API
  api:
    build:
      context: ./books-api-training
      dockerfile: Dockerfile
    container_name: books-api
    ports:
      - "8080:80"
    volumes:
      - ./books-api-training/app:/srv/app
    environment:
      APP_NAME: "BooksAPI"
      APP_ENV: "local"
      APP_DEBUG: "true"
      APP_URL: "http://localhost:8080"
      DB_CONNECTION: pgsql
      DB_HOST: db
      DB_PORT: 5432
      DB_DATABASE: books_app
      DB_USERNAME: books_user
      DB_PASSWORD: books_pass
    depends_on:
      db:
        condition: service_healthy
    networks:
      - books-network

  # React Frontend（後でコメント解除）
  # frontend:
  #   build:
  #     context: ./books-frontend-training
  #     dockerfile: Dockerfile
  #   container_name: books-frontend
  #   ports:
  #     - "3000:3000"
  #   volumes:
  #     - ./books-frontend-training:/app
  #     - /app/node_modules
  #   environment:
  #     - REACT_APP_API_URL=http://localhost:8080/api
  #   depends_on:
  #     - api
  #   networks:
  #     - books-network

volumes:
  db_data:

networks:
  books-network:
    driver: bridge
```

### 設定のポイント

1. **パス指定**: `./books-api-training` で相対パス指定
2. **コンテナ名**: プロジェクト固有の名前（`books-*`）
3. **フロントエンドはコメントアウト**: API実装後に有効化
4. **healthcheck**: DBの起動を待ってからAPIコンテナを起動

---

## コンテナ間通信

### 通信経路

- **React → Laravel（コンテナ内）**: `http://api:8000/api`
- **ブラウザ → React（ホストから）**: `http://localhost:3000`
- **ブラウザ → Laravel（ホストから）**: `http://localhost:8000`

### ポイント

- 同じDockerネットワーク内では、**サービス名で通信可能**
- 例: Reactコンテナ内から`api`という名前でLaravelにアクセス可能

---

## 本番環境との違い

### ローカル開発環境（現在の構成）

```
同一サーバー/Docker環境内
┌─────────────────────────────────┐
│  myproject-network              │
│  ┌──────────┐  ┌──────────┐    │
│  │ React    │→ │ Laravel  │    │
│  │ :3000    │  │ :8000    │    │
│  └──────────┘  └──────────┘    │
│  ┌──────────┐                   │
│  │ MySQL    │                   │
│  │ :3306    │                   │
│  └──────────┘                   │
└─────────────────────────────────┘
```

### 本番環境（一般的な構成パターン）

#### パターンA: 小規模（1サーバー構成）
```
同一サーバー内
┌─────────────────────────────────┐
│  Docker/Docker Compose          │
│  ┌──────────┐  ┌──────────┐    │
│  │ Nginx    │→ │ Laravel  │    │
│  │ (React)  │  │ API      │    │
│  │ :80/443  │  │ :8000    │    │
│  └──────────┘  └──────────┘    │
│  ┌──────────┐                   │
│  │ MySQL    │                   │
│  └──────────┘                   │
└─────────────────────────────────┘
```
- Reactはビルドして静的ファイル化
- Nginxで配信（コンテナ不要）
- ローカルに近い構成だが、最適化されている

#### パターンB: 中規模（分離構成）★推奨
```
┌──────────────┐        ┌──────────────┐
│ フロントエンド  │        │ バックエンド    │
│ サーバー/CDN   │        │ サーバー       │
├──────────────┤        ├──────────────┤
│ Nginx/CDN    │        │ Laravel      │
│ Reactビルド済 │───────→│ APIコンテナ  │
│ 静的ファイル   │ HTTPS  │              │
└──────────────┘        │ ┌──────────┐ │
                        │ │ MySQL    │ │
                        │ └──────────┘ │
                        └──────────────┘
```
- **フロントエンド**: 静的ファイルとして配信（Netlify, Vercel, CloudFront等）
- **バックエンド**: APIサーバーとして動作（AWS ECS, Cloud Run, Kubernetes等）
- **通信**: ブラウザ → API（HTTPS、パブリックURL）

#### パターンC: マイクロサービス（大規模）
```
ロードバランサー
      ↓
┌────────────────────────────────┐
│ Kubernetes / ECS / Cloud Run   │
│ ┌─────┐ ┌─────┐ ┌─────┐       │
│ │API-1│ │API-2│ │API-3│ ...   │
│ └─────┘ └─────┘ └─────┘       │
└────────────────────────────────┘
```

### 重要な違い

| 項目 | ローカル | 本番（パターンB推奨） |
|------|---------|---------------------|
| **React** | 開発サーバー（コンテナ） | ビルド済み静的ファイル |
| **通信** | サービス名（api:8000） | パブリックURL（https://api.example.com） |
| **ネットワーク** | Dockerネットワーク内 | インターネット経由（HTTPS） |
| **環境変数** | `http://api:8000/api` | `https://api.example.com/api` |
| **スケーリング** | 単一コンテナ | 複数インスタンス可能 |
| **証明書** | 不要 | SSL/TLS必須 |

### なぜ分離するのか？

1. **Reactはビルドすれば静的ファイル**
   - HTMLとJavaScriptに変換される
   - コンテナで動かし続ける必要がない
   - CDNで高速配信可能

2. **スケーリング**
   - APIとフロントを独立してスケール可能
   - APIのみ負荷に応じてインスタンス追加

3. **セキュリティ**
   - APIサーバーを直接インターネットに晒さない構成も可能
   - フロントはCDN、APIはプライベートネットワーク

4. **デプロイの独立性**
   - フロントとAPIを別々にデプロイ可能
   - 片方の更新が他方に影響しない

### 本番環境でのReact→Laravel通信

```javascript
// ローカル開発
const API_URL = 'http://api:8000/api';  // コンテナ間通信

// 本番環境
const API_URL = 'https://api.example.com/api';  // インターネット経由
```

ブラウザで動作するReactは、**常にパブリックURL**でAPIにアクセスします。

---

## Reactコンテナの役割（重要な理解）

### 本番環境ではReactコンテナは不要

- ✅ **本番**: `npm run build` → 静的ファイル（HTML, JS, CSS）をNginx/CDNで配信
- ⚠️ **開発**: `npm start` → 開発サーバーが必要（だからコンテナ使用）

### 開発時にコンテナを使う理由

```
【開発時の流れ】
┌─────────────────────┐
│ Reactコンテナ       │
│ ┌─────────────────┐ │
│ │ Node.js         │ │ ← 開発サーバー実行環境
│ │ npm start       │ │ ← これが開発サーバー起動
│ │ webpack-dev-srv │ │
│ └─────────────────┘ │
│   ↓                 │
│ - ホットリロード     │ ← コード変更を即座に反映
│ - JSX → JS変換      │ ← ブラウザが理解できる形に
│ - バンドル          │ ← 複数ファイルをまとめる
│ - エラー表示        │ ← 見やすいエラー画面
└─────────────────────┘
       ↓
ブラウザに配信（localhost:3000）

【本番時】
npm run build実行
     ↓
dist/フォルダに静的ファイル生成
  ├── index.html
  ├── main.abc123.js
  └── styles.def456.css
     ↓
Nginx/CDNに配置（コンテナ不要）
```

### 開発サーバーが必要な理由

生のReactコード（JSX）はブラウザで直接実行できない：

```jsx
// ❌ これはブラウザが理解できない
function App() {
  return <div>Hello</div>;
}
```

開発サーバーがリアルタイムで変換：

```javascript
// ✅ ブラウザが理解できる形に変換
function App() {
  return React.createElement('div', null, 'Hello');
}
```

### Docker（コンテナ）を使うメリット

#### ✅ メリット

1. **環境の統一**
   - Node.jsのバージョン統一
   - npm依存パッケージの統一
   - 「俺の環境では動く」問題を防ぐ

2. **本番に近い環境**
   - Dockerネットワークでコンテナ間通信をテスト
   - 本番デプロイ前に問題を発見しやすい

3. **セットアップが簡単**
   - `docker-compose up`だけで環境構築完了
   - 新メンバーがすぐに開発開始できる

4. **環境の分離**
   - ホストマシンを汚さない
   - 複数プロジェクトの依存関係が競合しない

#### 🤔 一人開発なら不要？

**一人作業の場合**：
```bash
# Dockerなしでも開発可能
cd laravel-project
php artisan serve  # localhost:8000

cd ../react-project
npm start          # localhost:3000
```

**結論**: 
- 一人開発なら確かにDockerなしでもOK
- ただし**練習として**Docker使うのは良い選択
- 将来的に以下の場合にメリット：
  - ✅ 他の人が参加する
  - ✅ 複数マシンで開発する（会社PC、自宅PC等）
  - ✅ 本番環境がDocker/Kubernetes等のコンテナ基盤
  - ✅ ポートフォリオとして見せる（環境構築スキルのアピール）

**あなたの状況（理解を深めるためのトレーニング）なら、Docker使用を推奨します！**

---

## 開発時の注意点

### 1. CORS設定
LaravelでReactからのリクエストを許可する必要がある

### 2. 環境変数
- Reactでは`REACT_APP_`プレフィックスが必要
- 例: `REACT_APP_API_URL=http://api:8000/api`

### 3. ホットリロード
- volumesマウントで開発体験を向上
- コード変更が即座に反映される

---

## 開発の流れ

1. ルートディレクトリで`docker-compose up`
2. Laravel: `http://localhost:8000`で起動
3. React: `http://localhost:3000`で起動
4. コード編集はホスト側で行い、コンテナ内で自動反映

---

## 今後の実装予定

### Phase 1: API基盤構築（現在）
- [x] APIリポジトリ作成（books-api-training）
- [x] 親ディレクトリ作成（booksService-training）
- [x] Docker環境構築（Dockerfile, docker-compose.yml）
- [x] Laravelプロジェクトセットアップ
- [x] データベース接続確認

### Phase 2: API実装
- [ ] マイグレーション作成（booksテーブル）
- [ ] Bookモデル作成
- [ ] BookController作成（CRUD）
- [ ] ルート定義
- [ ] Postmanでテスト

### Phase 3: 外部API連携
- [ ] GoogleBooksService作成
- [ ] BookSearchController作成
- [ ] ISBN検索エンドポイント追加

### Phase 4: フロントエンド実装
- [ ] フロントリポジトリ作成（books-frontend-training）
- [ ] Reactプロジェクトセットアップ
- [ ] Docker設定追加
- [ ] API連携実装
- [ ] CORS設定

### Phase 5: 仕上げ
- [ ] 認証機能（余裕があれば）
- [ ] テスト実装（余裕があれば）
- [ ] README整備
