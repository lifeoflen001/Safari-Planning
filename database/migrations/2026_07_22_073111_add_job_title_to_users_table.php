<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // job_title and role are owned by the preceding profile migration.
    }

    public function down(): void
    {
        // Intentionally left blank; these columns belong to the profile migration.
    }
};
