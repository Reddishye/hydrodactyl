<?php

namespace Pterodactyl\Services\Eggs;

use Carbon\Carbon;
use Illuminate\Support\Arr;
use Pterodactyl\Models\Egg;
use Pterodactyl\Models\EggVariable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\ConnectionInterface;
use Pterodactyl\Contracts\Repository\SettingsRepositoryInterface;

class EggUpdaterService
{
    private const SETTINGS_PREFIX = 'egg-updater:';

    public function __construct(
        protected ConnectionInterface $connection,
        protected EggParserService $parser,
        protected SettingsRepositoryInterface $settings,
    ) {}

    /**
     * Get eggs with disallowed update URLs per ALLOWED_EGG_HOSTS.
     *
     * @return Collection<int, Egg>
     */
    public function getDisallowedUrlEggs(): Collection
    {
        $allowedHosts = $this->getAllowedHosts();
        if (empty($allowedHosts)) {
            // No restriction — everything allowed
            return collect();
        }

        $eggs = Egg::query()
            ->whereNotNull('update_url')
            ->where('update_url', '!=', '')
            ->get();

        return $eggs->filter(function (Egg $egg) use ($allowedHosts) {
            $host = parse_url($egg->update_url, PHP_URL_HOST);
            return $host === false || $host === null || !in_array($host, $allowedHosts, true);
        })->values();
    }

    /**
     * Check all eligible eggs for updates.
     *
     * @return Collection<int, array{egg: Egg, status: string, diff: array, error: ?string}>
     */
    public function checkAll(): Collection
    {
        if (!filter_var($this->settings->get(self::SETTINGS_PREFIX . 'enabled', false), FILTER_VALIDATE_BOOLEAN)) {
            return collect();
        }

        $eggs = Egg::query()
            ->whereNotNull('update_url')
            ->where('update_url', '!=', '')
            ->where('exclude_from_updates', false)
            ->get();

        return $eggs->map(fn (Egg $egg) => $this->check($egg));
    }

    /**
     * Check a single egg for updates using conditional HTTP + SHA256.
     *
     * @return array{egg: Egg, status: string, diff: array, error: ?string, parsed: ?array}
     */
    public function check(Egg $egg): array
    {
        $result = [
            'egg' => $egg,
            'status' => 'error',
            'diff' => [],
            'error' => null,
            'parsed' => null,
        ];

        try {
            $url = $egg->update_url;
            if (empty($url)) {
                $result['error'] = 'No update URL configured';
                return $result;
            }

            if (!$this->isUrlAllowed($url)) {
                $result['error'] = 'Update URL host is not in ALLOWED_EGG_HOSTS';
                return $result;
            }

            $this->validateUrl($url);

            $headers = [];
            if ($egg->last_etag) {
                $headers['If-None-Match'] = $egg->last_etag;
            }
            if ($egg->last_modified) {
                $headers['If-Modified-Since'] = $egg->last_modified;
            }

            $response = Http::withHeaders($headers)
                ->timeout(30)
                ->get($url);

            if ($response->status() === 304) {
                $egg->forceFill(['last_update_check_at' => Carbon::now()])->save();
                $result['status'] = 'up_to_date';
                return $result;
            }

            if ($response->failed()) {
                $result['error'] = "HTTP {$response->status()}: {$response->reason()}";
                Log::warning('Egg update check failed', ['egg_id' => $egg->id, 'error' => $result['error']]);
                return $result;
            }

            $body = $response->body();
            $hash = hash('sha256', $body);

            if ($hash === $egg->last_update_hash) {
                $egg->forceFill([
                    'last_update_check_at' => Carbon::now(),
                    'last_etag' => $response->header('ETag'),
                    'last_modified' => $response->header('Last-Modified'),
                ])->save();
                $result['status'] = 'up_to_date';
                return $result;
            }

            $parsed = $this->parser->parseJsonString($body);
            $diff = $this->computeDiff($egg, $parsed);

            $egg->forceFill([
                'last_update_hash' => $hash,
                'last_etag' => $response->header('ETag'),
                'last_modified' => $response->header('Last-Modified'),
                'last_update_check_at' => Carbon::now(),
            ])->save();

            if (empty($diff)) {
                $result['status'] = 'up_to_date';
                return $result;
            }

            Log::info('Egg update available', ['egg_id' => $egg->id, 'diff' => $diff]);
            $result['status'] = 'update_available';
            $result['diff'] = $diff;
            $result['parsed'] = $parsed;

            return $result;
        } catch (\Throwable $e) {
            $result['error'] = $e->getMessage();
            Log::warning('Egg update check exception', ['egg_id' => $egg->id, 'error' => $e->getMessage()]);
            return $result;
        }
    }

