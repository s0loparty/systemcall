<?php

namespace App\Console\Commands;

use App\Enums\WaitingRoomStatus;
use App\Models\WaitingRoomMember;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('rooms:prune-stale-waiting-room-entries')]
#[Description('Prune pending waiting-room entries that were never decided.')]
class PruneStaleWaitingRoomEntriesCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $staleMinutes = (int) config('video-calls.waiting_room.stale_minutes', 30);
        $cutoff = now()->subMinutes($staleMinutes);

        $deleted = WaitingRoomMember::query()
            ->where('status', WaitingRoomStatus::Pending)
            ->where('created_at', '<', $cutoff)
            ->delete();

        $this->components->info("Pruned {$deleted} stale waiting-room entries.");

        return self::SUCCESS;
    }
}
