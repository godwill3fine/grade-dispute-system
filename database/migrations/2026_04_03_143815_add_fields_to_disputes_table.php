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
        Schema::table('disputes', function (Blueprint $table) {
            if (! Schema::hasColumn('disputes', 'ticket_number')) {
                $table->string('ticket_number')->unique()->after('id');
            }

            if (! Schema::hasColumn('disputes', 'assignment_name')) {
                $table->string('assignment_name')->nullable();
            }

            if (! Schema::hasColumn('disputes', 'priority')) {
                $table->enum('priority', ['low', 'medium', 'high'])->default('medium');
            }

            if (! Schema::hasColumn('disputes', 'escalation_level')) {
                $table->integer('escalation_level')->default(0);
            }

            if (! Schema::hasColumn('disputes', 'escalation_reason')) {
                $table->string('escalation_reason')->nullable();
            }

            if (! Schema::hasColumn('disputes', 'escalated_at')) {
                $table->timestamp('escalated_at')->nullable();
            }

            if (! Schema::hasColumn('disputes', 'resolved_at')) {
                $table->timestamp('resolved_at')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('disputes', function (Blueprint $table) {
            if (Schema::hasColumn('disputes', 'ticket_number')) {
                $table->dropColumn('ticket_number');
            }

            if (Schema::hasColumn('disputes', 'assignment_name')) {
                $table->dropColumn('assignment_name');
            }

            if (Schema::hasColumn('disputes', 'priority')) {
                $table->dropColumn('priority');
            }

            if (Schema::hasColumn('disputes', 'escalation_level')) {
                $table->dropColumn('escalation_level');
            }

            if (Schema::hasColumn('disputes', 'escalation_reason')) {
                $table->dropColumn('escalation_reason');
            }

            if (Schema::hasColumn('disputes', 'escalated_at')) {
                $table->dropColumn('escalated_at');
            }

            if (Schema::hasColumn('disputes', 'resolved_at')) {
                $table->dropColumn('resolved_at');
            }
        });
    }
};
