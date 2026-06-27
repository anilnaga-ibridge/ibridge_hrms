<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_specific_leave_counts', function (Blueprint $table) {
            if (!Schema::hasColumn('employee_specific_leave_counts', 'max_leaves_per_month')) {
                $table->integer('max_leaves_per_month')->nullable()->after('monthly_leave_expiry_cycle');
            }
        });
    }

    public function down(): void
    {
        Schema::table('employee_specific_leave_counts', function (Blueprint $table) {
            $table->dropColumn('max_leaves_per_month');
        });
    }
};
