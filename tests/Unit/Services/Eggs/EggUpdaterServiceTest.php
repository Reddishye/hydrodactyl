<?php

namespace Pterodactyl\Tests\Unit\Services\Eggs;

use Mockery\MockInterface;
use Pterodactyl\Models\Egg;
use Pterodactyl\Models\EggVariable;
use Pterodactyl\Tests\TestCase;
use Illuminate\Support\Facades\Http;
use Illuminate\Database\ConnectionInterface;
use Pterodactyl\Services\Eggs\EggUpdaterService;
use Pterodactyl\Services\Eggs\EggParserService;
use Pterodactyl\Contracts\Repository\SettingsRepositoryInterface;

class EggUpdaterServiceTest extends TestCase
{
    private MockInterface $connection;
    private MockInterface $parser;
    private MockInterface $settings;
    private EggUpdaterService $service;

    private array $sampleEggJson;

    public function setUp(): void
    {
        parent::setUp();

        $this->connection = $this->mock(ConnectionInterface::class);
        $this->parser = $this->mock(EggParserService::class);
        $this->settings = $this->mock(SettingsRepositoryInterface::class);

        $this->service = new EggUpdaterService(
            $this->connection,
            $this->parser,
            $this->settings,
        );

        $this->sampleEggJson = [
            'meta' => ['version' => 'PTDL_v2', 'update_url' => 'https://example.com/egg.json'],
            'name' => 'Updated Egg',
            'description' => 'Updated description',
            'author' => 'test@example.com',
            'startup' => 'java -jar server.jar',
            'docker_images' => ['ghcr.io/test/image:latest' => 'ghcr.io/test/image:latest'],
            'config' => [
                'files' => '{}',
                'startup' => '{}',
                'logs' => '{}',
                'stop' => '^C',
            ],
            'scripts' => [
                'installation' => [
                    'script' => 'echo install',
                    'entrypoint' => 'bash',
                    'container' => 'alpine',
                ],
            ],
            'variables' => [],
        ];
    }

    public function test_check_returns_error_when_no_url(): void
    {
        $egg = Egg::factory()->make(['update_url' => '', 'id' => 1]);

        $result = $this->service->check($egg);

        $this->assertSame('error', $result['status']);
        $this->assertSame('No update URL configured', $result['error']);
    }

    public function test_check_returns_error_when_host_disallowed(): void
    {
        config(['app.allowed_egg_hosts' => 'allowed.com']);
        $egg = Egg::factory()->make(['update_url' => 'https://evil.com/egg.json', 'id' => 1]);

        $result = $this->service->check($egg);

        $this->assertSame('error', $result['status']);
        $this->assertStringContainsString('ALLOWED_EGG_HOSTS', $result['error']);
        config(['app.allowed_egg_hosts' => '']);
    }

    public function test_check_returns_up_to_date_on_304(): void
    {
        $egg = Egg::factory()->make([
            'update_url' => 'https://example.com/egg.json',
            'last_etag' => '"abc123"',
            'id' => 1,
        ]);
        $egg->forceFill(['last_update_check_at' => null]);

        // We need a partial mock because check() saves to the egg
        $partial = \Mockery::mock(Egg::class)->makePartial();
        $partial->forceFill([
            'update_url' => 'https://example.com/egg.json',
            'last_etag' => '"abc123"',
            'id' => 1,
            'last_update_check_at' => null,
        ]);
        $partial->shouldReceive('save')->once()->andReturn(true);

        Http::fake([
            'example.com/*' => Http::response('', 304),
        ]);

        $result = $this->service->check($partial);

        $this->assertSame('up_to_date', $result['status']);
    }

    public function test_check_returns_error_on_http_failure(): void
    {
        $egg = Egg::factory()->make([
            'update_url' => 'https://example.com/egg.json',
            'id' => 1,
        ]);

        Http::fake([
            'example.com/*' => Http::response('Not Found', 404),
        ]);

        $result = $this->service->check($egg);

        $this->assertSame('error', $result['status']);
        $this->assertStringContainsString('404', $result['error']);
    }

    public function test_check_returns_up_to_date_when_hash_matches(): void
    {
        $partial = \Mockery::mock(Egg::class)->makePartial();
        $sameHash = hash('sha256', 'same-content');
        $partial->forceFill([
            'update_url' => 'https://example.com/egg.json',
            'last_update_hash' => $sameHash,
            'applied_update_hash' => $sameHash,
            'id' => 1,
        ]);
        $partial->shouldReceive('save')->once()->andReturn(true);

        Http::fake([
            'example.com/*' => Http::response('same-content', 200),
        ]);

        $result = $this->service->check($partial);

        $this->assertSame('up_to_date', $result['status']);
    }

