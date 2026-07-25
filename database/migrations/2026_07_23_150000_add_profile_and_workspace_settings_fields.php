<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('middle_name')->nullable()->after('name');
            $table->string('preferred_name')->nullable()->after('middle_name');
            $table->string('alternative_phone')->nullable()->after('phone');
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->string('logo_url')->nullable()->after('name');
            $table->string('website')->nullable()->after('logo_url');
            $table->text('description')->nullable()->after('website');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['logo_url', 'website', 'description']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['middle_name', 'preferred_name', 'alternative_phone']);
        });
    }
};
