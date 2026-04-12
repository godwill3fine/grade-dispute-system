<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('lecturers') && !Schema::hasColumn('lecturers', 'salutation')) {
            Schema::table('lecturers', function (Blueprint $table) {
                $table->string('salutation', 20)->nullable()->after('last_name');
            });
        }

        if (Schema::hasTable('staff') && !Schema::hasColumn('staff', 'salutation')) {
            Schema::table('staff', function (Blueprint $table) {
                $table->string('salutation', 20)->nullable()->after('last_name');
            });
        }

        if (Schema::hasColumn('lecturers', 'salutation')) {
            DB::table('lecturers')
                ->where(function ($query) {
                    $query->whereNull('salutation')
                        ->orWhere('salutation', '');
                })
                ->update(['salutation' => 'Dr.']);
        }

        if (Schema::hasColumn('staff', 'salutation')) {
            DB::table('staff')
                ->where('role', 'hod')
                ->where(function ($query) {
                    $query->whereNull('salutation')
                        ->orWhere('salutation', '');
                })
                ->update(['salutation' => 'Dr.']);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('lecturers') && Schema::hasColumn('lecturers', 'salutation')) {
            Schema::table('lecturers', function (Blueprint $table) {
                $table->dropColumn('salutation');
            });
        }

        if (Schema::hasTable('staff') && Schema::hasColumn('staff', 'salutation')) {
            Schema::table('staff', function (Blueprint $table) {
                $table->dropColumn('salutation');
            });
        }
    }
};
