<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rotational_teams', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('name');
            $table->integer('rotation_order')->default(0);
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
        });

        Schema::create('rotational_team_members', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('rotational_team_id');
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('user_id');
            $table->timestamps();

            $table->foreign('rotational_team_id')->references('id')->on('rotational_teams')->onDelete('cascade');
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            $table->unique(['rotational_team_id', 'user_id']);
        });

        Schema::create('rotational_schedules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('rotational_team_id');
            $table->date('date');
            $table->boolean('is_weekoff')->default(false);
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('rotational_team_id')->references('id')->on('rotational_teams')->onDelete('cascade');

            $table->unique(['rotational_team_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rotational_schedules');
        Schema::dropIfExists('rotational_team_members');
        Schema::dropIfExists('rotational_teams');
    }
};