    /**
     * Apply an update to an egg. Re-fetches the update URL.
     *
     * @throws \Throwable
     */
    public function apply(Egg $egg): Egg
    {
        $url = $egg->update_url;
        if (empty($url)) {
            throw new \RuntimeException('No update URL configured for this egg.');
        }

        if (!$this->isUrlAllowed($url)) {
            throw new \RuntimeException('Update URL host is not in ALLOWED_EGG_HOSTS.');
        }

        $this->validateUrl($url);

        $body = Http::timeout(30)->get($url)->body();
        $parsed = $this->parser->parseJsonString($body);
        $hash = hash('sha256', $body);

        return $this->connection->transaction(function () use ($egg, $parsed, $hash) {
            // Capture overrides BEFORE fillFromParsed overwrites
            $overrides = $egg->update_overrides ?? [];

            $egg = $this->parser->fillFromParsed($egg, $parsed);

            // Apply overrides: name, description, update_url
            $overrideName = $overrides['name'] ?? null;
            $overrideDesc = $overrides['description'] ?? null;
            $overrideUrl = $overrides['update_url'] ?? null;

            $egg->forceFill([
                'name' => $overrideName ?? $egg->name,
                'description' => $overrideDesc ?? $egg->description,
                'update_url' => $overrideUrl ?? $egg->update_url,
                'last_update_hash' => $hash,
                'last_etag' => null,
                'last_modified' => null,
                'last_update_applied_at' => Carbon::now(),
                'last_update_check_at' => Carbon::now(),
                'exclude_from_updates' => $egg->exclude_from_updates,
            ]);
            $egg->save();

            // Sync variables
            foreach ($parsed['variables'] ?? [] as $variable) {
                $egg->variables()->updateOrCreate(
                    ['env_variable' => $variable['env_variable']],
                    Collection::make($variable)->except('egg_id', 'env_variable')->toArray(),
                );
            }

            $imported = array_map(fn ($v) => $v['env_variable'], $parsed['variables'] ?? []);
            $egg->variables()->whereNotIn('env_variable', $imported)->delete();

            Log::info('Egg update applied', ['egg_id' => $egg->id, 'hash' => $hash]);

            return $egg->refresh();
        });
    }

    /**
     * Validate URL format.
     *
     * @throws \RuntimeException
     */
    private function validateUrl(string $url): void
    {
        $parsed = parse_url($url);
        if ($parsed === false || !in_array($parsed['scheme'] ?? '', ['http', 'https'], true)) {
            throw new \RuntimeException("Invalid update URL: {$url}");
        }
    }

    /**
     * Compute diff between current egg and parsed remote egg data.
     */
    private function computeDiff(Egg $egg, array $parsed): array
    {
        $diff = [];

        $fields = [
            'name' => 'name',
            'description' => 'description',
            'features' => 'features',
            'docker_images' => 'docker_images',
            'startup' => 'startup',
            'config_files' => 'config.files',
            'config_startup' => 'config.startup',
            'config_logs' => 'config.logs',
            'config_stop' => 'config.stop',
            'script_install' => 'scripts.installation.script',
            'script_entry' => 'scripts.installation.entrypoint',
            'script_container' => 'scripts.installation.container',
            'script_is_privileged' => 'scripts.installation.is_privileged',
        ];

        foreach ($fields as $eggField => $parsedKey) {
            $current = $egg->$eggField;
            $incoming = Arr::get($parsed, $parsedKey);

            if ($eggField === 'features' || $eggField === 'docker_images') {
                $current = $this->sortedJson($current);
                $incoming = $this->sortedJson($incoming ? (array) $incoming : null);
            }

            if ($current !== $incoming) {
                $diff[$eggField] = [
                    'from' => $current,
                    'to' => $incoming,
                ];
            }
        }

        // Compare variables
        $currentVars = $egg->variables->keyBy('env_variable')->toArray();
        $incomingVars = [];
        foreach ($parsed['variables'] ?? [] as $v) {
            $incomingVars[$v['env_variable']] = $v;
        }

        $varChanges = [];
        foreach ($incomingVars as $envVar => $v) {
            $existing = $currentVars[$envVar] ?? null;
            if (!$existing) {
                $varChanges['added'][] = $envVar;
            } elseif ($existing['default_value'] !== ($v['default_value'] ?? null)
                || $existing['rules'] !== ($v['rules'] ?? null)
                || $existing['user_viewable'] !== ($v['user_viewable'] ?? null)
                || $existing['user_editable'] !== ($v['user_editable'] ?? null)) {
                $varChanges['modified'][] = $envVar;
            }
        }
        foreach ($currentVars as $envVar => $v) {
            if (!isset($incomingVars[$envVar])) {
                $varChanges['removed'][] = $envVar;
            }
        }

        if (!empty($varChanges)) {
            $diff['variables'] = $varChanges;
        }

        return $diff;
    }

    /**
     * JSON-encode with sorted keys for stable comparison.
     */
    private function sortedJson(mixed $value): ?string
    {
        if (empty($value)) {
            return null;
        }
        ksort($value);
        return json_encode($value);
    }

    /**
     * Check if a URL's host is allowed.
     */
    private function isUrlAllowed(string $url): bool
    {
        $allowedHosts = $this->getAllowedHosts();
        if (empty($allowedHosts)) {
            return true;
        }

        $host = parse_url($url, PHP_URL_HOST);
        return $host !== false && $host !== null && in_array($host, $allowedHosts, true);
    }

    /**
     * Parse ALLOWED_EGG_HOSTS env into array.
     */
    private function getAllowedHosts(): array
    {
        $raw = env('ALLOWED_EGG_HOSTS', '');
        if (empty($raw)) {
            return [];
        }

        return array_filter(array_map('trim', explode(',', $raw)));
    }
}
