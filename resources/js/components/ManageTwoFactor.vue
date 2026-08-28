<script setup lang="ts">
import { Form } from '@inertiajs/vue3';

import { ShieldCheck } from '@lucide/vue';

import { onUnmounted, ref } from 'vue';

import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';

import { disable, enable } from '@/routes/two-factor';

import TwoFactorRecoveryCodes from '@/components/TwoFactorRecoveryCodes.vue';
import TwoFactorSetupModal from '@/components/TwoFactorSetupModal.vue';
import { useTwoFactorAuth } from '@/composables/useTwoFactorAuth';

export type Props = {
    canManageTwoFactor?: boolean;
    requiresConfirmation?: boolean;
    twoFactorEnabled?: boolean;
};

withDefaults(defineProps<Props>(), {
    canManageTwoFactor: false,
    requiresConfirmation: false,
    twoFactorEnabled: false,
});

const { hasSetupData, clearTwoFactorAuthData } = useTwoFactorAuth();
const showSetupModal = ref<boolean>(false);

onUnmounted(() => clearTwoFactorAuthData());
</script>

<template>
    <div v-if="canManageTwoFactor" class="space-y-6">
        <Card
            class="overflow-hidden rounded-md border-slate-200/80 bg-white/90 shadow-sm dark:bg-neutral-900 dark:border-neutral-800"
        >
            <CardHeader>
                <CardTitle class="flex items-center gap-2">
                    <ShieldCheck class="size-5 text-violet-600" />
                    Двухфакторная аутентификация
                </CardTitle>
                <CardDescription>
                    Дополнительный одноразовый код защищает вход, даже если
                    пароль стал известен посторонним.
                </CardDescription>
            </CardHeader>
            <CardContent>
                <div
                    v-if="!twoFactorEnabled"
                    class="flex flex-col items-start justify-start gap-4"
                >
                    <div
                        class="rounded-md border border-violet-100 bg-violet-50 p-4 text-sm text-violet-950"
                    >
                        Включите 2FA и подключите TOTP-приложение на телефоне.
                        После настройки вход будет подтверждаться безопасным
                        кодом.
                    </div>

                    <div>
                        <Button
                            v-if="hasSetupData"
                            class="rounded-md"
                            @click="showSetupModal = true"
                        >
                            <ShieldCheck />Продолжить настройку
                        </Button>
                        <Form
                            v-else
                            v-bind="enable.form()"
                            @success="showSetupModal = true"
                            #default="{ processing }"
                        >
                            <Button
                                class="rounded-md"
                                type="submit"
                                :disabled="processing"
                            >
                                Включить 2FA
                            </Button>
                        </Form>
                    </div>
                </div>

                <div
                    v-else
                    class="flex flex-col items-start justify-start gap-4"
                >
                    <div
                        class="rounded-md border border-emerald-100 bg-emerald-50 p-4 text-sm text-emerald-950"
                    >
                        2FA включена. При входе потребуется одноразовый код из
                        приложения-аутентификатора.
                    </div>

                    <div class="relative inline">
                        <Form v-bind="disable.form()" #default="{ processing }">
                            <Button
                                class="rounded-md"
                                variant="destructive"
                                type="submit"
                                :disabled="processing"
                            >
                                Отключить 2FA
                            </Button>
                        </Form>
                    </div>

                    <TwoFactorRecoveryCodes />
                </div>
            </CardContent>
        </Card>

        <TwoFactorSetupModal
            v-model:isOpen="showSetupModal"
            :requiresConfirmation="requiresConfirmation"
            :twoFactorEnabled="twoFactorEnabled"
        />
    </div>
</template>
