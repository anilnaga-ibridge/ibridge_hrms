<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEnterprisePremiumInfrastructure extends Migration
{
    public function up()
    {
        // -----------------------------------------------
        // 1. Feature Flags
        // -----------------------------------------------
        Schema::create('ep_feature_flags', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('company_id')->unsigned()->nullable()->default(null);
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->string('feature', 80); // ai_assistant, gantt_chart, offline_mode, calendar_sync, push_notifications, gamification
            $table->boolean('is_enabled')->default(true);
            $table->json('config')->nullable()->default(null); // feature-specific config
            $table->timestamps();

            $table->unique(['company_id', 'feature']);
            $table->index('feature');
            $table->index('company_id');
        });

        // -----------------------------------------------
        // 2. Observability Logs
        // -----------------------------------------------
        Schema::create('ep_observability_logs', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('company_id')->unsigned()->nullable()->default(null);
            $table->string('type', 30); // api_error, queue_failure, scheduler_failure, slow_query, notification_failure
            $table->string('channel', 60)->nullable()->default(null); // endpoint, job class, command name
            $table->text('message');
            $table->json('context')->nullable()->default(null);
            $table->integer('duration_ms')->nullable()->default(null); // for slow queries
            $table->string('severity', 20)->default('error'); // debug, info, warning, error, critical
            $table->string('resolved_at')->nullable()->default(null);
            $table->timestamps();

            $table->index(['company_id', 'type']);
            $table->index('type');
            $table->index('severity');
            $table->index('created_at');
        });

        // -----------------------------------------------
        // 3. Sync Queue (for offline support)
        // -----------------------------------------------
        Schema::create('ep_sync_queue', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('company_id')->unsigned();
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->bigInteger('user_id')->unsigned();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->string('operation', 20); // create, update, delete
            $table->string('resource_type', 50); // task, comment, attachment
            $table->string('client_id', 100)->nullable(); // UUID generated on client
            $table->bigInteger('resource_id')->unsigned()->nullable(); // set after server creation
            $table->json('payload'); // the data to apply
            $table->string('status', 20)->default('pending'); // pending, applied, conflict, failed
            $table->text('conflict_reason')->nullable()->default(null);
            $table->timestamp('client_created_at')->nullable();
            $table->timestamp('applied_at')->nullable()->default(null);
            $table->timestamps();

            $table->index(['company_id', 'user_id', 'status']);
            $table->index('client_id');
            $table->index('status');
        });

        // -----------------------------------------------
        // 4. Add is_system, is_inbox to projects (if not present)
        // -----------------------------------------------
        if (Schema::hasTable('projects')) {
            Schema::table('projects', function (Blueprint $table) {
                if (!Schema::hasColumn('projects', 'is_system')) {
                    $table->boolean('is_system')->default(false)->after('status');
                }
                if (!Schema::hasColumn('projects', 'is_inbox')) {
                    $table->boolean('is_inbox')->default(false)->after('is_system');
                }
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('ep_sync_queue');
        Schema::dropIfExists('ep_observability_logs');
        Schema::dropIfExists('ep_feature_flags');

        if (Schema::hasTable('projects')) {
            Schema::table('projects', function (Blueprint $table) {
                if (Schema::hasColumn('projects', 'is_inbox')) {
                    $table->dropColumn('is_inbox');
                }
                if (Schema::hasColumn('projects', 'is_system')) {
                    $table->dropColumn('is_system');
                }
            });
        }
    }
}
