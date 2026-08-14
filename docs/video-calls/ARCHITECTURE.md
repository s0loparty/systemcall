# Video Calls — Architecture

> Production target: one VPS managed by Dokploy.
> Dokploy owns the host-level Traefik and public ports 80/443.
> The project Docker Compose MUST NOT contain its own Traefik service.

## Topology
```text
Internet
   |
 80/443
   |
Dokploy-managed Traefik
   |----------|-------------|
   v          v             v
 app       reverb        livekit:7880
   |                         |
Postgres/Redis               |
                             |
Browser ===== direct WebRTC =+
```

## Routing
Dokploy domain configuration:
```text
call.example.com      -> app:8000
ws.call.example.com   -> reverb:8080
livekit.example.com   -> livekit:7880
```

LiveKit direct media:
```text
7881/TCP
50000-60000/UDP
3478/UDP
```

Do not host-publish app/Reverb/LiveKit signaling ports solely for HTTP routing.

The project Compose now follows this boundary: only LiveKit media/TURN ports are published directly on the host. PostgreSQL, Redis, app HTTP, Reverb, and LiveKit signaling remain internal to the Docker network for Dokploy routing.

## Responsibilities
Laravel is authoritative for business state and authorization: rooms, members, roles, waiting room, bans, moderation, tokens, chat, global pin and subrooms.

LiveKit is authoritative for media/realtime media presence: audio, video, screen share, tracks, SFU/WebRTC and active speaker.

Reverb carries application-level realtime events.

## Persistence Model
Milestone 1 adds the Laravel-owned persistence layer:
```text
rooms
room_members
room_bans
waiting_room_members
room_messages
room_subrooms
```

`rooms.public_id` and `room_subrooms.public_id` are public-facing identifiers. Numeric primary keys remain internal.

`room_members.livekit_identity` is unique and is the backend-controlled identity that later LiveKit tokens must use.

Guests are represented with nullable `user_id` plus server-issued `guest_id`. Browser-supplied arbitrary guest ids are still not trusted; Milestone 2 must add server-verifiable guest identity issuance.

Role and status values are stored as strings and cast through PHP enums. Policies and form requests cover current owner/member checks; moderation-specific operations remain in later milestones.

## Room CRUD / Access
Milestone 2 exposes JSON endpoints for room CRUD and join/access:
```text
GET    /rooms
POST   /rooms
GET    /rooms/{public_id}
PATCH  /rooms/{public_id}
DELETE /rooms/{public_id}
POST   /rooms/{public_id}/memberships
```

Only authenticated verified users create/update/delete rooms. Room route binding uses `rooms.public_id`; database ids are not public route identifiers.

Room creation hashes `password` into `password_hash` and creates the owner `RoomMember`. Public rooms are viewable by guests. Private rooms are not publicly viewable; private password-protected rooms may be joined through the join endpoint with the correct password.

Guest membership uses an encrypted/httpOnly Laravel cookie named `video_call_guest_id`. The browser never chooses the stored `guest_id`; any submitted `guest_id` field is ignored.

## Join flow
```text
Client -> Laravel
          |- room active?
          |- identity valid?
          |- password?
          |- banned?
          |- waiting approval?
          |- target allowed?
          v
        RoomMember
          v
        signed LiveKit token
          v
        LiveKit
```

## Identity
LiveKit identity is backend-controlled and maps to RoomMember. Display name is not a security identity.

## Moderation
Privileged request -> Laravel Policy -> application/LiveKit service action -> application realtime notification.

Kick removes the actual LiveKit participant. Ban persists and blocks future token issuance. Silent remote-unmute is forbidden.

## Subrooms
Each subroom is a distinct LiveKit room. Switching disconnects current media room, requests a separately authorized target token and connects to target.

## Scaling path
Start with all services on one Dokploy VPS. Later move PostgreSQL/Redis and especially LiveKit/TURN independently. A dedicated LiveKit VPS allows TURN/TLS to use public 443 without conflicting with Dokploy.
