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
            $table->string('office_location')->nullable()->after('status');
            $table->boolean('require_password_reset')->default(false)->after('office_location');
            $table->boolean('manual_approval_required')->default(false)->after('require_password_reset');
            $table->timestamp('welcome_email_sent_at')->nullable()->after('manual_approval_required');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'office_location',
                'require_password_reset',
                'manual_approval_required',
                'welcome_email_sent_at',
            ]);
        });
    }
};
