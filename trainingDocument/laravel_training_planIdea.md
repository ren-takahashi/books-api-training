# 書籍管理API - Laravel + React学習プロジェクト

## プロジェクト概要

書籍の登録・管理ができるシステム。外部API（Google Books API）と連携してISBNから書籍情報を自動取得する機能も実装。

### 目的
- Laravelの基本機能の理解（CRUD、マイグレーション、モデル、コントローラー等）
- 外部API連携の実装
- React連携によるフロントエンド開発
- ポートフォリオとしての活用

---

## API仕様

### エンドポイント一覧

| メソッド | エンドポイント | 説明 |
|---------|---------------|------|
| GET | `/api/books` | 書籍一覧取得 |
| POST | `/api/books` | 書籍登録 |
| GET | `/api/books/{id}` | 書籍詳細取得 |
| PUT | `/api/books/{id}` | 書籍更新 |
| DELETE | `/api/books/{id}` | 書籍削除 |
| GET | `/api/books/search?q={isbn}` | ISBN検索（Google Books API連携） |

---

## ISBN検索機能の利用イメージ

### 基本的な使い方

```
【ユーザーの操作フロー】

1. 「書籍を追加」ボタンをクリック
   ↓
2. 登録フォームが表示される
   ┌─────────────────────────┐
   │ 書籍を登録              │
   ├─────────────────────────┤
   │ ISBN: [_____________] 🔍│ ← ここに入力
   │                         │
   │ タイトル: [___________] │
   │ 著者: [_______________] │
   │ 説明: [_______________] │
   │ ステータス: [未読 ▼]    │
   │                         │
   │ [キャンセル] [登録]      │
   └─────────────────────────┘
   ↓
3. ISBNを入力して🔍ボタンをクリック
   例: 9784797395167
   ↓
4. 自動で情報が取得されて入力される✨
   ┌─────────────────────────┐
   │ 書籍を登録              │
   ├─────────────────────────┤
   │ ISBN: [9784797395167]   │
   │                         │
   │ タイトル: [リーダブルコード] ← 自動入力
   │ 著者: [Dustin Boswell] ← 自動入力
   │ 説明: [より良いコード...] ← 自動入力
   │ ステータス: [未読 ▼]    │
   │                         │
   │ 📷 表紙画像も表示        │
   │                         │
   │ [キャンセル] [登録]      │
   └─────────────────────────┘
   ↓
5. 必要なら手動で修正可能
   ↓
6. 「登録」ボタンで保存
```

### 実際のユースケース

#### パターンA: 手元の本を登録
```
本棚から本を手に取る
  ↓
裏表紙のISBNを確認
  (例: 978-4-7741-9511-2)
  ↓
アプリのISBN欄に入力
  ↓
🔍ボタンクリック
  ↓
タイトル・著者・表紙が自動入力される
  ↓
読書ステータスを選択して「登録」
```

#### パターンB: 買いたい本をメモ
```
本屋で気になる本を発見
  ↓
スマホでアプリを開く
  ↓
ISBNをスキャン or 手入力
  (バーコードスキャナーアプリと連携も可能)
  ↓
自動で書籍情報取得
  ↓
「未読」で登録しておく
  ↓
後で購入リストとして活用
```

#### パターンC: 手動入力も可能
```
ISBNが分からない古い本の場合
  ↓
ISBN欄はスキップ
  ↓
タイトル・著者を手動で入力
  ↓
登録
```

### ISBN検索のメリット

#### 1. 入力の手間が激減
- タイトル、著者、説明文を手入力不要
- 表紙画像も自動取得
- 数字13桁入力するだけ

#### 2. 正確性向上
- タイトルの誤字がない
- 著者名が正確（特に外国人名）
- 公式データなので信頼性が高い

#### 3. ユーザー体験が良い
- 「自動で入力される」感動体験
- サクサク登録できる
- 面倒な作業が楽しくなる

### Google Books APIのレスポンス例

#### リクエスト
```
GET https://www.googleapis.com/books/v1/volumes?q=isbn:9784797395167
```

#### レスポンス（抜粋）
```json
{
  "kind": "books#volumes",
  "totalItems": 1,
  "items": [
    {
      "volumeInfo": {
        "title": "リーダブルコード",
        "subtitle": "より良いコードを書くためのシンプルで実践的なテクニック",
        "authors": [
          "Dustin Boswell",
          "Trevor Foucher"
        ],
        "publisher": "オライリージャパン",
        "publishedDate": "2012-06-23",
        "description": "コードは理解しやすくなければならない...",
        "industryIdentifiers": [
          {
            "type": "ISBN_13",
            "identifier": "9784873115658"
          }
        ],
        "imageLinks": {
          "thumbnail": "http://books.google.com/books/content?id=..."
        }
      }
    }
  ]
}
```

