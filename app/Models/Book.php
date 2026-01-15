<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Book extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     * （一括代入可能な属性の設定）
     *
     * @var array<string>
     */
    protected $fillable = [
        'title',
        'author',
        'isbn',
        'cover_image',
        'description',
        'read_status',
    ];

    /**
     * The attributes that should be cast.
     * （属性の型変換の設定）
     * 
     * NOTE: created_at, updated_at はデフォルトで Carbon インスタンスに変換されるが、
     *       明示的に指定することでコードの可読性を向上させる。
     *
     * @var array<string, string>
     */
    protected $casts = [
        'created_at' => 'datetime', // Carbonオブジェクトに変換
        'updated_at' => 'datetime', // Carbonオブジェクトに変換
    ];
}