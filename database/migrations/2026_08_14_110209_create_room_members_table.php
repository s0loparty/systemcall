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
        Schema::create('room_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->constrained()->cascadeOnDelete();
            $table->foreignId('room_subroom_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('guest_id')->nullable();
            $table->string('display_name');
            $table->string('role')->default('participant')->index();
            $table->string('livekit_identity')->unique();
            $table->boolean('can_publish_audio')->default(true);
            $table->boolean('can_publish_video')->default(true);
            $table->boolean('can_publish_screen')->default(true);
            $table->timestamp('joined_at')->nullable()->index();
            $table->timestamp('left_at')->nullable()->index();
            $table->timestamps();

            $table->index(['room_id', 'role']);
            $table->index(['room_id', 'user_id']);
            $table->index(['room_id', 'guest_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('room_members');
    }
};
