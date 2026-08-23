import { configureEcho } from '@laravel/echo-vue';

function optionalPort(value: string | boolean | undefined): number | undefined {
    if (typeof value !== 'string' || value === '') {
        return undefined;
    }

    const port = Number(value);

    return Number.isFinite(port) ? port : undefined;
}

function websocketHost(): string | undefined {
    const configuredHost = import.meta.env.VITE_REVERB_HOST;

    if (
        configuredHost === 'localhost' &&
        window.location.hostname === '127.0.0.1' &&
        window.location.port !== '' &&
        window.location.port !== '80'
    ) {
        return 'host.docker.internal';
    }

    return configuredHost;
}

export function initializeEcho(): void {
    configureEcho({
        broadcaster: 'reverb',
        key: import.meta.env.VITE_REVERB_APP_KEY,
        authEndpoint: '/broadcasting/room-auth',
        wsHost: websocketHost(),
        wsPort: optionalPort(import.meta.env.VITE_REVERB_PORT),
        wssPort: optionalPort(import.meta.env.VITE_REVERB_PORT),
        forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
        enabledTransports: ['ws', 'wss'],
    });
}
