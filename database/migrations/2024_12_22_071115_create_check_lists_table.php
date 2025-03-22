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
        Schema::create('check_lists', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('check_list_type_id');
            $table->foreign('check_list_type_id')->references('id')->on('check_list_types')
                ->onUpdate('cascade')
                ->onDelete('no action');
            $table->string('acceptable_extensions');
            $table->string('acceptable_mimes')->comment('Stores frontend validation value');
            $table->string('accept')->comment('Frontend input accept parameter');
            $table->boolean('active')->default(true)->comment("Used when the checklist is no longer needed");
            $table->integer('file_size')->default('512')->comment('file size in KB');
            $table->string('description', 128)->nullable();
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users')
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
        Schema::dropIfExists('check_lists');
    }
};
