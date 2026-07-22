<?php

namespace Database\Seeders;

use Symfony\Component\Yaml\Yaml;
use Ramsey\Uuid\Uuid;
use Illuminate\Support\Str;
use Illuminate\Database\Seeder;
use Pterodactyl\Models\Location;
use Pterodactyl\Models\Node;
use Pterodactyl\Models\Allocation;
use Pterodactyl\Services\Nodes\NodeCreationService;
use Pterodactyl\Services\Locations\LocationCreationService;

class DevSetupSeeder extends Seeder
{
    public function run(
        NodeCreationService $nodeService,
        LocationCreationService $locationService,
    ): void {
        $location = $this->ensureLocation($locationService);
        $node = $this->ensureNode($nodeService, $location);
        $this->ensureAllocations($node);
        $this->writeDaemonConfig($node);
    }

    private function ensureLocation(LocationCreationService $service): Location
    {
        $location = Location::query()->where('short', 'dev')->first();
        if ($location) {
            return $location;
        }
        return $service->handle([
            'short' => 'dev',
            'long' => 'Development',
        ]);
    }

    private function ensureNode(NodeCreationService $service, Location $location): Node
    {
        $node = Node::query()->where('fqdn', 'elytra')->first();
        if ($node) {
            return $node;
        }
        return $service->handle([
            'name' => 'hydrodactyl-dev',
            'location_id' => $location->id,
            'fqdn' => 'elytra',
            'scheme' => 'http',
            'daemonType' => 'elytra',
            'daemonListen' => 8080,
            'daemonSFTP' => 2022,
            'daemonBase' => '/var/lib/pterodactyl/volumes',
            'memory' => 4096,
            'memory_overallocate' => 0,
            'disk' => 102400,
            'disk_overallocate' => 0,
            'upload_size' => 100,
            'public' => true,
            'behind_proxy' => false,
            'maintenance_mode' => false,
            'description' => 'Auto-created dev node',
        ]);
    }

    private function ensureAllocations(Node $node): void
    {
        $existing = Allocation::query()->where('node_id', $node->id)->count();
        if ($existing > 0) {
            return;
        }

        $allocations = [];
        foreach (range(25565, 25575) as $port) {
            $allocations[] = [
                'node_id' => $node->id,
                'ip' => '0.0.0.0',
                'port' => $port,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        Allocation::query()->insert($allocations);
    }

    private function writeDaemonConfig(Node $node): void
    {
        $path = '/etc/pterodactyl/config.yml';
        $config = $node->getYamlConfiguration();
        $written = file_put_contents($path, $config, LOCK_EX);
        if ($written === false) {
            $this->command?->warn("Failed to write daemon config to {$path}");
            return;
        }
        $this->command?->info("Daemon config written to {$path}");
    }
}
