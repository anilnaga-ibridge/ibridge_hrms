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
        // Alter projects table to add Inbox/System flags
        Schema::table('projects', function (Blueprint $table) {
            $table->boolean('is_system')->default(false)->after('status');
            $table->boolean('is_inbox')->default(false)->after('is_system');
        });

        // 1. Recurring Tasks
        Schema::create('ep_recurring_tasks', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('company_id')->unsigned();
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade')->onUpdate('cascade');
            $table->bigInteger('task_id')->unsigned();
            $table->foreign('task_id')->references('id')->on('ep_tasks')->onDelete('cascade')->onUpdate('cascade');
            $table->string('frequency', 30); // daily, weekdays, weekly, monthly, yearly, custom
            $table->integer('interval_value')->default(1);
            $table->text('week_days')->nullable()->default(null); // comma separated or json e.g. "monday,tuesday"
            $table->integer('month_day')->nullable()->default(null);
            $table->integer('year_day')->nullable()->default(null);
            $table->string('end_type', 30)->default('never'); // never, date, occurrences
            $table->date('end_date')->nullable()->default(null);
            $table->integer('occurrences')->nullable()->default(null);
            $table->integer('completed_occurrences')->default(0);
            $table->dateTime('next_run_at')->nullable()->default(null);
            $table->boolean('is_active')->default(true);
            $table->bigInteger('created_by')->unsigned()->nullable()->default(null);
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null')->onUpdate('cascade');
            $table->timestamps();

            $table->index('company_id');
            $table->index('task_id');
            $table->index('next_run_at');
        });

        // 2. Favorites
        Schema::create('ep_favorites', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('company_id')->unsigned();
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade')->onUpdate('cascade');
            $table->bigInteger('user_id')->unsigned();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
            $table->string('type', 30); // project, label, filter, view
            $table->string('reference_id', 100); // hash / xid of referenced element
            $table->timestamps();

            $table->index(['company_id', 'user_id']);
        });

        // 3. Notification Preferences
        Schema::create('ep_notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('company_id')->unsigned();
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade')->onUpdate('cascade');
            $table->bigInteger('user_id')->unsigned();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
            $table->boolean('browser')->default(true);
            $table->boolean('email')->default(true);
            $table->boolean('push')->default(true);
            $table->boolean('digest')->default(false);
            $table->timestamps();

            $table->unique(['company_id', 'user_id']);
        });

        // 4. Undo Actions
        Schema::create('ep_undo_actions', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('user_id')->unsigned();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
            $table->string('action_type', 50); // delete, complete, move, assignment, status_change
            $table->json('payload'); // reverse/undo state values
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->index('user_id');
        });

        // 5. Calendar Integrations
        Schema::create('ep_calendar_integrations', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('company_id')->unsigned();
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade')->onUpdate('cascade');
            $table->bigInteger('user_id')->unsigned();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
            $table->string('provider', 30); // google, outlook, ics
            $table->text('access_token')->nullable()->default(null);
            $table->text('refresh_token')->nullable()->default(null);
            $table->boolean('sync_enabled')->default(true);
            $table->dateTime('last_synced_at')->nullable()->default(null);
            $table->timestamps();

            $table->index(['company_id', 'user_id']);
        });

        // 6. Badges
        Schema::create('ep_badges', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable()->default(null);
            $table->string('icon')->nullable()->default(null);
            $table->string('requirement_type', 50); // tasks_completed, streak_days, custom
            $table->integer('requirement_value');
            $table->timestamps();
        });

        // 7. User Streaks
        Schema::create('ep_user_streaks', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('company_id')->unsigned();
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade')->onUpdate('cascade');
            $table->bigInteger('user_id')->unsigned();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
            $table->integer('daily_streak')->default(0);
            $table->integer('weekly_streak')->default(0);
            $table->date('last_completed_date')->nullable()->default(null);
            $table->timestamps();

            $table->unique(['company_id', 'user_id']);
        });

        // 8. User Achievements
        Schema::create('ep_user_achievements', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('company_id')->unsigned();
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade')->onUpdate('cascade');
            $table->bigInteger('user_id')->unsigned();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
            $table->bigInteger('badge_id')->unsigned();
            $table->foreign('badge_id')->references('id')->on('ep_badges')->onDelete('cascade')->onUpdate('cascade');
            $table->dateTime('unlocked_at');
            $table->timestamps();

            $table->index(['company_id', 'user_id']);
        });

        // 9. Goals
        Schema::create('ep_goals', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('company_id')->unsigned();
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade')->onUpdate('cascade');
            $table->bigInteger('user_id')->unsigned();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
            $table->string('name');
            $table->string('goal_type', 30); // tasks_completed, bugs_closed, time_logged
            $table->integer('target');
            $table->integer('current_progress')->default(0);
            $table->date('start_date');
            $table->date('end_date');
            $table->string('status', 20)->default('active'); // active, completed, failed
            $table->timestamps();

            $table->index(['company_id', 'user_id']);
        });

        // 10. Pomodoro Sessions
        Schema::create('ep_pomodoro_sessions', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('user_id')->unsigned();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
            $table->bigInteger('task_id')->unsigned()->nullable()->default(null);
            $table->foreign('task_id')->references('id')->on('ep_tasks')->onDelete('set null')->onUpdate('cascade');
            $table->dateTime('start_at');
            $table->dateTime('end_at')->nullable()->default(null);
            $table->integer('duration'); // duration in minutes
            $table->boolean('completed')->default(false);
            $table->timestamps();

            $table->index('user_id');
        });

        // 11. Workspace Settings
        Schema::create('ep_workspace_settings', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('company_id')->unsigned();
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade')->onUpdate('cascade');
            $table->text('working_days')->nullable()->default(null); // e.g. "1,2,3,4,5"
            $table->string('default_priority', 10)->default('P3');
            $table->string('default_reminder', 20)->default('30m');
            $table->time('business_hours_start')->default('09:00:00');
            $table->time('business_hours_end')->default('18:00:00');
            $table->string('timezone', 100)->default('UTC');
            $table->string('task_number_format', 50)->default('TASK-');
            $table->timestamps();

            $table->unique('company_id');
        });

        // 12. User Preferences
        Schema::create('ep_user_preferences', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('company_id')->unsigned();
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade')->onUpdate('cascade');
            $table->bigInteger('user_id')->unsigned();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
            $table->string('theme', 30)->default('light');
            $table->boolean('keyboard_shortcuts')->default(true);
            $table->string('default_view', 50)->default('inbox');
            $table->string('time_format', 10)->default('12h');
            $table->string('date_format', 30)->default('YYYY-MM-DD');
            $table->timestamps();

            $table->unique(['company_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ep_user_preferences');
        Schema::dropIfExists('ep_workspace_settings');
        Schema::dropIfExists('ep_pomodoro_sessions');
        Schema::dropIfExists('ep_goals');
        Schema::dropIfExists('ep_user_achievements');
        Schema::dropIfExists('ep_user_streaks');
        Schema::dropIfExists('ep_badges');
        Schema::dropIfExists('ep_calendar_integrations');
        Schema::dropIfExists('ep_undo_actions');
        Schema::dropIfExists('ep_notification_preferences');
        Schema::dropIfExists('ep_favorites');
        Schema::dropIfExists('ep_recurring_tasks');

        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn(['is_system', 'is_inbox']);
        });
    }
};
