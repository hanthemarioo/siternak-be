<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('daily_flock_records', function (Blueprint $table) {
            $table->id();

            $table->foreignId('coop_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('created_by')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->date('date');

            $table->decimal('feed_kg', 8, 2)->default(0);
            $table->decimal('water_l', 8, 2)->default(0);
            $table->decimal('avg_body_weight_kg', 8, 2)->default(0);

            $table->integer('mortality_count')->default(0);
            $table->integer('culling_count')->default(0);

            $table->timestamps();

            $table->unique(['coop_id', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_flock_records');
    }
};
