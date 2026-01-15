## Controllerファイル生成

ホスト側で以下のコマンドを実行する場合
```bash
# Controllerファイルを作成
docker exec -it books-api sh -c "cd app && php artisan make:controller BookController"

# 【推奨】ユーザーIDを指定してControllerファイル生成（パーミッション問題が発生しない）
docker exec -u $(id -u):$(id -g) books-api php artisan make:controller BookController
```
コンテナ内で直接実行する場合
```bash
cd app
php artisan make:controller BookController
```
