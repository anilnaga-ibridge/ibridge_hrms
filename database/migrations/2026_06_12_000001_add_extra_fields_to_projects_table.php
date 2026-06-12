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
        Schema::table('projects', function (Blueprint $table) {
            $table->string('customer')->nullable()->default(null);
            $table->boolean('calculate_progress')->default(false);
            $table->integer('progress')->default(0);
            $table->string('billing_type', 50)->default('fixed_rate');
            $table->double('total_rate')->nullable()->default(0);
            $table->double('estimated_hours')->nullable()->default(0);
            $table->text('tags')->nullable()->default(null);
            $table->boolean('send_email')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn([
                'customer',
                'calculate_progress',
                'progress',
                'billing_type',
                'total_rate',
                'estimated_hours',
                'tags',
                'send_email'
            ]);
        });
    }
};
