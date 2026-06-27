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
        // 1. Project Sections
        Schema::create('ep_project_sections', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('project_id')->unsigned();
            $table->foreign('project_id')->references('id')->on('projects')->onDelete('cascade')->onUpdate('cascade');
            $table->string('name');
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // 2. Labels
        Schema::create('ep_labels', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('company_id')->unsigned();
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade')->onUpdate('cascade');
            $table->string('name');
            $table->string('color', 10);
            $table->timestamps();
        });

        // 3. Tasks & Subtasks (parent_id)
        Schema::create('ep_tasks', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('company_id')->unsigned();
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade')->onUpdate('cascade');
            $table->bigInteger('project_id')->unsigned()->nullable()->default(null);
            $table->foreign('project_id')->references('id')->on('projects')->onDelete('cascade')->onUpdate('cascade');
            $table->bigInteger('section_id')->unsigned()->nullable()->default(null);
            $table->foreign('section_id')->references('id')->on('ep_project_sections')->onDelete('set null')->onUpdate('cascade');
            $table->bigInteger('parent_id')->unsigned()->nullable()->default(null);
            $table->foreign('parent_id')->references('id')->on('ep_tasks')->onDelete('cascade')->onUpdate('cascade');
            $table->string('task_number', 50);
            $table->string('title');
            $table->text('description')->nullable()->default(null);
            $table->longText('rich_text_description')->nullable()->default(null);
            $table->enum('status', ['pending', 'in_progress', 'under_review', 'testing', 'completed', 'cancelled', 'on_hold'])->default('pending');
            $table->enum('priority', ['P1', 'P2', 'P3', 'P4'])->default('P3');
            $table->bigInteger('created_by')->unsigned()->nullable()->default(null);
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null')->onUpdate('cascade');
            $table->bigInteger('updated_by')->unsigned()->nullable()->default(null);
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null')->onUpdate('cascade');
            $table->date('due_date')->nullable()->default(null);
            $table->time('due_time')->nullable()->default(null);
            $table->date('start_date')->nullable()->default(null);
            $table->time('start_time')->nullable()->default(null);
            $table->decimal('estimated_hours', 8, 2)->nullable()->default(null);
            $table->decimal('actual_hours', 8, 2)->nullable()->default(null);
            $table->dateTime('completion_date')->nullable()->default(null);
            $table->boolean('is_archived')->default(false);
            $table->boolean('is_deleted')->default(false);
            $table->enum('recurrence_type', ['none', 'daily', 'weekly', 'monthly', 'yearly', 'custom'])->default('none');
            $table->text('recurrence_pattern')->nullable()->default(null);
            $table->date('next_recurrence_date')->nullable()->default(null);
            $table->timestamps();
        });

        // 6. Task Users (assignee, reviewer, watcher)
        Schema::create('ep_task_users', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('task_id')->unsigned();
            $table->foreign('task_id')->references('id')->on('ep_tasks')->onDelete('cascade')->onUpdate('cascade');
            $table->bigInteger('user_id')->unsigned();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
            $table->enum('type', ['assignee', 'reviewer', 'watcher'])->default('assignee');
            $table->bigInteger('assigned_by')->unsigned()->nullable()->default(null);
            $table->foreign('assigned_by')->references('id')->on('users')->onDelete('set null')->onUpdate('cascade');
            $table->dateTime('assigned_date')->nullable()->default(null);
            $table->dateTime('accepted_date')->nullable()->default(null);
            $table->dateTime('completed_date')->nullable()->default(null);
            $table->timestamps();
        });

        // 7. Task Labels
        Schema::create('ep_task_labels', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('task_id')->unsigned();
            $table->foreign('task_id')->references('id')->on('ep_tasks')->onDelete('cascade')->onUpdate('cascade');
            $table->bigInteger('label_id')->unsigned();
            $table->foreign('label_id')->references('id')->on('ep_labels')->onDelete('cascade')->onUpdate('cascade');
            $table->timestamps();
        });

        // 8. Checklists
        Schema::create('ep_checklists', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('task_id')->unsigned();
            $table->foreign('task_id')->references('id')->on('ep_tasks')->onDelete('cascade')->onUpdate('cascade');
            $table->string('name');
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // 9. Checklist Items
        Schema::create('ep_checklist_items', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('checklist_id')->unsigned();
            $table->foreign('checklist_id')->references('id')->on('ep_checklists')->onDelete('cascade')->onUpdate('cascade');
            $table->string('name');
            $table->boolean('is_completed')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // 10. Task Comments
        Schema::create('ep_task_comments', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('task_id')->unsigned();
            $table->foreign('task_id')->references('id')->on('ep_tasks')->onDelete('cascade')->onUpdate('cascade');
            $table->bigInteger('user_id')->unsigned();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
            $table->bigInteger('parent_id')->unsigned()->nullable()->default(null);
            $table->foreign('parent_id')->references('id')->on('ep_task_comments')->onDelete('cascade')->onUpdate('cascade');
            $table->text('comment');
            $table->boolean('is_pinned')->default(false);
            $table->timestamps();
        });

        // 11. Comment Reactions
        Schema::create('ep_comment_reactions', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('comment_id')->unsigned();
            $table->foreign('comment_id')->references('id')->on('ep_task_comments')->onDelete('cascade')->onUpdate('cascade');
            $table->bigInteger('user_id')->unsigned();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
            $table->string('emoji', 50);
            $table->timestamps();
        });

        // 12. Task Attachments
        Schema::create('ep_task_attachments', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('task_id')->unsigned();
            $table->foreign('task_id')->references('id')->on('ep_tasks')->onDelete('cascade')->onUpdate('cascade');
            $table->bigInteger('user_id')->unsigned();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
            $table->string('file_name');
            $table->bigInteger('file_size')->default(0);
            $table->string('file_type', 100)->nullable()->default(null);
            $table->string('file_path');
            $table->string('file_url');
            $table->timestamps();
        });

        // 13. Task Activities (Audit Logs)
        Schema::create('ep_task_activities', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('task_id')->unsigned();
            $table->foreign('task_id')->references('id')->on('ep_tasks')->onDelete('cascade')->onUpdate('cascade');
            $table->bigInteger('user_id')->unsigned()->nullable()->default(null);
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null')->onUpdate('cascade');
            $table->string('activity_type');
            $table->text('description');
            $table->json('properties')->nullable()->default(null);
            $table->timestamp('created_at')->useCurrent();
        });

        // 14. Reminders
        Schema::create('ep_reminders', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('task_id')->unsigned();
            $table->foreign('task_id')->references('id')->on('ep_tasks')->onDelete('cascade')->onUpdate('cascade');
            $table->bigInteger('user_id')->unsigned();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
            $table->string('reminder_type', 50); // e.g. 10m, 30m, 1h, 1d, custom
            $table->dateTime('remind_at');
            $table->boolean('is_sent')->default(false);
            $table->timestamps();
        });

        // 15. Notifications System
        Schema::create('ep_notifications', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('user_id')->unsigned();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
            $table->string('type'); // e.g. assigned, comment, reminder, overdue
            $table->json('data');
            $table->dateTime('read_at')->nullable()->default(null);
            $table->timestamps();
        });

        // 16. Time Tracking
        Schema::create('ep_time_logs', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('task_id')->unsigned();
            $table->foreign('task_id')->references('id')->on('ep_tasks')->onDelete('cascade')->onUpdate('cascade');
            $table->bigInteger('user_id')->unsigned();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
            $table->dateTime('start_time');
            $table->dateTime('end_time')->nullable()->default(null);
            $table->integer('duration_minutes')->default(0);
            $table->string('memo')->nullable()->default(null);
            $table->timestamps();
        });

        // 17. Saved Filters
        Schema::create('ep_saved_filters', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('user_id')->unsigned();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
            $table->string('name');
            $table->json('filter_criteria');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ep_saved_filters');
        Schema::dropIfExists('ep_time_logs');
        Schema::dropIfExists('ep_notifications');
        Schema::dropIfExists('ep_reminders');
        Schema::dropIfExists('ep_task_activities');
        Schema::dropIfExists('ep_task_attachments');
        Schema::dropIfExists('ep_comment_reactions');
        Schema::dropIfExists('ep_task_comments');
        Schema::dropIfExists('ep_checklist_items');
        Schema::dropIfExists('ep_checklists');
        Schema::dropIfExists('ep_task_labels');
        Schema::dropIfExists('ep_task_users');
        Schema::dropIfExists('ep_tasks');
        Schema::dropIfExists('ep_labels');
        Schema::dropIfExists('ep_project_sections');
    }
};
