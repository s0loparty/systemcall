import { computed, onBeforeUnmount, ref } from 'vue';
import { useLiveKit } from './useLiveKit';
import { useMediaDevices } from './useMediaDevices';
import { useRoomJoin } from './useRoomJoin';

type UseCallSessionOptions = {
    roomPublicId: string;
    displayName: string;
};

export function useCallSession(options: UseCallSessionOptions) {
    const liveKit = useLiveKit();
    const mediaDevices = useMediaDevices();
    const roomJoin = useRoomJoin(options.roomPublicId, options.displayName);
    const connecting = ref(false);
    const errorMessage = ref<string | null>(null);
    const deviceErrorMessage = computed(
        () =>
            mediaDevices.deviceErrorMessage.value ??
            liveKit.deviceErrorMessage.value,
    );

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
            await mediaDevices.stopPreview();
            const response = await roomJoin.joinAndIssueToken();

            await liveKit.connect(
                response.livekit.url,
                response.livekit.token,
                mediaDevices.selectedDevices.value,
            );
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
        connectionState: liveKit.connectionState,
        connecting,
        busy,
        microphoneEnabled: liveKit.microphoneEnabled,
        cameraEnabled: liveKit.cameraEnabled,
        screenShareEnabled: liveKit.screenShareEnabled,
        activeSpeakerIdentity: liveKit.activeSpeakerIdentity,
        tiles: liveKit.tiles,
        errorMessage,
        deviceErrorMessage,
        audioInputs: mediaDevices.audioInputs,
        videoInputs: mediaDevices.videoInputs,
        selectedDevices: mediaDevices.selectedDevices,
        previewEnabled: mediaDevices.previewEnabled,
        previewLoading: mediaDevices.previewLoading,
        loadDevices: mediaDevices.loadDevices,
        startPreview: mediaDevices.startPreview,
        stopPreview: mediaDevices.stopPreview,
        setPreviewHost: mediaDevices.setPreviewHost,
        setAudioInput: mediaDevices.setAudioInput,
        setVideoInput: mediaDevices.setVideoInput,
        connect,
        disconnect: liveKit.disconnect,
        toggleMicrophone: liveKit.toggleMicrophone,
        toggleCamera: liveKit.toggleCamera,
        toggleScreenShare: liveKit.toggleScreenShare,
        setMediaHost: liveKit.setMediaHost,
    };
}
