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
        Schema::create('unit_lecturers', function (Blueprint $table) {
            $table->id();
            $table->string('unit_code');
            $table->string('lecturer_id');
            $table->string('course');
            $table->timestamps();

            $table->foreign('unit_code')
                ->references('unit_code')
                ->on('units')
                ->cascadeOnDelete();

            $table->foreign('lecturer_id')
                ->references('lecturer_id')
                ->on('lecturers')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('unit_lecturers');
    }
};
