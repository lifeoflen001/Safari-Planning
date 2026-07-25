<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->json('access_permissions')->nullable()->after('role');
            $table->json('access_modules')->nullable()->after('access_permissions');
            $table->string('record_scope')->default('workspace')->after('access_modules');
            $table->boolean('must_change_password')->default(false)->after('password');
            $table->timestamp('temporary_password_expires_at')->nullable()->after('must_change_password');
            $table->unsignedTinyInteger('temporary_password_attempts')->default(0)->after('temporary_password_expires_at');
            $table->timestamp('temporary_password_locked_at')->nullable()->after('temporary_password_attempts');
            $table->timestamp('temporary_password_used_at')->nullable()->after('temporary_password_locked_at');
            $table->timestamp('last_login_at')->nullable()->after('last_active_at');
            $table->unsignedInteger('session_version')->default(1)->after('last_login_at');
            $table->index(['record_scope', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['record_scope', 'is_active']);
            $table->dropColumn([
                'access_permissions', 'access_modules', 'record_scope', 'must_change_password',
                'temporary_password_expires_at', 'temporary_password_attempts',
                'temporary_password_locked_at', 'temporary_password_used_at',
                'last_login_at', 'session_version',
            ]);
        });
    }
};
