<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->integer('sort_order')->default(0)->after('tags');
            $table->timestamp('completed_at')->nullable()->default(null)->after('sort_order');
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn(['sort_order', 'completed_at']);
        });
    }
};
