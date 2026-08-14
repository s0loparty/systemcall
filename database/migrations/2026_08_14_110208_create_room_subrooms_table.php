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
        Schema::create('room_subrooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->constrained()->cascadeOnDelete();
            $table->string('public_id', 32)->unique();
            $table->string('name');
            $table->string('livekit_room_name')->unique();
            $table->unsignedSmallInteger('position')->default(0);
            $table->boolean('is_default')->default(false)->index();
            $table->timestamps();

            $table->unique(['room_id', 'name']);
            $table->index(['room_id', 'position']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('room_subrooms');
    }
};