    public function test_check_returns_update_available_with_diff(): void
    {
        $body = json_encode($this->sampleEggJson);

        $partial = \Mockery::mock(Egg::class)->makePartial();
        $partial->forceFill([
            'update_url' => 'https://example.com/egg.json',
            'id' => 1,
            'name' => 'Old Egg Name',
            'description' => 'Old description',
            'startup' => 'old command',
            'features' => null,
            'docker_images' => ['old/image:latest' => 'old/image:latest'],
            'config_files' => null,
            'config_startup' => null,
            'config_logs' => null,
            'config_stop' => null,
            'script_install' => null,
            'script_entry' => null,
            'script_container' => null,
            'script_is_privileged' => null,
            // ponytail: a baseline hash distinct from the remote body's hash so
            // the check reports update_available instead of treating this as the
            // first-ever baseline check.
            'applied_update_hash' => hash('sha256', 'previously-applied-baseline'),
        ]);

        // Egg has a ->variables relation that returns empty collection
        $partial->setRelation('variables', collect());
        $partial->shouldReceive('save')->once()->andReturn(true);

        $this->parser->shouldReceive('parseJsonString')
            ->once()
            ->with($body)
            ->andReturn($this->sampleEggJson);

        Http::fake([
            'example.com/*' => Http::response($body, 200),
        ]);

        $result = $this->service->check($partial);

        $this->assertSame('update_available', $result['status']);
        $this->assertArrayHasKey('name', $result['diff']);
        $this->assertArrayHasKey('description', $result['diff']);
        $this->assertArrayHasKey('startup', $result['diff']);
    }

    public function test_apply_updates_egg_with_overrides(): void
    {
        $body = json_encode($this->sampleEggJson);
        $varsMock = \Mockery::mock('Illuminate\Database\Eloquent\Relations\HasMany');
        $varsMock->shouldReceive('updateOrCreate')->times(0); // no variables in sample
        $varsMock->shouldReceive('whereNotIn')->andReturnSelf();
        $varsMock->shouldReceive('delete')->once()->andReturn(true);

        $egg = \Mockery::mock(Egg::class)->makePartial();
        $egg->forceFill([
            'id' => 1,
            'update_url' => 'https://example.com/egg.json',
            'name' => 'Old Name',
            'description' => 'Old desc',
            'update_overrides' => ['name' => 'Pinned Name', 'description' => 'Pinned desc'],
            'startup' => 'old',
            'docker_images' => ['old' => 'old'],
            'exclude_from_updates' => false,
            'last_update_hash' => null,
            'last_etag' => null,
            'last_modified' => null,
            'last_update_check_at' => null,
            'last_update_applied_at' => null,
        ]);
        // Override variables() relationship to return mock
        $egg->shouldReceive('variables')->andReturn($varsMock);
        $egg->shouldReceive('save')->once()->andReturn(true);
        $egg->shouldReceive('refresh')->once()->andReturnSelf();

        $this->parser->shouldReceive('parseJsonString')
            ->once()
            ->with($body)
            ->andReturn($this->sampleEggJson);

        $this->parser->shouldReceive('fillFromParsed')
            ->once()
            ->andReturnUsing(function (Egg $e, array $p) {
                return $e->forceFill([
                    'name' => $p['name'],
                    'description' => $p['description'],
                    'update_url' => $p['meta']['update_url'],
                    'startup' => $p['startup'],
                    'docker_images' => $p['docker_images'],
                ]);
            });

        $this->connection->shouldReceive('transaction')
            ->once()
            ->andReturnUsing(function (callable $callback) {
                return $callback();
            });

        Http::fake([
            'example.com/*' => Http::response($body, 200),
        ]);

        $result = $this->service->apply($egg);

        $this->assertSame('Pinned Name', $result->name);
        $this->assertSame('Pinned desc', $result->description);
    }

    public function test_check_rejects_disallowed_host(): void
    {
        config(['app.allowed_egg_hosts' => 'good.com']);

        $egg = new Egg(['update_url' => 'https://evil.com/egg.json', 'id' => 1]);

        $result = $this->service->check($egg);

        $this->assertSame('error', $result['status']);
        $this->assertStringContainsString('ALLOWED_EGG_HOSTS', $result['error']);

        config(['app.allowed_egg_hosts' => '']);
    }

    public function test_check_all_returns_empty_when_disabled(): void
    {
        $this->settings->shouldReceive('get')
            ->with('egg-updater:enabled', false)
            ->andReturn('0');

        $result = $this->service->checkAll();

        $this->assertTrue($result->isEmpty());
    }

    public function test_apply_throws_on_empty_url(): void
    {
        $egg = Egg::factory()->make(['update_url' => '', 'id' => 1]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No update URL configured');

        $this->service->apply($egg);
    }

    public function test_apply_throws_on_disallowed_host(): void
    {
        config(['app.allowed_egg_hosts' => 'good.com']);
        $egg = Egg::factory()->make(['update_url' => 'https://evil.com/egg.json', 'id' => 1]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('ALLOWED_EGG_HOSTS');

        $this->service->apply($egg);
        config(['app.allowed_egg_hosts' => '']);
    }

    public function test_validate_url_rejects_invalid_scheme(): void
    {
        config(['app.allowed_egg_hosts' => '']);
        $egg = new Egg(['update_url' => 'ftp://example.com/egg.json', 'id' => 1]);

        Http::fake();

        $result = $this->service->check($egg);

        $this->assertSame('error', $result['status']);
        $this->assertStringContainsString('Invalid update URL', $result['error']);
    }
}
