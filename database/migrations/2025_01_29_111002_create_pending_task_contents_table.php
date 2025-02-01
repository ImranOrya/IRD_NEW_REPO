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
        Schema::create('pending_task_contents', function (Blueprint $table) {
            $table->id();
            $table->integer('step');
            $table->longText('content');
            $table->unsignedBigInteger('pending_task_id');
            $table->foreign('pending_task_id')->references('id')
                ->on('pending_tasks')
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pending_task_contents');
    }
};
