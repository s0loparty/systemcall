import { computed, onBeforeUnmount, ref } from 'vue';

import type {
    RoomMemberSummary,
    SelectedDevices,
    SubroomTokenResponse,
    TestingMediaMode,
    WaitingRoomEntry,
    WaitingRoomEntryDecidedEvent,
} from '../types';
import { useLiveKit } from './useLiveKit';
import { useMediaDevices } from './useMediaDevices';
import { useRoomJoin } from './useRoomJoin';

type UseCallSessionOptions = {
    roomPublicId: string;
    displayName: string;
    initialSelectedDevices?: SelectedDevices;
    initialVideoBackground?: {
        enabled: boolean;
        blurRadius: number;
    };
    testingMediaMode?: TestingMediaMode;
};

export function useCallSession(options: UseCallSessionOptions) {
    const liveKit = useLiveKit({
        testingMediaMode: options.testingMediaMode,
        initialVideoBackground: options.initialVideoBackground,
    });
    const mediaDevices = useMediaDevices({
        initialSelectedDevices: options.initialSelectedDevices,
        testingMediaMode: options.testingMediaMode,
    });
    const roomJoin = useRoomJoin(options.roomPublicId, options.displayName);
    const connecting = ref(false);
    const errorMessage = ref<string | null>(null);
    const waitingRoomEntry = ref<WaitingRoomEntry | null>(null);
    const joinedMember = ref<RoomMemberSummary | null>(null);
    const switchingSubroom = ref(false);
    const deviceErrorMessage = computed(
        () =>
            mediaDevices.deviceErrorMessage.value ??
            liveKit.videoBackgroundErrorMessage.value ??
            liveKit.deviceErrorMessage.value,
    );

    const busy = computed(
        () =>
            connecting.value ||
            roomJoin.joinRequest.processing ||
            roomJoin.tokenRequest.processing ||
            roomJoin.subroomTokenRequest.processing ||
            roomJoin.leaveRequest.processing ||
            switchingSubroom.value,
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

            if (response.kind === 'waiting-room') {
                waitingRoomEntry.value = response.response.waiting_room;

                return;
            }

            joinedMember.value = response.member;

            await liveKit.connect(
                response.response.livekit.url,
                response.response.livekit.token,
                mediaDevices.selectedDevices.value,
            );
        } catch (error) {
            errorMessage.value =
                error instanceof Error
                    ? error.message
                    : 'Не удалось подключиться к комнате.';
            await liveKit.disconnect();
        } finally {
            connecting.value = false;
        }
    }

    async function handleWaitingRoomDecision(
        event: WaitingRoomEntryDecidedEvent,
    ): Promise<void> {
        if (waitingRoomEntry.value?.id !== event.waiting_room.id) {
            return;
        }

        waitingRoomEntry.value = event.waiting_room;

        if (event.waiting_room.status === 'rejected') {
            errorMessage.value = 'Ваша заявка на вход в комнату отклонена.';

            return;
        }

        if (
            event.waiting_room.status !== 'approved' ||
            liveKit.connected.value
        ) {
            return;
        }

        connecting.value = true;
        errorMessage.value = null;

        try {
            const response = await roomJoin.issueToken();

            await liveKit.connect(
                response.response.livekit.url,
                response.response.livekit.token,
                mediaDevices.selectedDevices.value,
            );
            joinedMember.value = event.member;
        } catch (error) {
            errorMessage.value =
                error instanceof Error
                    ? error.message
                    : 'Не удалось подключиться к комнате.';
            await liveKit.disconnect();
        } finally {
            connecting.value = false;
        }
    }

    async function switchSubroom(
        subroomPublicId: string | null,
    ): Promise<SubroomTokenResponse | null> {
        if (!liveKit.connected.value || switchingSubroom.value) {
            return null;
        }

        switchingSubroom.value = true;
        errorMessage.value = null;

        const currentMediaState = {
            microphone: liveKit.microphoneEnabled.value,
            camera: liveKit.cameraEnabled.value,
        };

        try {
            const response = await roomJoin.issueSubroomToken(subroomPublicId);

            await liveKit.disconnect();
            await liveKit.connect(
                response.livekit.url,
                response.livekit.token,
                mediaDevices.selectedDevices.value,
                currentMediaState,
            );
            joinedMember.value = response.member;

            return response;
        } catch (error) {
            errorMessage.value =
                error instanceof Error
                    ? error.message
                    : 'Не удалось переключить подкомнату.';

            return null;
        } finally {
            switchingSubroom.value = false;
        }
    }

    async function leave(): Promise<void> {
        if (roomJoin.leaveRequest.processing) {
            return;
        }

        try {
            await roomJoin.leave();
            joinedMember.value = null;
        } finally {
            await liveKit.disconnect();
        }
    }

    onBeforeUnmount(() => {
        void liveKit.disconnect();
    });

    return {
        joinRequest: roomJoin.joinRequest,
        tokenRequest: roomJoin.tokenRequest,
        waitingRoomEntry,
        joinedMember,
        connected: liveKit.connected,
        connectionState: liveKit.connectionState,
        connecting,
        switchingSubroom,
        busy,
        microphoneEnabled: liveKit.microphoneEnabled,
        cameraEnabled: liveKit.cameraEnabled,
        screenShareEnabled: liveKit.screenShareEnabled,
        videoBackgroundEnabled: liveKit.videoBackgroundEnabled,
        videoBackgroundProcessing: liveKit.videoBackgroundProcessing,
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
        switchSubroom,
        leave,
        handleWaitingRoomDecision,
        disconnect: liveKit.disconnect,
        toggleMicrophone: liveKit.toggleMicrophone,
        toggleCamera: liveKit.toggleCamera,
        toggleScreenShare: liveKit.toggleScreenShare,
        setVideoBackgroundEnabled: liveKit.setVideoBackgroundEnabled,
        setMediaHost: liveKit.setMediaHost,
    };
}
