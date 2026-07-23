<?php

namespace Pterodactyl\Http\Controllers\Admin\Settings;

use Cron\CronExpression;
use Illuminate\View\View;
use Pterodactyl\Models\Egg;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\RedirectResponse;
use Prologue\Alerts\AlertsMessageBag;
use Illuminate\View\Factory as ViewFactory;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Services\Eggs\EggUpdaterService;
use Pterodactyl\Contracts\Repository\SettingsRepositoryInterface;

class EggUpdaterController extends Controller
{
    public function __construct(
        private AlertsMessageBag $alert,
        private SettingsRepositoryInterface $settings,
        private ViewFactory $view,
        private EggUpdaterService $updaterService,
    ) {
    }

    public function index(): View
    {
        $frequency = $this->settings->get('egg-updater:frequency', 'manual');
        if ($frequency === '') {
            $frequency = 'manual';
        }

        $eggs = Egg::query()
            ->whereNotNull('update_url')
            ->where('update_url', '!=', '')
            ->get();

        $stats = [
            'total' => $eggs->count(),
            'ok' => 0,
            'errors' => 0,
            'pending' => 0,
            'excluded' => 0,
        ];
        foreach ($eggs as $egg) {
            if ($egg->exclude_from_updates) {
                ++$stats['excluded'];
            } elseif ($egg->last_update_error) {
                ++$stats['errors'];
            } elseif (!$egg->last_update_check_at) {
                ++$stats['pending'];
            } else {
                ++$stats['ok'];
            }
        }

        return $this->view->make('admin.settings.egg-updater', [
            'enabled' => $this->settings->get('egg-updater:enabled', '0'),
            'frequency' => $frequency,
            'auto_apply' => $this->settings->get('egg-updater:auto-apply', '0'),
            'eggs' => $eggs,
            'stats' => $stats,
            'unallowedEggs' => $this->updaterService->getDisallowedUrlEggs(),
            'allowedHosts' => config('app.allowed_egg_hosts', ''),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $all = $request->request->all();
        $enabled = ($all['egg_updater_enabled'] ?? '0') ? '1' : '0';
        $autoApply = ($all['egg_updater_auto_apply'] ?? '0') ? '1' : '0';
        $frequency = trim($all['egg_updater_frequency'] ?? '');
        $errors = [];

        if ($frequency === '' && $enabled === '1') {
            $errors[] = 'A cron expression is required when the auto-updater is enabled.';
        } elseif ($frequency !== '' && !CronExpression::isValidExpression($frequency)) {
            $errors[] = 'Invalid cron expression.';
            $frequency = $this->settings->get('egg-updater:frequency', '');
        }

        $this->settings->set('egg-updater:enabled', $enabled);
        $this->settings->set('egg-updater:auto-apply', $autoApply);

        if (empty($errors)) {
            $this->settings->set('egg-updater:frequency', $frequency);
        }

        if (!empty($errors)) {
            $this->alert->warning(implode(' ', $errors))->flash();
        } else {
            $this->alert->success('Egg updater settings updated.')->flash();
        }

        return redirect()->route('admin.settings.egg-updater');
    }

    public function checkAll(): JsonResponse
    {
        $results = $this->updaterService->checkAll();

        $data = $results->map(fn (array $r) => [
            'egg_id' => $r['egg']->id,
            'status' => $r['status'],
            'error' => $r['error'],
            'last_update_check_at' => $r['egg']->last_update_check_at?->diffForHumans(),
            'last_update_error' => $r['egg']->last_update_error,
        ]);

        return response()->json(['checked' => $data]);
    }

    public function check(Request $request, Egg $egg): JsonResponse
    {
        if ($this->settings->get('egg-updater:enabled', '0') !== '1') {
            return response()->json([
                'egg_id' => $egg->id,
                'status' => 'error',
                'error' => 'Egg updater is disabled.',
            ], 422);
        }

        $result = $this->updaterService->check($egg);

        return response()->json([
            'egg_id' => $egg->id,
            'status' => $result['status'],
            'error' => $result['error'],
            'last_update_check_at' => $egg->fresh()->last_update_check_at?->diffForHumans(),
            'last_update_error' => $egg->fresh()->last_update_error,
        ]);
    }

    public function apply(Request $request, Egg $egg): JsonResponse
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

    public function toggleExclude(Request $request, Egg $egg): JsonResponse
    {
        $excluded = !$egg->exclude_from_updates;
        $egg->update(['exclude_from_updates' => $excluded]);

        return response()->json([
            'egg_id' => $egg->id,
            'excluded' => $excluded,
        ]);
    }
}
