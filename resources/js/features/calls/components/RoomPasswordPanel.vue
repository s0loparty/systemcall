<script setup lang="ts">
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

const password = defineModel<string>('password', { required: true });
</script>

<template>
    <div class="space-y-1.5">
        <Label class="text-sm" for="room-password"> Пароль комнаты </Label>
        <Input
            id="room-password"
            v-model="password"
            data-test="room-password"
            type="password"
            autocomplete="current-password"
            class="rounded-lg border-border/60 bg-white/80"
            placeholder="Введите пароль комнаты"
        />
    </div>
</template>
