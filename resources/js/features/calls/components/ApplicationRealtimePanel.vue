<script setup lang="ts">
import type { EchoConnectionState, RoomApplicationActivity } from '../types';

type Props = {
    connectionState: EchoConnectionState;
    activities: RoomApplicationActivity[];
};

defineProps<Props>();
</script>

<template>
    <section
        class="flex flex-col gap-3 rounded-3xl border border-neutral-200 bg-white p-4 shadow-sm md:flex-row md:items-center md:justify-between dark:border-neutral-800 dark:bg-neutral-950"
    >
        <div class="flex items-center gap-3">
            <span
                class="size-2 rounded-full"
                :class="
                    connectionState === 'connected'
                        ? 'bg-emerald-500'
                        : connectionState === 'connecting' ||
                            connectionState === 'reconnecting'
                          ? 'bg-amber-400'
                          : connectionState === 'failed'
                            ? 'bg-red-500'
                            : 'bg-neutral-400'
                "
            />
            <div>
                <p class="text-sm font-medium text-neutral-950 dark:text-white">
                    Reverb application channel
                </p>
                <p class="text-xs text-neutral-500 dark:text-neutral-400">
                    Echo status: {{ connectionState }}
                </p>
            </div>
        </div>

        <div class="min-w-0 text-sm text-neutral-600 dark:text-neutral-300">
            <p v-if="activities.length === 0">Application events are ready.</p>
            <ol v-else class="flex flex-col gap-1">
                <li
                    v-for="activity in activities"
                    :key="activity.id"
                    class="truncate"
                >
                    {{ activity.message }}
                </li>
            </ol>
        </div>
    </section>
</template>
