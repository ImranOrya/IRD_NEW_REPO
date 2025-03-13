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
        Schema::create('director_documents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('director_id');
            $table->foreign('director_id')->references('id')->on('directors')
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->unsignedBigInteger('document_id');
            $table->foreign('document_id')->references('id')->on('documents')
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
        Schema::dropIfExists('director_documents');
    }
};
