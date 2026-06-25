<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('employee_monthly_leave', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('company_id')->unsigned()->nullable()->default(null);
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade')->onUpdate('cascade');
            $table->bigInteger('employee_id')->unsigned();
            $table->foreign('employee_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
            $table->date('credited_date');
            $table->enum('status', ['ACTIVE', 'USED', 'EXPIRED'])->default('ACTIVE');
            $table->date('used_date')->nullable()->default(null);
            $table->bigInteger('used_in_leave_id')->unsigned()->nullable()->default(null);
            $table->foreign('used_in_leave_id')->references('id')->on('leaves')->onDelete('set null')->onUpdate('cascade');
            $table->timestamps();

            // Index for query performance optimization
            $table->index(['company_id', 'employee_id', 'credited_date'], 'emp_monthly_leave_comp_emp_date_idx');

            // Indexes for query performance optimization (using short names for indices as well)
            $table->index(['company_id', 'employee_id'], 'emp_monthly_leave_comp_emp_idx');
            $table->index(['employee_id', 'status'], 'emp_monthly_leave_emp_status_idx');
            $table->index('credited_date', 'emp_monthly_leave_credit_date_idx');
        });

        // Insert "Monthly Leave" leave type for existing companies
        $companies = DB::table('companies')->get();
        foreach ($companies as $company) {
            $adminId = $company->admin_id;

            if (!$adminId) {
                $admin = DB::table('users')
                    ->join('role_user', 'users.id', '=', 'role_user.user_id')
                    ->join('roles', 'role_user.role_id', '=', 'roles.id')
                    ->where('users.company_id', $company->id)
                    ->where('users.status', 'active')
                    ->where('roles.name', 'admin')
                    ->select('users.id')
                    ->first();

                $adminId = $admin ? $admin->id : null;
            }

            // Check if already exists
            $exists = DB::table('leave_types')
                ->where('company_id', $company->id)
                ->where('name', 'Monthly Leave')
                ->exists();

            if (!$exists) {
                DB::table('leave_types')->insert([
                    'company_id' => $company->id,
                    'created_by' => $adminId,
                    'name' => 'Monthly Leave',
                    'is_paid' => 1,
                    'total_leaves' => 0,
                    'is_monthly_leave' => true,
                    'monthly_leave_expiry_cycle' => 3,
                    'max_leaves_per_month' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // Insert menu translation
        $langs = DB::table('langs')->get();
        foreach ($langs as $lang) {
            $exists = DB::table('translations')
                ->where('lang_id', $lang->id)
                ->where('group', 'menu')
                ->where('key', 'monthly_leaves')
                ->exists();

            if (!$exists) {
                DB::table('translations')->insert([
                    'lang_id' => $lang->id,
                    'group' => 'menu',
                    'key' => 'monthly_leaves',
                    'value' => 'Monthly Leaves',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        $leaveTranslations = [
            'active_leaves' => 'Active Leaves',
            'used_leaves' => 'Used Leaves',
            'expired_leaves' => 'Expired Leaves',
            'next_credit_date' => 'Next Credit Date',
            'current_month_cycle_status' => 'Current {0}-Month Cycle Status',
            'recently_expired' => 'Recently Expired',
            'active' => 'Active',
            'used' => 'Used',
            'expired' => 'Expired',
            'all_records' => 'All Records',
            'showing_all_employees_hint' => 'Showing all employees. Click a row to view detailed monthly leave history.',
            'month' => 'Month',
            'leave_period' => 'Leave Period',
            'employee' => 'Employee',
            'available' => 'Available',
            'view_history' => 'View History',
            'monthly_leave_balance' => 'Monthly Leave Balance',
            'recently_expired_leaves' => 'Recently Expired Leaves',
            'last_used_on' => 'Last Used On',
            'total_expired' => 'Total Expired',
            'hide_full_history' => 'Hide Full History',
            'view_full_history' => 'View Full History',
            'used_on' => 'Used on',
            'not_credited' => 'Not Credited',
        ];

        // Insert leave translations
        foreach ($langs as $lang) {
            foreach ($leaveTranslations as $key => $val) {
                $exists = DB::table('translations')
                    ->where('lang_id', $lang->id)
                    ->where('group', 'leave')
                    ->where('key', $key)
                    ->exists();

                if (!$exists) {
                    DB::table('translations')->insert([
                        'lang_id' => $lang->id,
                        'group' => 'leave',
                        'key' => $key,
                        'value' => $val,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_monthly_leave');

        // Note: We avoid deleting default leave types on rollback to prevent breaking foreign keys on existing leaves.
        DB::table('translations')->where('key', 'monthly_leaves')->delete();
        DB::table('translations')
            ->where('group', 'leave')
            ->whereIn('key', [
                'active_leaves',
                'used_leaves',
                'expired_leaves',
                'next_credit_date',
                'current_month_cycle_status',
                'recently_expired',
                'active',
                'used',
                'expired',
                'all_records',
                'showing_all_employees_hint',
                'month',
                'leave_period',
                'employee',
                'available',
                'view_history',
                'monthly_leave_balance',
                'recently_expired_leaves',
                'last_used_on',
                'total_expired',
                'hide_full_history',
                'view_full_history',
                'used_on',
                'not_credited',
            ])
            ->delete();
    }
};
