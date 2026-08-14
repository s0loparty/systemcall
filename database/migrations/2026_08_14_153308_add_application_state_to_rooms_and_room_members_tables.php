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
        Schema::table('room_members', function (Blueprint $table) {
            $table->timestamp('hand_raised_at')->nullable()->after('can_publish_screen')->index();
        });

        Schema::table('rooms', function (Blueprint $table) {
            $table->foreignId('global_pinned_room_member_id')
                ->nullable()
                ->after('max_participants')
                ->constrained('room_members')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->dropConstrainedForeignId('global_pinned_room_member_id');
        });

        Schema::table('room_members', function (Blueprint $table) {
            $table->dropColumn('hand_raised_at');
        });
    }
};
