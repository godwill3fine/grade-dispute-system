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
        Schema::table('users', function (Blueprint $table) {
            $table->string('identifier')->nullable()->unique()->after('id');
            $table->enum('role', ['student', 'lecturer', 'staff', 'admin'])->default('student')->after('identifier');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['identifier']);
            $table->dropColumn(['identifier', 'role']);
        });
    }
};
