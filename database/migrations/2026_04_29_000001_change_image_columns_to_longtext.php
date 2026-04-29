<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->longText('avatar')->nullable()->change();
            $table->longText('cover_photo')->nullable()->change();
        });

        Schema::table('posts', function (Blueprint $table) {
            $table->longText('image')->nullable()->change();
        });

        Schema::table('stories', function (Blueprint $table) {
            $table->longText('image')->change();
        });

        Schema::table('messages', function (Blueprint $table) {
            $table->longText('image')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('avatar')->nullable()->change();
            $table->string('cover_photo')->nullable()->change();
        });

        Schema::table('posts', function (Blueprint $table) {
            $table->string('image')->nullable()->change();
        });

        Schema::table('stories', function (Blueprint $table) {
            $table->string('image')->change();
        });

        Schema::table('messages', function (Blueprint $table) {
            $table->string('image')->nullable()->change();
        });
    }
};
