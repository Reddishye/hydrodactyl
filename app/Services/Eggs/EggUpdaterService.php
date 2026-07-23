<?php

namespace Pterodactyl\Services\Eggs;

use Carbon\Carbon;
use Pterodactyl\Models\Egg;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Database\ConnectionInterface;
use Pterodactyl\Exceptions\Service\InvalidFileUploadException;
use Pterodactyl\Contracts\Repository\SettingsRepositoryInterface;

class EggUpdaterService
{
    public function __construct(
        protected ConnectionInterface $connection,
        protected EggParserService $parser,
        protected SettingsRepositoryInterface $settings,
    ) {
    }

    /**
     * Get eggs with disallowed update URLs per ALLOWED_EGG_HOSTS.
     *
     * @return Collection<int, Egg>
     */
    public function getDisallowedUrlEggs(): Collection
    {
        $allowedHosts = $this->getAllowedHosts();
        if (empty($allowedHosts)) {
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
        if ($this->settings->get('egg-updater:enabled', false) !== '1') {
            return collect();
        }

        $eggs = Egg::query()
            ->whereNotNull('update_url')
            ->where('update_url', '!=', '')
            ->where('exclude_from_updates', false)
            ->get()
            ->filter(fn (Egg $egg) => $this->isUrlAllowed($egg->update_url));

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
                $this->persistUpdateMeta($egg, ['last_update_error' => $result['error']]);

                return $result;
            }

            if (!$this->isUrlAllowed($url)) {
                $result['error'] = 'Update URL host is not in ALLOWED_EGG_HOSTS';
                $this->persistUpdateMeta($egg, ['last_update_error' => $result['error']]);

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
                // Remote hasn't changed since last check. But there may be a
                // pending update the user never applied (applied_update_hash
                // may still differ from last_update_hash from a prior check).
                $this->persistUpdateMeta($egg, ['last_update_error' => null]);
                $pending = $egg->applied_update_hash && $egg->last_update_hash
                    && $egg->applied_update_hash !== $egg->last_update_hash;
                $result['status'] = $pending ? 'update_available' : 'up_to_date';

                return $result;
            }

            if ($response->failed()) {
                $result['error'] = "HTTP {$response->status()}: {$response->reason()}";
                Log::warning('Egg update check failed', ['egg_id' => $egg->id, 'error' => $result['error']]);
                $this->persistUpdateMeta($egg, ['last_update_error' => $result['error']]);

                return $result;
            }

            $body = $response->body();
            $hash = hash('sha256', $body);

            // ponytail: status is decided solely by comparing the remote hash
            // against applied_update_hash (the hash of the last applied remote
            // body). last_update_hash is always refreshed so the "latest seen"
            // value is persisted, but it never drives the status decision —
            // that was the original bug (a seen-but-not-applied hash flipping
            // status to up_to_date on the next reload).
            if ($egg->applied_update_hash === null) {
                // First check or never applied: treat current remote as the
                // applied baseline so update_available only fires once the
                // remote actually changes from this point forward.
                $this->persistUpdateMeta($egg, [
                    'applied_update_hash' => $hash,
                    'last_update_hash' => $hash,
                    'last_etag' => $response->header('ETag'),
                    'last_modified' => $response->header('Last-Modified'),
                    'last_update_error' => null,
                ]);
                $result['status'] = 'up_to_date';

                return $result;
            }

            if ($hash === $egg->applied_update_hash) {
                $this->persistUpdateMeta($egg, [
                    'last_update_hash' => $hash,
                    'last_etag' => $response->header('ETag'),
                    'last_modified' => $response->header('Last-Modified'),
                    'last_update_error' => null,
                ]);
                $result['status'] = 'up_to_date';

                return $result;
            }

            try {
                $parsed = $this->parser->parseJsonString($body);
            } catch (\JsonException $e) {
                $result['error'] = 'Invalid JSON response from update URL: the remote source did not return valid egg data.';
                Log::warning('Egg update check: invalid JSON', ['egg_id' => $egg->id, 'url' => $url, 'body_preview' => substr($body, 0, 200)]);
                $this->persistUpdateMeta($egg, ['last_update_error' => 'Update check failed']);

                return $result;
            } catch (InvalidFileUploadException $e) {
                $result['error'] = 'Invalid egg format - the remote source returned valid JSON but missing PTDL_v1/PTDL_v2 meta.version.';
                Log::warning('Egg update check: invalid format', ['egg_id' => $egg->id, 'url' => $url]);
                $this->persistUpdateMeta($egg, ['last_update_error' => 'Update check failed']);

                return $result;
            }
            $diff = $this->computeDiff($egg, $parsed);

            $this->persistUpdateMeta($egg, [
                'last_update_hash' => $hash,
                'last_etag' => $response->header('ETag'),
                'last_modified' => $response->header('Last-Modified'),
                'last_update_error' => null,
            ]);

            Log::info('Egg update available', ['egg_id' => $egg->id, 'diff' => $diff]);
            $result['status'] = 'update_available';
            $result['diff'] = $diff;
            $result['parsed'] = $parsed;

            // update_available already saved above with error=null
            return $result;
        } catch (\RuntimeException $e) {
            // Controlled exceptions (e.g. invalid URL) surface the message to callers,
            // but never persist raw exception text to the DB column.
            $result['error'] = $e->getMessage();
            Log::warning('Egg update check exception', ['egg_id' => $egg->id, 'error' => $e->getMessage(), 'exception' => $e]);
            $this->persistUpdateMeta($egg, ['last_update_error' => 'Update check failed']);

            return $result;
        } catch (\Throwable $e) {
            // Unexpected exceptions: log full details, but never leak raw messages to the
            // frontend JSON response or to the persisted last_update_error column.
            $result['error'] = 'Update check failed';
            Log::warning('Egg update check exception', ['egg_id' => $egg->id, 'error' => $e->getMessage(), 'exception' => $e]);
            $this->persistUpdateMeta($egg, ['last_update_error' => 'Update check failed']);

            return $result;
        }
    }

    /**
     * Best-effort persist of update-check metadata. Never throws: a failed
     * metadata write (e.g. validation on an incomplete egg in unit tests)
     * must never corrupt the caller-facing result/error message that was
     * already set. The DB column always stores a generic message; the full
     * exception, when present, is captured via Log::warning instead.
     *
     * @param array<string, mixed> $attributes
     */
    private function persistUpdateMeta(Egg $egg, array $attributes): void
    {
        $attributes['last_update_check_at'] = Carbon::now();

        try {
            $egg->forceFill($attributes)->save();
        } catch (\Throwable $e) {
            Log::warning('Egg update meta persist failed', ['egg_id' => $egg->id ?? null, 'error' => $e->getMessage(), 'exception' => $e]);
        }
    }

    /**
     * Apply an update to an egg. Re-fetches the update URL.
     *
     * @throws \Throwable
     */
    public function apply(Egg $egg): Egg
    {
        if ($egg->exclude_from_updates) {
            throw new \RuntimeException('Egg is excluded from updates.');
        }

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
                'applied_update_hash' => $hash,
                'last_etag' => null,
                'last_modified' => null,
                'last_update_error' => null,
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
            $incoming = data_get($parsed, $parsedKey);

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
        if (is_null($value)) {
            return null;
        }
        if (is_array($value)) {
            ksort($value);
        }
        $encoded = json_encode($value);

        return $encoded === false ? null : $encoded;
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
        $raw = config('app.allowed_egg_hosts', '');
        if (empty($raw)) {
            return [];
        }

        return array_filter(array_map('trim', explode(',', $raw)));
    }
}
