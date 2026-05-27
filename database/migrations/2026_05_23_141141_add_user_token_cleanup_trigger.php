<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared("
        CREATE TRIGGER delete_user_tokens_after_user_delete
        AFTER DELETE ON users
        FOR EACH ROW
        DELETE FROM personal_access_tokens
        WHERE tokenable_type = 'App\\\\Models\\\\User'
          AND tokenable_id = OLD.id;
    ");
    }

    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS delete_user_tokens_after_user_delete;');
    }
};
