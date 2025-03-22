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
        Schema::create('ngos', function (Blueprint $table) {
            $table->id();
            $table->string('abbr', 16);
            $table->string('registration_no', 64);
            $table->string('date_of_establishment')->nullable();
            $table->unsignedBigInteger('role_id');
            $table->foreign('role_id')->references('id')->on('roles')
                ->onUpdate('cascade')
                ->onDelete('no action');
            $table->unsignedBigInteger('ngo_type_id');
            $table->foreign('ngo_type_id')->references('id')->on('ngo_types')
                ->onUpdate('cascade')
                ->onDelete('no action');
            $table->unsignedBigInteger('address_id');
            $table->foreign('address_id')->references('id')->on('addresses')
                ->onUpdate('cascade')
                ->onDelete('no action');
            $table->string('moe_registration_no')->unique()->nullable()->comment('Ministry of Economy register NO');
            $table->unsignedBigInteger('place_of_establishment')->nullable();
            $table->foreign('place_of_establishment')->references('id')->on('countries')
                ->onUpdate('cascade')
                ->onDelete('no action');
            $table->unsignedBigInteger('email_id')->nullable();
            $table->foreign('email_id')->references('id')->on('emails')
                ->onUpdate('cascade')
                ->onDelete('set null');
            $table->unsignedBigInteger('contact_id')->nullable();
            $table->foreign('contact_id')->references('id')->on('contacts')
                ->onUpdate('cascade')
                ->onDelete('set null');
            $table->string('username')->unique();
            $table->string('password');
            $table->string('profile')->nullable();
            $table->boolean('is_editable')->default(true);
            $table->boolean('is_logged_in')->default(false);
            $table->foreignId('user_id')
                ->constrained()
                ->onUpdate('cascade')
                ->onDelete('no action');
            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ngos');
    }
};
