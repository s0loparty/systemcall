# Video Calls — Quality Checklist

> Production target: one VPS managed by Dokploy.
> Dokploy owns the host-level Traefik and public ports 80/443.
> The project Docker Compose MUST NOT contain its own Traefik service.

## Dokploy
- [ ] project Compose contains no Traefik
- [ ] Dokploy owns 80/443
- [ ] app domain maps to internal app port
- [ ] Reverb domain maps to 8080
- [ ] LiveKit domain maps to 7880
- [ ] production secrets stored in Dokploy
- [ ] no unnecessary host publishing for HTTP services

## LiveKit network
- [ ] 7881/TCP published/firewalled correctly
- [ ] UDP media range published/firewalled correctly
- [ ] 3478/UDP configured if TURN/UDP enabled
- [ ] no host-443 conflict with Dokploy
- [ ] 5349 only if verified
- [ ] signaling WSS works through Dokploy
- [ ] external IP advertisement works
- [ ] direct media works

## Docker
- [ ] `docker compose config`
- [ ] app/worker/scheduler/reverb share image
- [ ] PostgreSQL/Redis not public
- [ ] healthchecks
- [ ] named volumes
- [ ] no production bind mounts
- [ ] no `artisan serve`
- [ ] pinned production LiveKit version

## Security/access
- [ ] server-side validation/Policies
- [ ] secure room IDs
- [ ] hashed passwords
- [ ] server-verifiable guest identity
- [ ] API secret never frontend
- [ ] token checks room/password/ban/waiting/target
- [ ] LiveKit identity maps to RoomMember
- [ ] webhook signature/idempotency
- [ ] safe/rate-limited chat

## Media/UI
- [ ] mic/camera
- [ ] screen share
- [ ] active speaker from LiveKit
- [ ] adaptive layout/media
- [ ] permission/device errors
- [ ] reconnect/kicked/banned/room-ended states
- [ ] no silent remote unmute

## Features
- [ ] waiting room cannot be bypassed
- [ ] moderation authorization
- [ ] kick removes LiveKit participant
- [ ] ban blocks rejoin/token
- [ ] raised hands
- [ ] personal/global pin
- [ ] max 5 subrooms server-side
- [ ] clean subroom switching

## Checks
- [ ] composer validate
- [ ] Laravel tests
- [ ] Pint
- [ ] PHPStan/Larastan if installed
- [ ] frontend typecheck/lint/build
- [ ] docker compose config

## Production
- [ ] PostgreSQL backup/restore
- [ ] migration/rollback procedure
- [ ] Dokploy/container logs accessible
- [ ] firewall verified
- [ ] two-browser smoke test
- [ ] restrictive-network/TURN test planned
