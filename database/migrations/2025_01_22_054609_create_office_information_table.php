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
        Schema::create('office_information', function (Blueprint $table) {
            $table->id();
            $table->string('address_english', 512);
            $table->string('address_farsi', 512);
            $table->string('address_pashto', 512);
            $table->string('contact', 32)->unique();
            $table->string('email', 64)->unique();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('office_information');
    }
};
