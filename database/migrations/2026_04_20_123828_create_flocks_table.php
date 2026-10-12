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
        Schema::create('flocks', function (Blueprint $table) {
            $table->id();

            $table->foreignId('farm_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('name');
            $table->string('house_code');
            $table->unsignedInteger('age_in_day');
            $table->string('strain');
            $table->date('start_date');

            $table->date('closed_at')->nullable();
            $table->enum('closing_reason', [
                'harvested',
                'depopulated',
                'disease',
                'sold',
                'terminated',
                'other'
            ])->nullable();

            $table->enum('status', [
                'active',
                'closed',
            ])->default('active');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('flocks');
    }
};
