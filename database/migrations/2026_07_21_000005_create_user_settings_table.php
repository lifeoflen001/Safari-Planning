<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('date_format')->default('DD MMM YYYY');
            $table->string('time_format')->default('24h');
            $table->string('week_starts_on')->default('monday');
            $table->string('default_calendar_view')->default('week');
            $table->boolean('email_notifications')->default(true);
            $table->boolean('conflict_alerts')->default(true);
            $table->boolean('payment_reminders')->default(true);
            $table->boolean('assignment_updates')->default(true);
            $table->string('theme')->default('light');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_settings');
    }
};
