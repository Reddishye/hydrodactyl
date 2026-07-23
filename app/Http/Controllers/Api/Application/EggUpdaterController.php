<?php

namespace Pterodactyl\Http\Controllers\Api\Application;

use Pterodactyl\Models\Egg;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\View\Factory as ViewFactory;
use Pterodactyl\Services\Eggs\EggUpdaterService;
use Pterodactyl\Contracts\Repository\SettingsRepositoryInterface;
use Pterodactyl\Http\Requests\Api\Application\EggUpdater\ApplyEggRequest;
use Pterodactyl\Http\Requests\Api\Application\EggUpdater\CheckEggRequest;
use Pterodactyl\Http\Requests\Api\Application\EggUpdater\ToggleExcludeEggRequest;
use Pterodactyl\Http\Requests\Api\Application\EggUpdater\GetEggUpdaterSettingsRequest;
use Pterodactyl\Http\Requests\Api\Application\EggUpdater\UpdateEggUpdaterSettingsRequest;

class EggUpdaterController extends ApplicationApiController
{
    public function __construct(
        private readonly EggUpdaterService $updaterService,
        private readonly SettingsRepositoryInterface $settings,
        private readonly ViewFactory $view,
    ) {
        parent::__construct();
    }

    /**
     * Return current egg-updater settings.
     */
    public function settings(GetEggUpdaterSettingsRequest $request): JsonResponse
    {
        return response()->json([
            'enabled' => $this->settings->get('egg-updater:enabled', '0'),
            'frequency' => $this->settings->get('egg-updater:frequency', 'manual'),
            'auto_apply' => $this->settings->get('egg-updater:auto-apply', '0'),
        ]);
    }

    /**
     * Update egg-updater settings.
     */
    public function updateSettings(UpdateEggUpdaterSettingsRequest $request): JsonResponse
    {
        $data = $request->validated();

        $enabled = $data['egg_updater_enabled'] ?? $this->settings->get('egg-updater:enabled', '0');
        $this->settings->set('egg-updater:enabled', (string) $enabled);

        if (array_key_exists('egg_updater_frequency', $data)) {
            $this->settings->set('egg-updater:frequency', (string) $data['egg_updater_frequency']);
        }

        $autoApply = $data['egg_updater_auto_apply'] ?? $this->settings->get('egg-updater:auto-apply', '0');
        $this->settings->set('egg-updater:auto-apply', (string) $autoApply);

        return response()->json([
            'enabled' => $this->settings->get('egg-updater:enabled', '0'),
            'frequency' => $this->settings->get('egg-updater:frequency', 'manual'),
            'auto_apply' => $this->settings->get('egg-updater:auto-apply', '0'),
        ]);
    }

    /**
     * Check a single egg for updates.
     */
    public function check(CheckEggRequest $request, Egg $egg): JsonResponse
    {
        if ($this->settings->get('egg-updater:enabled', '0') !== '1') {
            return response()->json([
                'egg_id' => $egg->id,
                'status' => 'error',
                'error' => 'Egg updater is disabled.',
            ], 422);
        }

        $result = $this->updaterService->check($egg);
        $fresh = $egg->fresh();

        return response()->json([
            'egg_id' => $egg->id,
            'status' => $result['status'],
            'error' => $result['error'],
            'last_update_check_at' => $fresh?->last_update_check_at?->diffForHumans(),
            'last_update_error' => $fresh?->last_update_error,
        ]);
    }

    /**
     * Apply the latest update for an egg.
     */
    public function apply(ApplyEggRequest $request, Egg $egg): JsonResponse
    {
        if ($this->settings->get('egg-updater:enabled', '0') !== '1') {
            return response()->json([
                'egg_id' => $egg->id,
                'status' => 'error',
                'error' => 'Egg updater is disabled.',
            ], 422);
        }

        try {
            $egg = $this->updaterService->apply($egg);

            return response()->json([
                'egg_id' => $egg->id,
                'status' => 'applied',
                'last_update_applied_at' => $egg->last_update_applied_at?->diffForHumans(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('Egg update apply failed', ['egg_id' => $egg->id, 'error' => $e->getMessage(), 'exception' => $e]);

            return response()->json([
                'egg_id' => $egg->id,
                'status' => 'error',
                'error' => 'Apply failed.',
            ], 422);
        }
    }

    /**
     * Toggle an egg's exclude_from_updates flag.
     */
    public function toggleExclude(ToggleExcludeEggRequest $request, Egg $egg): JsonResponse
    {
        $excluded = !$egg->exclude_from_updates;
        $egg->update(['exclude_from_updates' => $excluded]);

        return response()->json([
            'egg_id' => $egg->id,
            'excluded' => $excluded,
        ]);
    }

    /**
     * Check all eligible eggs for updates.
     */
    public function checkAll(GetEggUpdaterSettingsRequest $request): JsonResponse
    {
        $results = $this->updaterService->checkAll();
        $checked = $results->map(fn (array $r) => [
            'egg_id' => $r['egg']->id,
            'status' => $r['status'],
            'error' => $r['error'],
            'last_update_check_at' => $r['egg']->last_update_check_at?->diffForHumans(),
            'last_update_error' => $r['egg']->last_update_error,
        ]);

        return response()->json(['checked' => $checked]);
    }
}
