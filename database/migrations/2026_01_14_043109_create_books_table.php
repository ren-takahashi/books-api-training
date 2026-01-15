<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('books', function (Blueprint $table) {
            // ID（主キー）
            $table->id();                                    // BIGINT、PRIMARY KEY
            // 書籍タイトル
            $table->string('title');                         // VARCHAR(255)、NOT NULL
            // 著者名
            $table->string('author')->nullable();            // VARCHAR(255)、NULL可
            // ISBN番号
            $table->string('isbn', 13)->nullable()->unique(); // VARCHAR(13)、NULL可、UNIQUE
            // 表紙画像URL
            $table->text('cover_image')->nullable();         // TEXT、NULL可
            // 書籍説明
            $table->text('description')->nullable();         // TEXT、NULL可
            // 読書ステータス
            $table->enum('read_status', ['unread', 'reading', 'completed'])
                  ->default('unread');                       // ENUM DEFAULT 'unread'
            // 作成日時、更新日時
            $table->timestamps();                            // created_atカラム, updated_atカラム
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('books');
    }
};