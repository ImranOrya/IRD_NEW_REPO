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
        Schema::create('directors', function (Blueprint $table) {
            $table->id();   
            $table->boolean('is_Active');
            $table->string('nid_no',64);
            $table->string('nid_attachment')->nullable();
            $table->unsignedBigInteger('nid_type_id');
            $table->string('profile',128);
            $table->foreign('nid_type_id')->references('id')->on('nid_types')
                ->onUpdate('cascade')
                ->onDelete('no action');

                 $table->unsignedBigInteger('gender_id');
            $table->foreign('gender_id')->references('id')->on('genders')
                ->onUpdate('cascade')
                ->onDelete('no action');

              $table->unsignedBigInteger('ngo_id');
            $table->foreign('ngo_id')->references('id')->on('ngos')
                ->onUpdate('cascade')
                ->onDelete('no action');
            
                
                 $table->unsignedBigInteger('country_id');
            $table->foreign('country_id')->references('id')->on('countries')
                ->onUpdate('cascade')
                ->onDelete('no action');

                  $table->unsignedBigInteger('address_id')->unique();
            $table->foreign('address_id')->references('id')->on('addresses')
                ->onUpdate('cascade')
                ->onDelete('no action');

                     $table->unsignedBigInteger('email_id');
            $table->foreign('email_id')->references('id')->on('emails')
                ->onUpdate('cascade')
                ->onDelete('no action');

                  $table->unsignedBigInteger('contact_id');
            $table->foreign('contact_id')->references('id')->on('contacts')
                ->onUpdate('cascade')
                ->onDelete('no action');


            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('directors');
    }
};