### React実装サンプル

```jsx
function BookForm() {
  const [formData, setFormData] = useState({
    isbn: '',
    title: '',
    author: '',
    description: '',
    cover_image: '',
    read_status: 'unread'
  });
  const [isSearching, setIsSearching] = useState(false);

  // ISBN検索
  const handleIsbnSearch = async () => {
    if (!formData.isbn) return;
    
    setIsSearching(true);
    try {
      const response = await axios.get(
        `http://api:8000/api/books/search?q=${formData.isbn}`
      );
      
      // 自動入力
      setFormData({
        ...formData,
        title: response.data.title,
        author: response.data.author,
        description: response.data.description,
        cover_image: response.data.cover_image
      });
      
      toast.success('書籍情報を取得しました！');
    } catch (error) {
      toast.error('書籍が見つかりませんでした');
    } finally {
      setIsSearching(false);
    }
  };

  return (
    <form>
      {/* ISBN入力 + 検索ボタン */}
      <div className="isbn-search">
        <input
          type="text"
          value={formData.isbn}
          onChange={(e) => setFormData({...formData, isbn: e.target.value})}
          placeholder="ISBN (例: 9784797395167)"
        />
        <button 
          type="button" 
          onClick={handleIsbnSearch}
          disabled={isSearching}
        >
          {isSearching ? '検索中...' : '🔍 検索'}
        </button>
      </div>

      {/* 表紙プレビュー */}
      {formData.cover_image && (
        <img src={formData.cover_image} alt="表紙" />
      )}

      {/* その他のフィールド */}
      <input
        type="text"
        value={formData.title}
        onChange={(e) => setFormData({...formData, title: e.target.value})}
        placeholder="タイトル"
        required
      />
      
      <input
        type="text"
        value={formData.author}
        onChange={(e) => setFormData({...formData, author: e.target.value})}
        placeholder="著者"
      />
      
      <textarea
        value={formData.description}
        onChange={(e) => setFormData({...formData, description: e.target.value})}
        placeholder="説明"
      />
      
      <select
        value={formData.read_status}
        onChange={(e) => setFormData({...formData, read_status: e.target.value})}
      >
        <option value="unread">未読</option>
        <option value="reading">読書中</option>
        <option value="completed">読了</option>
      </select>
      
      <button type="submit">登録</button>
    </form>
  );
}
```

### 実装のポイント

#### バリデーション
```php
// Laravel側でISBN形式チェック
$request->validate([
    'q' => 'required|string|regex:/^(97[89])?\d{9}[\dX]$/'
]);
```

#### エラーハンドリング
- **ISBNが見つからない** → 「書籍が見つかりませんでした」
- **API障害** → 「現在検索できません。手動で入力してください」
- **不正なISBN** → 「正しいISBN形式で入力してください」

#### UX改善
- 検索中はボタンを無効化（連打防止）
- ローディングアニメーション表示
- 成功時は✨アニメーション
- 失敗時も優しいメッセージ

---

## データベース設計

### テーブル: books

| カラム名 | 型 | NULL | 説明 |
|---------|---|------|------|
| id | BIGINT | NO | 主キー（自動採番） |
| title | VARCHAR(255) | NO | 書籍名 |
| author | VARCHAR(255) | YES | 著者 |
| isbn | VARCHAR(13) | YES | ISBN（10桁または13桁） |
| cover_image | TEXT | YES | 表紙画像URL |
| description | TEXT | YES | 書籍の説明 |
| read_status | ENUM | NO | 読書ステータス（unread/reading/completed） |
| created_at | TIMESTAMP | NO | 作成日時 |
| updated_at | TIMESTAMP | NO | 更新日時 |

### マイグレーション例

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('books', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('author')->nullable();
            $table->string('isbn', 13)->nullable()->unique();
            $table->text('cover_image')->nullable();
            $table->text('description')->nullable();
            $table->enum('read_status', ['unread', 'reading', 'completed'])->default('unread');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('books');
    }
};
```

---

## Laravel実装構成

### ディレクトリ構造

