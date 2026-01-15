## マイグレーション基本コマンド
マイグレーションファイル作成 → マイグレーション実行してテーブルが作成する。
作成したテーブルの操作メソッドは`app/Models`内に作成する（`app/Models`配下にREADMEを配置するのでそちらを参照）


### 1. マイグレーションファイル生成
```bash
# コンテナ内で実行する場合
php artisan make:migration create_〇〇_table --create=テーブル名

# ホストから実行する場合（パーミッション問題を回避）
docker exec -u $(id -u):$(id -g) books-api php artisan make:migration create_〇〇_table --create=テーブル名

# 既存テーブルにカラム追加する場合
php artisan make:migration add_〇〇_to_テーブル名_table --table=テーブル名

# モデルとマイグレーションを同時生成
php artisan make:model ModelName -m
```

### 2. マイグレーション実行
```bash
# 未実行のマイグレーションを実行
php artisan migrate

# ホストから実行する場合
docker exec books-api php artisan migrate

# マイグレーション状態を確認
php artisan migrate:status
```

### 3. ロールバック・リセット
```bash
# 直前のバッチのマイグレーションを取り消す
php artisan migrate:rollback

# 特定のステップ数だけロールバック
php artisan migrate:rollback --step=2

# 全マイグレーションをロールバック
php artisan migrate:reset

# 全テーブル削除 → 全マイグレーション再実行（開発時便利）
php artisan migrate:fresh

# fresh + シーダー実行
php artisan migrate:fresh --seed
```

### 4. パーミッション問題が発生した場合
```bash
# マイグレーションファイルの所有権を変更
sudo chown -R takahashiren:takahashiren database/migrations/

# または個別ファイル
sudo chown takahashiren:takahashiren database/migrations/ファイル名.php
```