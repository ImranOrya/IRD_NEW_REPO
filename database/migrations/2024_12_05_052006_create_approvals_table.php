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
        Schema::create('approvals', function (Blueprint $table) {
            $table->id();
            $table->boolean("completed")->default(false);
            $table->string("request_comment")->nullable();
            $table->timestamp('request_date')->useCurrent();
            $table->string("respond_comment")->nullable();
            $table->string("respond_date")->nullable();
            $table->unsignedBigInteger('requester_id')->comment("Person ID who sends the request");
            $table->string('requester_type')->comment("The Requester Model class name");
            $table->unsignedBigInteger('responder_id')->comment("Person ID who responds the request")->nullable();
            $table->string('responder_type')->comment("The responder Model class name")->nullable();
            $table->unsignedBigInteger('notifier_type_id');
            $table->foreign('notifier_type_id')->references('id')->on('notifier_types')
                ->onUpdate('cascade')
                ->onDelete('no action');
            $table->unsignedBigInteger('approval_type_id');
            $table->foreign('approval_type_id')->references('id')->on('approval_types')
                ->onUpdate('cascade')
                ->onDelete('no action');
            $table->index(['notifier_type_id', 'approval_type_id']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('approvals');
    }
};