```
app/
├── Http/
│   └── Controllers/
│       ├── BookController.php        # CRUD操作
│       └── BookSearchController.php  # 外部API連携
├── Models/
│   └── Book.php                      # Bookモデル
└── Services/
    └── GoogleBooksService.php        # Google Books API連携サービス

database/
└── migrations/
    └── 2026_01_06_create_books_table.php

routes/
└── api.php                           # APIルート定義
```

### モデル実装例

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Book extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'author',
        'isbn',
        'cover_image',
        'description',
        'read_status',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
```

### コントローラー実装例（CRUD）

```php
<?php

namespace App\Http\Controllers;

use App\Models\Book;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class BookController extends Controller
{
    // 一覧取得
    public function index(): JsonResponse
    {
        $books = Book::orderBy('created_at', 'desc')->get();
        return response()->json($books);
    }

    // 詳細取得
    public function show(int $id): JsonResponse
    {
        $book = Book::findOrFail($id);
        return response()->json($book);
    }

    // 新規作成
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'author' => 'nullable|string|max:255',
            'isbn' => 'nullable|string|max:13|unique:books',
            'cover_image' => 'nullable|url',
            'description' => 'nullable|string',
            'read_status' => 'required|in:unread,reading,completed',
        ]);

        $book = Book::create($validated);
        return response()->json($book, 201);
    }

    // 更新
    public function update(Request $request, int $id): JsonResponse
    {
        $book = Book::findOrFail($id);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'author' => 'nullable|string|max:255',
            'isbn' => 'nullable|string|max:13|unique:books,isbn,' . $id,
            'cover_image' => 'nullable|url',
            'description' => 'nullable|string',
            'read_status' => 'required|in:unread,reading,completed',
        ]);

        $book->update($validated);
        return response()->json($book);
    }

    // 削除
    public function destroy(int $id): JsonResponse
    {
        $book = Book::findOrFail($id);
        $book->delete();
        return response()->json(null, 204);
    }
}
```

### 外部API連携サービス

```php
<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GoogleBooksService
{
    private const API_URL = 'https://www.googleapis.com/books/v1/volumes';

    public function searchByIsbn(string $isbn): ?array
    {
        try {
            $response = Http::get(self::API_URL, [
                'q' => "isbn:{$isbn}",
            ]);

            if ($response->successful() && $response->json('totalItems') > 0) {
                $book = $response->json('items')[0];
                return $this->formatBookData($book);
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Google Books API Error: ' . $e->getMessage());
            return null;
        }
    }

    private function formatBookData(array $book): array
    {
        $volumeInfo = $book['volumeInfo'] ?? [];

        return [
            'title' => $volumeInfo['title'] ?? '',
            'author' => implode(', ', $volumeInfo['authors'] ?? []),
            'isbn' => $this->extractIsbn($volumeInfo),
            'cover_image' => $volumeInfo['imageLinks']['thumbnail'] ?? null,
            'description' => $volumeInfo['description'] ?? null,
        ];
    }

    private function extractIsbn(array $volumeInfo): ?string
    {
        $identifiers = $volumeInfo['industryIdentifiers'] ?? [];
        
        foreach ($identifiers as $identifier) {
            if (in_array($identifier['type'], ['ISBN_13', 'ISBN_10'])) {
                return $identifier['identifier'];
            }
        }

        return null;
    }
}
```

### 検索コントローラー

```php
<?php

namespace App\Http\Controllers;

use App\Services\GoogleBooksService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookSearchController extends Controller
{
    public function __construct(
        private GoogleBooksService $googleBooksService
    ) {}

    public function searchByIsbn(Request $request): JsonResponse
    {
        $request->validate([
            'q' => 'required|string',
        ]);

        $isbn = $request->query('q');
        $bookData = $this->googleBooksService->searchByIsbn($isbn);

        if (!$bookData) {
            return response()->json([
                'message' => '書籍が見つかりませんでした'
            ], 404);
        }

        return response()->json($bookData);
    }
}
```

### ルート定義（api.php）

```php
<?php

use App\Http\Controllers\BookController;
use App\Http\Controllers\BookSearchController;
use Illuminate\Support\Facades\Route;

// CRUD
Route::apiResource('books', BookController::class);

// ISBN検索
Route::get('books/search', [BookSearchController::class, 'searchByIsbn']);
```

---

## 学べる技術要素

### Laravel
- ✅ **マイグレーション**: データベーステーブル作成
- ✅ **モデル**: Eloquent ORMの使い方
- ✅ **コントローラー**: リクエスト処理、レスポンス返却
- ✅ **バリデーション**: 入力値検証
- ✅ **ルーティング**: APIエンドポイント定義
- ✅ **HTTPクライアント**: 外部API連携
- ✅ **サービスクラス**: ビジネスロジックの分離
- ✅ **エラーハンドリング**: 例外処理

### その他
- ✅ **RESTful API設計**: CRUD操作の標準的な設計
- ✅ **JSON処理**: データの整形と返却
- ✅ **外部API連携**: Google Books APIの利用

---

## React側の実装イメージ

### 主な機能

1. **書籍一覧画面**
   - カード形式で表示
   - 読書ステータスでフィルター
   - 表紙画像表示

2. **書籍登録フォーム**
   - ISBN入力欄
   - 「検索」ボタンでGoogle Books APIから自動取得
   - 手動入力も可能

3. **書籍詳細モーダル**
   - 書籍情報表示
   - 編集・削除ボタン

4. **読書ステータス切替**
   - 未読/読書中/読了の切替

### React技術要素

- ✅ useState, useEffect（状態管理、副作用）
- ✅ axios / fetch（API通信）
- ✅ React Router（ページ遷移）
- ✅ フォーム処理
- ✅ モーダル実装
- ✅ 条件付きレンダリング

---

## 実装ステップ（段階的アプローチ）

### Phase 1: 基本CRUD（最優先）
1. ✅ Laravelプロジェクトセットアップ
2. ✅ マイグレーション作成・実行
3. ✅ Bookモデル作成
4. ✅ BookController作成（CRUD）
5. ✅ ルート定義
6. ✅ Postmanでテスト

### Phase 2: バリデーション追加
1. ✅ リクエストバリデーション実装
2. ✅ エラーレスポンス整形

### Phase 3: 外部API連携
1. ✅ GoogleBooksService作成
2. ✅ BookSearchController作成
3. ✅ 検索エンドポイント追加
4. ✅ テスト

### Phase 4: React実装
1. ✅ Reactプロジェクトセットアップ
2. ✅ 一覧画面実装
3. ✅ 登録フォーム実装
4. ✅ ISBN検索機能実装
5. ✅ 編集・削除機能実装
6. ✅ CORS設定

---

## 拡張アイデア（余裕があれば）

### 機能拡張
- [ ] ページネーション
- [ ] 検索・フィルター機能強化（著者名、タイトル検索）
- [ ] 読書メモ機能（感想を記録）
- [ ] お気に入り/評価機能
- [ ] カテゴリ/タグ機能

### 技術拡張
- [ ] 認証機能（Laravel Sanctum）
- [ ] ユニットテスト（PHPUnit）
- [ ] キャッシュ実装（Redis）
- [ ] 画像アップロード機能
- [ ] GraphQL化

---

## 参考リソース

### 外部API
- **Google Books API**: https://developers.google.com/books/docs/v1/using?hl=ja
  - 無料
  - 認証不要（基本的な使用）
  - ISBNでの検索が可能

### Laravel公式ドキュメント
- マイグレーション: https://laravel.com/docs/11.x/migrations
- Eloquent ORM: https://laravel.com/docs/11.x/eloquent
- HTTPクライアント: https://laravel.com/docs/11.x/http-client
- バリデーション: https://laravel.com/docs/11.x/validation

---

## 開発のポイント

### CORS設定を忘れずに
Reactからのリクエストを許可するため、Laravelで`cors`設定が必要。

```php
// config/cors.php
'paths' => ['api/*'],
'allowed_origins' => ['http://localhost:3000'],
```

### 環境変数の活用
```env
# .env
GOOGLE_BOOKS_API_KEY=your_api_key  # 必要に応じて
```

### エラーハンドリング
- 404エラー（書籍が見つからない）
- バリデーションエラー（入力値不正）
- 外部APIエラー（Google Books API障害）

これらを適切にハンドリングし、フロントエンドに分かりやすいエラーメッセージを返す。

---

## まとめ

この書籍管理APIプロジェクトで、Laravel/Reactの基礎から実践的なスキルまで幅広く学べます。

**学習効果**:
- 🎯 Laravelの基本機能の理解
- 🎯 RESTful API設計
- 🎯 外部API連携
- 🎯 React連携
- 🎯 ポートフォリオ作品

**次のステップ**: まずはPhase 1の基本CRUDから始めましょう！