<?php

namespace App\Http\Controllers;

use App\Models\Book;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class BookController extends Controller
{
    /**
     * 書籍一覧取得API
     * Booksテーブルから全件取得し、作成日時の降順で返却する
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(): JsonResponse
    {
        // Booksテーブルから全件取得（作成日時の降順）
        $books = Book::orderBy('created_at', 'desc')->get();

        // JSON形式で返却
        return response()->json($books);
    }
}