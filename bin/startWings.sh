podman run -d \
  --name wings \
  --restart unless-stopped \
  --network host \
  --cap-add=NET_ADMIN \
  -e TZ="$(timedatectl show -p Timezone --value 2>/dev/null || echo UTC)" \
  -v "$PWD/srv/config/config.yaml:/etc/pterodactyl/config.yml" \
  -v "$PWD/srv/pterodactyl:/var/lib/pterodactyl" \
  -v "$PWD/srv/pterodactyl/tmp:/tmp/pterodactyl" \
  -v "/run/user/1000/podman/podman.sock:/var/run/docker.sock" \
  ghcr.io/pterodactyl/wings:latest
