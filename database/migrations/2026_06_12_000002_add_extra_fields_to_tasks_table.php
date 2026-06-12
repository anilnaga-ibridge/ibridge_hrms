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
        Schema::table('tasks', function (Blueprint $table) {
            $table->boolean('is_public')->default(false);
            $table->boolean('is_billable')->default(false);
            $table->string('task_file')->nullable()->default(null);
            $table->text('task_file_url')->nullable()->default(null);
            $table->double('hourly_rate')->default(0);
            $table->string('repeat_every', 50)->nullable()->default(null);
            $table->text('followers')->nullable()->default(null); // JSON array of user IDs
            $table->text('tags')->nullable()->default(null); // JSON array of tag strings
        });

        // Seed translations
        LangTrans::seedMainTranslations();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn([
                'is_public',
                'is_billable',
                'task_file',
                'task_file_url',
                'hourly_rate',
                'repeat_every',
                'followers',
                'tags'
            ]);
        });
    }
};
