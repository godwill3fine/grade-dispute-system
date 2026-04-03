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
        Schema::table('dispute_logs', function (Blueprint $table) {
            if (! Schema::hasColumn('dispute_logs', 'old_status')) {
                $table->string('old_status')->nullable();
            }

            if (! Schema::hasColumn('dispute_logs', 'new_status')) {
                $table->string('new_status')->nullable();
            }

            if (! Schema::hasColumn('dispute_logs', 'comment')) {
                $table->text('comment')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dispute_logs', function (Blueprint $table) {
            if (Schema::hasColumn('dispute_logs', 'old_status')) {
                $table->dropColumn('old_status');
            }

            if (Schema::hasColumn('dispute_logs', 'new_status')) {
                $table->dropColumn('new_status');
            }

            if (Schema::hasColumn('dispute_logs', 'comment')) {
                $table->dropColumn('comment');
            }
        });
    }
};
