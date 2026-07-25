<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->unsignedSmallInteger('plan_year')->nullable()->index()->after('reference');
            $table->unsignedTinyInteger('plan_month')->nullable()->index()->after('plan_year');
            $table->string('start_instruction')->nullable()->after('group_name');
            $table->string('guide_phone')->nullable()->after('language');
            $table->string('primary_flight_time')->nullable()->after('guide_phone');
            $table->string('secondary_flight_time')->nullable()->after('primary_flight_time');
            $table->foreignId('guide_id')->nullable()->after('secondary_flight_time')->constrained('guides')->nullOnDelete();
            $table->foreignId('vehicle_id')->nullable()->after('guide_id')->constrained('vehicles')->nullOnDelete();
            $table->foreignId('translator_id')->nullable()->after('vehicle_id')->constrained('translators')->nullOnDelete();
            $table->string('assignment_notes')->nullable()->after('translator_id');
            $table->text('operational_notes')->nullable()->after('assignment_notes');
            $table->string('visibility')->default('workspace')->index()->after('operational_notes');
            $table->foreignId('visible_to_user_id')->nullable()->after('visibility')->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->after('visible_to_user_id')->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->after('created_by')->constrained('users')->nullOnDelete();
            $table->timestamp('archived_at')->nullable()->index()->after('updated_by');
        });

        foreach (DB::table('bookings')->select(['id', 'arrival_date', 'created_at'])->whereNull('plan_year')->get() as $booking) {
            $date = $booking->arrival_date ?: $booking->created_at;
            if (! $date) {
                continue;
            }

            $timestamp = strtotime((string) $date);
            if ($timestamp === false) {
                continue;
            }

            DB::table('bookings')->where('id', $booking->id)->update([
                'plan_year' => (int) date('Y', $timestamp),
                'plan_month' => (int) date('n', $timestamp),
            ]);
        }

        Schema::create('filter_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('context')->index();
            $table->string('name');
            $table->json('filters');
            $table->timestamps();
            $table->unique(['user_id', 'context', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('filter_views');

        Schema::table('bookings', function (Blueprint $table) {
            $table->dropForeign(['guide_id']);
            $table->dropForeign(['vehicle_id']);
            $table->dropForeign(['translator_id']);
            $table->dropForeign(['visible_to_user_id']);
            $table->dropForeign(['created_by']);
            $table->dropForeign(['updated_by']);
            $table->dropColumn([
                'plan_year', 'plan_month', 'start_instruction', 'guide_phone',
                'primary_flight_time', 'secondary_flight_time', 'guide_id', 'vehicle_id',
                'translator_id', 'assignment_notes', 'operational_notes', 'visibility',
                'visible_to_user_id', 'created_by', 'updated_by', 'archived_at',
            ]);
        });
    }
};
