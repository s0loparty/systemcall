<?php

namespace App\Services\LiveKit;

use Agence104\LiveKit\RoomServiceClient;
use App\Models\Room;
use App\Models\RoomMember;
use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Illuminate\Support\Facades\Config;
use Livekit\ParticipantPermission;
use Livekit\TrackSource;

class LiveKitRoomService
{
    public function __construct(private ?RoomServiceClient $client = null) {}

    public function removeParticipant(Room $room, RoomMember $member): void
    {
        $this->client()->removeParticipant($this->roomName($room), $member->livekit_identity);
    }

    public function updateParticipantPermissions(Room $room, RoomMember $member): void
    {
        $permission = (new ParticipantPermission)
            ->setCanSubscribe(true)
            ->setCanPublish($member->can_publish_audio || $member->can_publish_video || $member->can_publish_screen)
            ->setCanPublishData(true)
            ->setCanPublishSources($this->publishSources($member));

        $this->client()->updateParticipant(
            $this->roomName($room),
            $member->livekit_identity,
            null,
            $permission,
            $member->display_name,
        );
    }

    private function client(): RoomServiceClient
    {
        if ($this->client instanceof RoomServiceClient) {
            return $this->client;
        }

        return $this->client = new RoomServiceClient(
            $this->configString('url'),
            $this->configString('api_key'),
            $this->configString('api_secret'),
        );
    }

    private function roomName(Room $room): string
    {
        return 'room-'.$room->public_id;
    }

    private function configString(string $key): string
    {
        $value = Config::get('services.livekit.'.$key);

        if (! is_string($value) || $value === '') {
            throw new \RuntimeException('LiveKit configuration is incomplete.');
        }

        return $value;
    }

    /**
     * @return RepeatedField<int>
     */
    private function publishSources(RoomMember $member): RepeatedField
    {
        $sources = new RepeatedField(GPBType::ENUM, TrackSource::class);

        if ($member->can_publish_video) {
            $sources[] = TrackSource::CAMERA;
        }

        if ($member->can_publish_audio) {
            $sources[] = TrackSource::MICROPHONE;
        }

        if ($member->can_publish_screen) {
            $sources[] = TrackSource::SCREEN_SHARE;
            $sources[] = TrackSource::SCREEN_SHARE_AUDIO;
        }

        return $sources;
    }
}
