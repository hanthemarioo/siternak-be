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
        Schema::create('daily_egg_productions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('daily_flock_record_id')
                ->constrained()
                ->cascadeOnDelete();

            // Marketable (layak jual)
            $table->integer('marketable_eggs_count')->default(0);
            $table->decimal('marketable_eggs_weight_kg', 10, 3)->default(0);

            // Sorted / telur warna putih
            $table->integer('sorted_eggs_count')->default(0);
            $table->decimal('sorted_eggs_weight_kg', 10, 3)->default(0);

            // Busuk (spoiled)
            $table->integer('spoiled_eggs_count')->default(0);
            $table->decimal('spoiled_eggs_weight_kg', 10, 3)->default(0);

            // Rusak / pecah
            $table->integer('broken_eggs_count')->default(0);
            $table->decimal('broken_eggs_weight_kg', 10, 3)->default(0);


            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_egg_productions');
    }
};
