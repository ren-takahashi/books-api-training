<?php

use App\Http\Controllers\BookController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// デフォルトで用意されているユーザー情報取得API（エンドポイント（middlewareでSanctum（認証機能）使用））
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// --- 以下トレーニング用に実装するAPIのルーティング ---
// 書籍一覧取得API（認証不要）
Route::get('/books', [BookController::class, 'index']);
