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
        // 1. Task Dependencies
        Schema::create('ep_task_dependencies', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('company_id')->unsigned();
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade')->onUpdate('cascade');
            $table->bigInteger('project_id')->unsigned()->nullable()->default(null);
            $table->foreign('project_id')->references('id')->on('projects')->onDelete('cascade')->onUpdate('cascade');
            $table->bigInteger('task_id')->unsigned();
            $table->foreign('task_id')->references('id')->on('ep_tasks')->onDelete('cascade')->onUpdate('cascade');
            $table->bigInteger('depends_on_task_id')->unsigned();
            $table->foreign('depends_on_task_id')->references('id')->on('ep_tasks')->onDelete('cascade')->onUpdate('cascade');
            $table->string('dependency_type', 30)->default('finish_to_start'); // finish_to_start, start_to_start, finish_to_finish, start_to_finish
            $table->integer('lag_days')->default(0);
            $table->bigInteger('created_by')->unsigned()->nullable()->default(null);
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null')->onUpdate('cascade');
            $table->timestamps();

            // Indexes
            $table->index('task_id');
            $table->index('depends_on_task_id');
            $table->index('project_id');
            $table->index('company_id');
        });

        // 2. Automation Rules
        Schema::create('ep_automation_rules', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('company_id')->unsigned();
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade')->onUpdate('cascade');
            $table->bigInteger('project_id')->unsigned()->nullable()->default(null);
            $table->foreign('project_id')->references('id')->on('projects')->onDelete('cascade')->onUpdate('cascade');
            $table->string('name');
            $table->text('description')->nullable()->default(null);
            $table->string('event_name'); // task_created, task_completed, task_overdue, task_assigned, task_status_changed, project_completed
            $table->json('conditions')->nullable()->default(null);
            $table->json('actions')->nullable()->default(null);
            $table->boolean('is_active')->default(true);
            $table->bigInteger('created_by')->unsigned()->nullable()->default(null);
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null')->onUpdate('cascade');
            $table->timestamps();
        });

        // 3. Task Templates
        Schema::create('ep_task_templates', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('company_id')->unsigned();
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade')->onUpdate('cascade');
            $table->string('name');
            $table->text('description')->nullable()->default(null);
            $table->string('category')->nullable()->default(null);
            $table->boolean('is_global')->default(false);
            $table->bigInteger('created_by')->unsigned()->nullable()->default(null);
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null')->onUpdate('cascade');
            $table->timestamps();
        });

        // 4. Task Template Items
        Schema::create('ep_task_template_items', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('template_id')->unsigned();
            $table->foreign('template_id')->references('id')->on('ep_task_templates')->onDelete('cascade')->onUpdate('cascade');
            $table->string('title');
            $table->text('description')->nullable()->default(null);
            $table->string('priority', 10)->default('P3');
            $table->decimal('estimated_hours', 8, 2)->nullable()->default(null);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // 5. Saved Views
        Schema::create('ep_saved_views', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('company_id')->unsigned();
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade')->onUpdate('cascade');
            $table->bigInteger('user_id')->unsigned();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
            $table->string('name');
            $table->string('icon', 50)->nullable()->default(null);
            $table->json('filters')->nullable()->default(null);
            $table->string('grouping', 50)->nullable()->default(null);
            $table->string('sorting', 50)->nullable()->default(null);
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        // 6. Notifications Queue
        Schema::create('ep_notifications_queue', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('company_id')->unsigned();
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade')->onUpdate('cascade');
            $table->bigInteger('user_id')->unsigned();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
            $table->string('notification_type');
            $table->json('data');
            $table->string('status', 20)->default('pending'); // pending, sent, failed
            $table->integer('attempts')->default(0);
            $table->dateTime('last_attempt_at')->nullable()->default(null);
            $table->dateTime('scheduled_at')->nullable()->default(null);
            $table->timestamps();
        });

        // 7. Task Metrics
        Schema::create('ep_task_metrics', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('company_id')->unsigned();
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade')->onUpdate('cascade');
            $table->date('date');
            $table->bigInteger('project_id')->unsigned()->nullable()->default(null);
            $table->foreign('project_id')->references('id')->on('projects')->onDelete('cascade')->onUpdate('cascade');
            $table->integer('total_tasks')->default(0);
            $table->integer('completed_tasks')->default(0);
            $table->integer('pending_tasks')->default(0);
            $table->integer('overdue_tasks')->default(0);
            $table->integer('time_spent_minutes')->default(0);
            $table->timestamps();
        });

        // 8. Project Metrics
        Schema::create('ep_project_metrics', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('company_id')->unsigned();
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade')->onUpdate('cascade');
            $table->bigInteger('project_id')->unsigned();
            $table->foreign('project_id')->references('id')->on('projects')->onDelete('cascade')->onUpdate('cascade');
            $table->date('date');
            $table->decimal('completion_percentage', 5, 2)->default(0.00);
            $table->decimal('burn_rate', 5, 2)->default(0.00);
            $table->decimal('allocated_hours', 8, 2)->default(0.00);
            $table->decimal('remaining_hours', 8, 2)->default(0.00);
            $table->timestamps();
        });

        // 9. User Productivity Scores
        Schema::create('ep_user_productivity_scores', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('company_id')->unsigned();
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade')->onUpdate('cascade');
            $table->bigInteger('user_id')->unsigned();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
            $table->integer('year');
            $table->integer('month');
            $table->decimal('completion_percentage', 5, 2)->default(0.00);
            $table->decimal('on_time_percentage', 5, 2)->default(0.00);
            $table->decimal('time_log_accuracy', 5, 2)->default(0.00);
            $table->decimal('collaboration_score', 5, 2)->default(0.00);
            $table->decimal('reopened_penalty', 5, 2)->default(0.00);
            $table->decimal('final_score', 5, 2)->default(0.00);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ep_user_productivity_scores');
        Schema::dropIfExists('ep_project_metrics');
        Schema::dropIfExists('ep_task_metrics');
        Schema::dropIfExists('ep_notifications_queue');
        Schema::dropIfExists('ep_saved_views');
        Schema::dropIfExists('ep_task_template_items');
        Schema::dropIfExists('ep_task_templates');
        Schema::dropIfExists('ep_automation_rules');
        Schema::dropIfExists('ep_task_dependencies');
    }
};
