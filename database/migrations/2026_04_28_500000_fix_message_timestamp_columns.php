<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Step 1: Clear zero-date values first (while still TIMESTAMP type)
        DB::statement("UPDATE messages SET pinned_at = NULL WHERE pinned_at = 0");
        DB::statement("UPDATE messages SET deleted_for_sender_at = NULL WHERE deleted_for_sender_at = 0");
        DB::statement("UPDATE messages SET deleted_for_receiver_at = NULL WHERE deleted_for_receiver_at = 0");
        DB::statement("UPDATE messages SET deleted_for_everyone_at = NULL WHERE deleted_for_everyone_at = 0");

        // Step 2: Convert TIMESTAMP → DATETIME so MySQL never auto-fills them again
        DB::statement('ALTER TABLE messages MODIFY pinned_at DATETIME NULL DEFAULT NULL');
        DB::statement('ALTER TABLE messages MODIFY deleted_for_sender_at DATETIME NULL DEFAULT NULL');
        DB::statement('ALTER TABLE messages MODIFY deleted_for_receiver_at DATETIME NULL DEFAULT NULL');
        DB::statement('ALTER TABLE messages MODIFY deleted_for_everyone_at DATETIME NULL DEFAULT NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE messages MODIFY pinned_at TIMESTAMP NULL DEFAULT NULL');
        DB::statement('ALTER TABLE messages MODIFY deleted_for_sender_at TIMESTAMP NULL DEFAULT NULL');
        DB::statement('ALTER TABLE messages MODIFY deleted_for_receiver_at TIMESTAMP NULL DEFAULT NULL');
        DB::statement('ALTER TABLE messages MODIFY deleted_for_everyone_at TIMESTAMP NULL DEFAULT NULL');
    }
};
