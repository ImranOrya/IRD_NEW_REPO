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
        Schema::create('ngo_trans', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ngo_id');
            $table->foreign('ngo_id')->references('id')->on('ngos')
                ->onUpdate('cascade')
                ->onDelete('no action');
            $table->string('language_name');
            $table->foreign('language_name')->references('name')->on('languages')
                ->onUpdate('cascade')
                ->onDelete('no action');
            $table->string('name', 128);
            $table->longText('vision')->nullable();
            $table->longText('mission')->nullable();
            $table->longText('general_objective')->nullable();
            $table->longText('objective')->nullable();
            $table->longText('introduction')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ngo_trans');
    }
};
