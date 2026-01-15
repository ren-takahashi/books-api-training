## Modelファイル生成

ホスト側で以下のコマンドを実行する場合
```bash
# Modelファイルを作成
docker exec -it books-api sh -c "cd app && php artisan make:model Book"

# 【推奨】ユーザーIDを指定してModelファイル生成（パーミッション問題が発生しない）
docker exec -u $(id -u):$(id -g) books-api php artisan make:model Book
```
コンテナ内で直接実行する場合
```bash
cd app
php artisan make:model Book
```

## CakePHPのModelとの違いについて（自分用メモ）
### 役割と配置

| フレームワーク | クラス名 | ファイルパス | 継承元 | 役割 |
|--------------|---------|------------|--------|------|
| **CakePHP** | `BooksTable` | `src/Model/Table/BooksTable.php` | `Table` | テーブル操作 |
| **Laravel** | `Book`（単数形） | `app/Models/Book.php` | `Model` | テーブル操作 |

### 重要なポイント

1. **命名規則の違い**
   - **CakePHP**: `BooksTable`（複数形 + Table）
   - **Laravel**: `Book`（単数形のみ）
   - Laravelは`Book`モデルから自動的に`books`テーブルを推測（複数形に自動変換）

2. **ORMの違い**
   - **CakePHP**: Query Builder（`$this->find()->where(...)`）
   - **Laravel**: Eloquent ORM（`Book::where(...)->get()`）

3. **データ取得方法の比較**

   **CakePHP:**
   ```php
   // BooksTable.php
   public function findActive() {
       return $this->find()
           ->where(['status' => 'active'])
           ->toArray();
   }
   
   // Controllerで使用
   $books = $this->Books->findActive();
   ```

   **Laravel:**
   ```php
   // Book.php (Model)
   public function scopeActive($query) {
       return $query->where('status', 'active');
   }
   
   // Controllerで使用（Modelを直接呼び出し）
   $books = Book::active()->get();
   ```

4. **重要な違い**
   - **CakePHP**: TableクラスはControllerで`$this->Books`としてロードして使用
   - **Laravel**: Modelは直接`Book::find()`のように静的メソッドで呼び出し
   - **LaravelのModelがCakePHPのTableクラスの役割を果たす**

### データ操作の比較

| 操作 | CakePHP | Laravel |
|------|---------|---------|
| 全件取得 | `$this->Books->find()->toArray()` | `Book::all()` |
| 条件付き取得 | `$this->Books->find()->where(['id' => 1])->first()` | `Book::where('id', 1)->first()` |
| ID検索 | `$this->Books->get($id)` | `Book::find($id)` |
| 新規作成 | `$entity = $this->Books->newEntity($data);`<br>`$this->Books->save($entity);` | `Book::create($data)` |
| 更新 | `$book = $this->Books->get($id);`<br>`$book = $this->Books->patchEntity($book, $data);`<br>`$this->Books->save($book);` | `$book = Book::find($id);`<br>`$book->update($data);` |
| 削除 | `$book = $this->Books->get($id);`<br>`$this->Books->delete($book);` | `Book::destroy($id);` |

### まとめ

- **マイグレーション** (`database/migrations/`) → データベーステーブルの構造を定義・作成
- **Model** (`app/Models/`) → 作成されたテーブルを操作するクラス（CakePHPのTableクラスに相当）
- LaravelのModelはEloquent ORMという強力なORMで、直接DB操作を行う
- CakePHPよりもシンプルで直感的な記述が可能
