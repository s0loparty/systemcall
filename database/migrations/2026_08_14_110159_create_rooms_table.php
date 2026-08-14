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
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->string('public_id', 32)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('visibility')->default('private')->index();
            $table->string('status')->default('active')->index();
            $table->string('password_hash')->nullable();
            $table->boolean('waiting_room_enabled')->default(false)->index();
            $table->unsignedSmallInteger('max_participants')->nullable();
            $table->timestamp('ended_at')->nullable()->index();
            $table->timestamps();

            $table->index(['owner_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};
