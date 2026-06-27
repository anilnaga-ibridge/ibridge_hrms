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
        Schema::table('ep_tasks', function (Blueprint $table) {
            // Single indexes
            $table->index('status', 'ep_tasks_status_idx');
            $table->index('priority', 'ep_tasks_priority_idx');
            $table->index('due_date', 'ep_tasks_due_date_idx');
            $table->index('created_at', 'ep_tasks_created_at_idx');
            
            // Composite indexes
            $table->index(['company_id', 'project_id'], 'ep_tasks_company_project_idx');
            $table->index(['company_id', 'status'], 'ep_tasks_company_status_idx');
            $table->index(['project_id', 'status'], 'ep_tasks_project_status_idx');
            $table->index(['project_id', 'due_date'], 'ep_tasks_project_due_date_idx');
        });

        Schema::table('ep_task_users', function (Blueprint $table) {
            $table->index('user_id', 'ep_task_users_user_idx');
            $table->index(['task_id', 'user_id', 'type'], 'ep_task_users_task_user_type_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ep_tasks', function (Blueprint $table) {
            $table->dropIndex('ep_tasks_status_idx');
            $table->dropIndex('ep_tasks_priority_idx');
            $table->dropIndex('ep_tasks_due_date_idx');
            $table->dropIndex('ep_tasks_created_at_idx');
            $table->dropIndex('ep_tasks_company_project_idx');
            $table->dropIndex('ep_tasks_company_status_idx');
            $table->dropIndex('ep_tasks_project_status_idx');
            $table->dropIndex('ep_tasks_project_due_date_idx');
        });

        Schema::table('ep_task_users', function (Blueprint $table) {
            $table->dropIndex('ep_task_users_user_idx');
            $table->dropIndex('ep_task_users_task_user_type_idx');
        });
    }
};
