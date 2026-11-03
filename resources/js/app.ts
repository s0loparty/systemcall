import { createInertiaApp } from '@inertiajs/vue3';

import { initializeTheme } from '@/composables/useAppearance';
import AppLayout from '@/layouts/AppLayout.vue';
import AuthLayout from '@/layouts/AuthLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { initializeEcho } from '@/lib/echo';
import { initializeFlashToast } from '@/lib/flashToast';
import * as Sentry from '@sentry/vue';

import './assets/css/app.css';

const fallbackAppName =
    import.meta.env.VITE_APP_NAME &&
    !import.meta.env.VITE_APP_NAME.includes('${')
        ? import.meta.env.VITE_APP_NAME
        : 'Laravel';

// This configures Laravel Echo for Reverb private application channels...
initializeEcho();

createInertiaApp({
    title: (title, page) => {
        const appName =
            typeof page.props.name === 'string'
                ? page.props.name
                : fallbackAppName;

        return title ? `${title} - ${appName}` : appName;
    },
    layout: (name) => {
        switch (true) {
            case name === 'Welcome':
                return null;
            case ['rooms/Prepare', 'rooms/Waiting', 'rooms/Call'].includes(
                name,
            ):
                return null;
            case name.startsWith('auth/'):
                return AuthLayout;
            case name.startsWith('settings/'):
                return [AppLayout, SettingsLayout];
            default:
                return AppLayout;
        }
    },
    progress: {
        color: '#4B5563',
    },
    withApp(app, { ssr }) {
        // Sentry-compatible DSN points to the self-hosted Bugsink instance.
        // Disabled by default for local development and unconfigured deploys.
        const dsn = import.meta.env.VITE_SENTRY_DSN;

        if (!ssr && dsn && import.meta.env.PROD) {
            Sentry.init({
                app,
                dsn,
                environment: import.meta.env.MODE,
                tracesSampleRate: 0,
                dataCollection: {
                    userInfo: false,
                    cookies: false,
                    httpHeaders: false,
                    httpBodies: [],
                    urlQueryParams: false,
                },
            });
        }
    },
});

if (typeof window !== 'undefined') {
    initializeTheme();
    initializeFlashToast();

    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
            navigator.serviceWorker
                .register('/sw.js')
                .catch((error: unknown) => {
                    console.error('Service worker registration failed:', error);
                });
        });
    }
}
