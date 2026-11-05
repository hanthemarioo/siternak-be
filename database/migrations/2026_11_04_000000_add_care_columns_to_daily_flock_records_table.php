<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('daily_flock_records', function (Blueprint $table) {
            $table->decimal('temperature_c', 4, 1)->nullable()->after('culling_count');
            $table->decimal('uniformity_percent', 5, 2)->nullable()->after('temperature_c');
            $table->string('feed_name')->nullable()->after('uniformity_percent');
            $table->string('medicine_vitamin')->nullable()->after('feed_name');
            $table->string('vaccine')->nullable()->after('medicine_vitamin');
            $table->text('notes')->nullable()->after('vaccine');
        });
    }

    public function down(): void
    {
        Schema::table('daily_flock_records', function (Blueprint $table) {
            $table->dropColumn([
                'temperature_c',
                'uniformity_percent',
                'feed_name',
                'medicine_vitamin',
                'vaccine',
                'notes',
            ]);
        });
    }
};

