<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')
            ->where('email', 'admin@admin.com')
            ->update([
                'password' => Hash::make('adminpassword'),
                'is_admin' => true,
            ]);
    }

    public function down(): void {}
};
