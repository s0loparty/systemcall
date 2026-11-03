import { createInertiaApp } from '@inertiajs/vue3';
import * as Sentry from '@sentry/vue';
import { createApp, h } from 'vue';

import { initializeTheme } from '@/composables/useAppearance';
import AppLayout from '@/layouts/AppLayout.vue';
import AuthLayout from '@/layouts/AuthLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { initializeEcho } from '@/lib/echo';
import { initializeFlashToast } from '@/lib/flashToast';

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
    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) });
        app.use(plugin);

        // Sentry-compatible DSN points to the self-hosted Bugsink instance.
        // Disabled by default for local development and unconfigured deploys.
        const dsn = import.meta.env.VITE_SENTRY_DSN;
        if (dsn && import.meta.env.PROD) {
            Sentry.init({
                app,
                dsn,
                environment: import.meta.env.MODE,
                tracesSampleRate: 0,
                sendDefaultPii: false,
            });
        }

        app.mount(el);
    },
});

// This will set light / dark mode on page load...
initializeTheme();

// This will listen for flash toast data from the server...
initializeFlashToast();

if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js').catch((error: unknown) => {
            console.error('Service worker registration failed:', error);
        });
    });
}
