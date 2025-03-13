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
        Schema::create('approval_documents', function (Blueprint $table) {
            $table->id();
            $table->string('actual_name', 64);
            $table->string('size', 128);
            $table->string('path');
            $table->string('type', 32);
            $table->unsignedBigInteger('check_list_id');
            $table->foreign('check_list_id')->references('id')->on('check_lists')
                ->onUpdate('cascade')
                ->onDelete('no action');
            $table->unsignedBigInteger('approval_id');
            $table->foreign('approval_id')->references('id')->on('approvals')
                ->onUpdate('cascade')
                ->onDelete('no action');
            $table->timestamps();
            $table->index(['approval_id', 'check_list_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('approval_documents');
    }
};
