<script setup lang="ts">
type Props = {
    error?: string;
};

defineProps<Props>();

const password = defineModel<string>('password', { required: true });
</script>

<template>
    <section
        class="rounded-2xl border border-neutral-200 bg-white p-4 shadow-xs dark:border-neutral-800 dark:bg-neutral-950"
    >
        <label class="text-sm font-medium" for="room-password">
            Room password
        </label>
        <input
            id="room-password"
            v-model="password"
            class="mt-2 w-full rounded-xl border border-neutral-300 bg-white px-3 py-2 text-sm transition outline-none focus:border-neutral-900 dark:border-neutral-700 dark:bg-neutral-900 dark:focus:border-neutral-100"
            type="password"
            autocomplete="current-password"
        />
        <p v-if="error" class="mt-2 text-sm text-red-600 dark:text-red-400">
            {{ error }}
        </p>
    </section>
</template>
