<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('viewer')->index();
            $table->string('job_title')->nullable();
            $table->string('phone')->nullable();
            $table->string('timezone')->default('Africa/Dar_es_Salaam');
            $table->string('avatar_path')->nullable();
            $table->boolean('is_active')->default(true)->index();
        });

        Schema::create('booking_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('body');
            $table->timestamps();
            $table->index(['booking_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_comments');
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'job_title', 'phone', 'timezone', 'avatar_path', 'is_active']);
        });
    }
};
