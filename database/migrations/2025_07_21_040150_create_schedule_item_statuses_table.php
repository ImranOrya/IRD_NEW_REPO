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
        Schema::create('schedule_item_statuses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('schedule_status_id');
            $table->foreign('schedule_status_id')->references('id')->on('schedule_statuses')
                ->onUpdate('cascade')
                ->onDelete('no action');
            $table->unsignedBigInteger('schedule_item_id');
            $table->foreign('schedule_item_id')->references('id')->on('schedule_items')
                ->onUpdate('cascade')
                ->onDelete('no action');
            $table->string('discription');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schedule_item_statuses');
    }
};
