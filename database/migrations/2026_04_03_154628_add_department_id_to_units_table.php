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
        Schema::table('units', function (Blueprint $table) {
            if (! Schema::hasColumn('units', 'department_id')) {
                $table->foreignId('department_id')
                    ->nullable()
                    ->after('unit_name')
                    ->constrained('departments')
                    ->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('units', function (Blueprint $table) {
            if (Schema::hasColumn('units', 'department_id')) {
                $table->dropForeign(['department_id']);
                $table->dropColumn('department_id');
            }
        });
    }
};
