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
        Schema::table('ep_user_productivity_scores', function (Blueprint $table) {
            $table->integer('rank')->nullable()->default(null)->after('final_score');
            $table->json('metrics_json')->nullable()->default(null)->after('rank');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ep_user_productivity_scores', function (Blueprint $table) {
            $table->dropColumn(['rank', 'metrics_json']);
        });
    }
};
