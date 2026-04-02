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
        Schema::create('disputes', function (Blueprint $table) {
            $table->id();
        
            $table->string('student_id');
            $table->enum('type', ['unit','general']);
        
            $table->string('unit_code')->nullable();
        
            $table->string('assigned_to');
            $table->string('assigned_role');
        
            $table->string('title');
            $table->text('description');
        
            $table->enum('status', ['pending','under_review','resolved','escalated'])->default('pending');
        
            $table->timestamps();
        
            $table->foreign('student_id')->references('reg_number')->on('students')->cascadeOnDelete();
            $table->foreign('unit_code')->references('unit_code')->on('units')->nullOnDelete();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('disputes');
    }
};
