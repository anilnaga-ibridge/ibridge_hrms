<?php

use App\Classes\LangTrans;
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
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('company_id')->unsigned()->nullable()->default(null);
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade')->onUpdate('cascade');
            $table->string('name');
            $table->text('description')->nullable()->default(null);
            $table->string('status', 20)->default('not_started');
            $table->date('start_date');
            $table->date('deadline')->nullable()->default(null);
            $table->bigInteger('created_by')->unsigned()->nullable()->default(null);
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null')->onUpdate('cascade');
            $table->text('members')->nullable()->default(null); // JSON array of user IDs
            $table->timestamps();
        });

        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('company_id')->unsigned()->nullable()->default(null);
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade')->onUpdate('cascade');
            $table->string('name');
            $table->text('description')->nullable()->default(null);
            $table->string('status', 20)->default('not_started');
            $table->string('priority', 20)->default('medium');
            $table->date('start_date')->nullable()->default(null);
            $table->date('due_date')->nullable()->default(null);
            $table->bigInteger('project_id')->unsigned()->nullable()->default(null);
            $table->foreign('project_id')->references('id')->on('projects')->onDelete('cascade')->onUpdate('cascade');
            $table->bigInteger('created_by')->unsigned()->nullable()->default(null);
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null')->onUpdate('cascade');
            $table->text('assignees')->nullable()->default(null); // JSON array of user IDs
            $table->timestamps();
        });

        // Seed translations
        LangTrans::seedMainTranslations();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks');
        Schema::dropIfExists('projects');
    }
};
