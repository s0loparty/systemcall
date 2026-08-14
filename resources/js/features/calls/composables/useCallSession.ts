import { computed, onBeforeUnmount, ref } from 'vue';
import { useLiveKit } from './useLiveKit';
import { useRoomJoin } from './useRoomJoin';

type UseCallSessionOptions = {
    roomPublicId: string;
    displayName: string;
};

export function useCallSession(options: UseCallSessionOptions) {
    const liveKit = useLiveKit();
    const roomJoin = useRoomJoin(options.roomPublicId, options.displayName);
    const connecting = ref(false);
    const errorMessage = ref<string | null>(null);

    const busy = computed(
        () =>
            connecting.value ||
            roomJoin.joinRequest.processing ||
            roomJoin.tokenRequest.processing,
    );

    async function connect(): Promise<void> {
        if (busy.value || liveKit.connected.value) {
            return;
        }

        connecting.value = true;
        errorMessage.value = null;

        try {
            const response = await roomJoin.joinAndIssueToken();

            await liveKit.connect(response.livekit.url, response.livekit.token);
        } catch (error) {
            errorMessage.value =
                error instanceof Error
                    ? error.message
                    : 'Could not connect to the room.';
            await liveKit.disconnect();
        } finally {
            connecting.value = false;
        }
    }

    onBeforeUnmount(() => {
        void liveKit.disconnect();
    });

    return {
        joinRequest: roomJoin.joinRequest,
        tokenRequest: roomJoin.tokenRequest,
        connected: liveKit.connected,
        connecting,
        busy,
        microphoneEnabled: liveKit.microphoneEnabled,
        cameraEnabled: liveKit.cameraEnabled,
        tiles: liveKit.tiles,
        errorMessage,
        connect,
        disconnect: liveKit.disconnect,
        toggleMicrophone: liveKit.toggleMicrophone,
        toggleCamera: liveKit.toggleCamera,
        setMediaHost: liveKit.setMediaHost,
    };
}
