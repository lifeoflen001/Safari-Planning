<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('country')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('contact_phone')->nullable();
            $table->string('status')->default('active')->index();
            $table->timestamps();
        });

        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type')->index();
            $table->string('city')->nullable();
            $table->string('contact_name')->nullable();
            $table->string('contact_phone')->nullable();
            $table->string('status')->default('active')->index();
            $table->timestamps();
        });

        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 12)->nullable()->index();
            $table->string('type')->index();
            $table->string('region')->nullable();
            $table->timestamps();
        });

        Schema::create('guides', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('license_number')->nullable()->index();
            $table->string('status')->default('available')->index();
            $table->json('languages')->nullable();
            $table->date('license_expiry')->nullable();
            $table->timestamps();
        });

        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->string('registration_number')->unique();
            $table->string('type')->default('4x4 safari vehicle');
            $table->unsignedSmallInteger('capacity');
            $table->string('status')->default('available')->index();
            $table->date('last_service_at')->nullable();
            $table->date('next_service_at')->nullable();
            $table->timestamps();
        });

        Schema::create('translators', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->json('languages')->nullable();
            $table->string('status')->default('available')->index();
            $table->timestamps();
        });

        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->string('group_name');
            $table->unsignedSmallInteger('pax');
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->string('country')->nullable()->index();
            $table->string('language')->nullable()->index();
            $table->date('arrival_date')->nullable()->index();
            $table->date('departure_date')->nullable()->index();
            $table->string('status')->default('draft')->index();
            $table->string('payment_status')->default('pending')->index();
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['arrival_date', 'departure_date']);
        });

        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type')->index();
            $table->string('title');
            $table->foreignId('start_location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->foreignId('end_location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->dateTime('starts_at')->index();
            $table->dateTime('ends_at')->index();
            $table->string('status')->default('planned')->index();
            $table->string('flight_number')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['starts_at', 'ends_at']);
        });

        Schema::create('activity_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_id')->constrained()->cascadeOnDelete();
            $table->foreignId('guide_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('vehicle_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('translator_id')->nullable()->constrained()->nullOnDelete();
            $table->string('assignment_status')->default('assigned');
            $table->timestamps();
            $table->unique(['activity_id', 'guide_id', 'vehicle_id', 'translator_id'], 'activity_assignment_unique');
        });

        Schema::create('flights', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->foreignId('activity_id')->nullable()->constrained()->nullOnDelete();
            $table->string('flight_number');
            $table->string('direction')->index();
            $table->string('airline')->nullable();
            $table->string('origin')->nullable();
            $table->string('destination')->nullable();
            $table->dateTime('scheduled_at')->index();
            $table->dateTime('actual_at')->nullable();
            $table->string('status')->default('scheduled')->index();
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3)->default('EUR');
            $table->date('due_date')->nullable()->index();
            $table->date('paid_at')->nullable();
            $table->string('method')->nullable();
            $table->string('status')->default('pending')->index();
            $table->string('reference')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->string('path');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action');
            $table->string('auditable_type');
            $table->unsignedBigInteger('auditable_id');
            $table->json('changes')->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->timestamps();
            $table->index(['auditable_type', 'auditable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('documents');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('flights');
        Schema::dropIfExists('activity_assignments');
        Schema::dropIfExists('activities');
        Schema::dropIfExists('bookings');
        Schema::dropIfExists('translators');
        Schema::dropIfExists('vehicles');
        Schema::dropIfExists('guides');
        Schema::dropIfExists('locations');
        Schema::dropIfExists('suppliers');
        Schema::dropIfExists('companies');
    }
};
