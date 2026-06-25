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
        Schema::table('companies', function (Blueprint $table) {
            $table->string('office_latitude', 50)->nullable()->after('capture_location');
            $table->string('office_longitude', 50)->nullable()->after('office_latitude');
            $table->unsignedInteger('office_location_radius')->default(100)->after('office_longitude');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['office_latitude', 'office_longitude', 'office_location_radius']);
        });
    }
};
