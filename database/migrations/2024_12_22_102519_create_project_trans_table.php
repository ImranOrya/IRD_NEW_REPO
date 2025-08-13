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
        Schema::create('project_trans', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('project_id');
            $table->foreign('project_id')->references('id')->on('projects')
                ->onUpdate('cascade')
                ->onDelete('no action');
            $table->string('language_name');
            $table->foreign('language_name')->references('name')->on('languages')->onUpdate('cascade')
                ->onDelete('cascade');
            $table->text('preamble');
            $table->text('health_experience');
            $table->text('goals');
            $table->text('objectives');
            $table->text('expected_outcome');
            $table->text('project_structure');
            $table->text('expected_impact');
            $table->text('subject');
            $table->text('main_activities');
            $table->text('introduction');
            $table->text('operational_plan');
            $table->text('mission');
            $table->text('vission');
            $table->text('terminologies');
            $table->text('organization_senior_manangement');
            $table->string('name');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_trans');
    }
};
