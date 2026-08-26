<script setup lang="ts">
import { Form } from '@inertiajs/vue3';

import { AlertTriangle } from '@lucide/vue';

import { useTemplateRef } from 'vue';

import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';

import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';

const passwordInput = useTemplateRef('passwordInput');
</script>

<template>
    <div class="space-y-6">
        <Card
            class="overflow-hidden rounded-md border-red-200 bg-red-50/80 shadow-sm"
        >
            <CardHeader class="flex flex-row items-start gap-3">
                <div
                    class="flex size-10 shrink-0 items-center justify-center rounded-md bg-red-100 text-red-700"
                >
                    <AlertTriangle class="size-5" />
                </div>
                <div>
                    <CardTitle class="text-red-950">
                        Удалить аккаунт
                    </CardTitle>
                    <CardDescription class="text-red-800/80">
                        Удаление аккаунта необратимо. Все связанные данные будут
                        удалены.
                    </CardDescription>
                </div>
            </CardHeader>
            <CardContent>
                <Dialog>
                    <DialogTrigger as-child>
                        <Button
                            class="rounded-md"
                            variant="destructive"
                            data-test="delete-user-button"
                        >
                            Удалить аккаунт
                        </Button>
                    </DialogTrigger>
                    <DialogContent class="rounded-md">
                        <Form
                            v-bind="ProfileController.destroy.form()"
                            reset-on-success
                            @error="() => passwordInput?.focus()"
                            :options="{
                                preserveScroll: true,
                            }"
                            class="space-y-6"
                            v-slot="{ errors, processing, reset, clearErrors }"
                        >
                            <DialogHeader class="space-y-3">
                                <DialogTitle>
                                    Подтвердите удаление аккаунта
                                </DialogTitle>
                                <DialogDescription>
                                    Введите пароль, чтобы навсегда удалить
                                    аккаунт и связанные с ним данные.
                                </DialogDescription>
                            </DialogHeader>

                            <div class="grid gap-2">
                                <Label for="password" class="sr-only">
                                    Пароль
                                </Label>
                                <PasswordInput
                                    id="password"
                                    name="password"
                                    ref="passwordInput"
                                    class="rounded-md"
                                    placeholder="Пароль"
                                />
                                <InputError :message="errors.password" />
                            </div>

                            <DialogFooter class="gap-2">
                                <DialogClose as-child>
                                    <Button
                                        class="rounded-md"
                                        variant="secondary"
                                        @click="
                                            () => {
                                                clearErrors();
                                                reset();
                                            }
                                        "
                                    >
                                        Отмена
                                    </Button>
                                </DialogClose>

                                <Button
                                    class="rounded-md"
                                    type="submit"
                                    variant="destructive"
                                    :disabled="processing"
                                    data-test="confirm-delete-user-button"
                                >
                                    Удалить аккаунт
                                </Button>
                            </DialogFooter>
                        </Form>
                    </DialogContent>
                </Dialog>
            </CardContent>
        </Card>
    </div>
</template>
