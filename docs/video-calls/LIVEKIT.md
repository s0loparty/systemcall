# LiveKit — Single Dokploy VPS

> Production target: one VPS managed by Dokploy.
> Dokploy owns the host-level Traefik and public ports 80/443.
> The project Docker Compose MUST NOT contain its own Traefik service.

## Signaling
Configure in Dokploy:
```text
livekit.example.com -> livekit:7880
```
HTTPS/WSS signaling passes through Dokploy-managed Traefik.

## Media
Media bypasses HTTP reverse proxy:
```text
7881/TCP
50000-60000/UDP
3478/UDP
```

Conceptual config:
```yaml
port: 7880
rtc:
  tcp_port: 7881
  port_range_start: 50000
  port_range_end: 60000
  use_external_ip: true
redis:
  address: redis:6379
```
Validate syntax against the pinned LiveKit version.

## TURN
Initial connectivity strategy:
1. direct UDP WebRTC;
2. ICE/TCP on 7881;
3. TURN/UDP on 3478;
4. optional TURN/TLS on 5349 only after version/config verification.

Do not bind TURN/TLS to host 443 because Dokploy Traefik owns it.

If reliable TURN/TLS on public 443 becomes necessary, preferred upgrade is a dedicated LiveKit VPS/public IP.

## Tokens
Browser requests access from Laravel. Laravel validates room/member/password/ban/waiting room/target and signs the LiveKit token.

Never expose API secret.

## Identity
Use backend-controlled RoomMember-based identity, e.g. `room-member:123`. Never use display name as security identity.

## Service layer
Centralize token creation, participant removal, force-mute and media-room operations behind `LiveKitService`.

## Frontend/media
Use LiveKit SDK for mic, camera, screen share, active speaker and media connection state. Review adaptive stream, simulcast, dynacast and selective subscriptions for larger rooms.

## Webhooks
Verify signatures and process idempotently. Webhooks are not frontend media realtime.

## Production checks
- signaling WSS through Dokploy
- correct external IP advertisement
- 7881/TCP reachable
- UDP range reachable
- TURN/UDP tested if enabled
- Redis reachable
- two-browser audio/video
- screen share
- reconnect
