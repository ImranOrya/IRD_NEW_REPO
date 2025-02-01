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
        Schema::create('ngo_representer_trans', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ngo_representer_id');
            $table->foreign('ngo_representer_id')->references('id')->on('ngo_representers')->onUpdate('cascade')
                ->onDelete('no action');
            $table->string('language_name');
            $table->foreign('language_name')->references('name')->on('languages')->onUpdate('cascade')
                ->onDelete('no action');
            $table->unsignedBigInteger('job_id')->nullable();
            $table->foreign('job_id')->references('id')->on('model_jobs')
                ->onUpdate('cascade')
                ->onDelete('no action');
            $table->string('name', 64);
            $table->string('last_name', 64)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ngo_representer_trans');
    }
};
