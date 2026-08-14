<?php

namespace App\Services\LiveKit;

use Agence104\LiveKit\AccessToken;
use Agence104\LiveKit\AccessTokenOptions;
use Agence104\LiveKit\VideoGrant;
use App\Models\Room;
use App\Models\RoomMember;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;

class LiveKitTokenService
{
    /**
     * @throws \Exception
     */
    public function issueToken(Room $room, RoomMember $member): LiveKitToken
    {
        $url = $this->configString('url');
        $apiKey = $this->configString('api_key');
        $apiSecret = $this->configString('api_secret');
        $ttl = max(60, (int) Config::get('services.livekit.token_ttl', 3600));
        $roomName = $this->roomName($room);

        $tokenOptions = (new AccessTokenOptions)
            ->setIdentity($member->livekit_identity)
            ->setName($member->display_name)
            ->setTtl($ttl)
            ->setMetadata(json_encode([
                'room_member_id' => $member->id,
                'room_public_id' => $room->public_id,
                'role' => $member->role->value,
            ], JSON_THROW_ON_ERROR));

        $videoGrant = (new VideoGrant)
            ->setRoomJoin()
            ->setRoomName($roomName)
            ->setCanPublish($member->can_publish_audio || $member->can_publish_video || $member->can_publish_screen)
            ->setCanSubscribe()
            ->setCanPublishData()
            ->setCanPublishSources($this->publishSources($member));

        $token = (new AccessToken($apiKey, $apiSecret))
            ->init($tokenOptions)
            ->setGrant($videoGrant)
            ->toJwt();

        return new LiveKitToken($url, $token, $roomName, $member->livekit_identity, $ttl);
    }

    public function roomName(Room $room): string
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
     * @return list<string>
     */
    private function publishSources(RoomMember $member): array
    {
        return array_values(Arr::where([
            $member->can_publish_video ? 'camera' : null,
            $member->can_publish_audio ? 'microphone' : null,
            $member->can_publish_screen ? 'screen_share' : null,
            $member->can_publish_screen ? 'screen_share_audio' : null,
        ], fn (?string $source): bool => $source !== null));
    }
}
