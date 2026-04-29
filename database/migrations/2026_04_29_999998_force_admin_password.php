<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Delete and recreate admin to ensure clean state
        DB::table('users')->where('email', 'admin@admin.com')->delete();

        DB::table('users')->insert([
            'name'              => 'Admin',
            'username'          => 'admin',
            'email'             => 'admin@admin.com',
            'password'          => '$2y$12$EIhHqNU9gtZWRNGEw2MYr.puSNgktXjjlCOygWW8nSNtt67bf.VCm',
            'is_admin'          => 1,
            'email_verified_at' => now(),
            'created_at'        => now(),
            'updated_at'        => now(),
        ]);
    }

    public function down(): void {}
};
