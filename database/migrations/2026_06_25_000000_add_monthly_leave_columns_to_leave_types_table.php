<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leave_types', function (Blueprint $table) {
            $table->boolean('is_monthly_leave')->default(false)->after('name');
            $table->integer('monthly_leave_expiry_cycle')->nullable()->default(null)->after('total_leaves');
        });

        Schema::table('employee_specific_leave_counts', function (Blueprint $table) {
            $table->integer('monthly_leave_expiry_cycle')->nullable()->default(null)->after('total_leaves');
        });

        // Data migration: set is_monthly_leave=1 on existing "Monthly Leave" types
        DB::table('leave_types')
            ->whereRaw("LOWER(name) = 'monthly leave'")
            ->update([
                'is_monthly_leave' => true,
                'monthly_leave_expiry_cycle' => DB::raw('CASE WHEN total_leaves > 0 THEN total_leaves ELSE 3 END'),
            ]);
    }

    public function down(): void
    {
        Schema::table('leave_types', function (Blueprint $table) {
            $table->dropColumn(['is_monthly_leave', 'monthly_leave_expiry_cycle']);
        });

        Schema::table('employee_specific_leave_counts', function (Blueprint $table) {
            $table->dropColumn('monthly_leave_expiry_cycle');
        });
    }
};
