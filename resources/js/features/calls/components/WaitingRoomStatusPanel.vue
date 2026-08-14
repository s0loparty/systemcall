<script setup lang="ts">
import type { WaitingRoomEntry } from '../types';

type Props = {
    entry: WaitingRoomEntry | null;
};

defineProps<Props>();
</script>

<template>
    <section
        v-if="entry"
        class="rounded-3xl border border-amber-200 bg-amber-50 p-4 text-amber-950 shadow-sm dark:border-amber-900/60 dark:bg-amber-950/30 dark:text-amber-100"
    >
        <p class="text-sm font-medium">
            {{
                entry.status === 'pending'
                    ? 'Waiting room request pending'
                    : entry.status === 'approved'
                      ? 'Waiting room request approved'
                      : 'Waiting room request rejected'
            }}
        </p>
        <p class="mt-1 text-sm text-amber-800 dark:text-amber-200/80">
            {{
                entry.status === 'pending'
                    ? 'A moderator must approve you before LiveKit media access is issued.'
                    : entry.status === 'approved'
                      ? 'Approval received. The call will request a media token.'
                      : 'You cannot join this room unless a moderator changes the decision.'
            }}
        </p>
    </section>
</template>
