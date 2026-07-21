<?php

namespace Pterodactyl\Http\Controllers\Admin\Settings;

use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Prologue\Alerts\AlertsMessageBag;
use Illuminate\View\Factory as ViewFactory;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Models\Egg;
use Pterodactyl\Contracts\Repository\SettingsRepositoryInterface;
use Pterodactyl\Services\Eggs\EggUpdaterService;

class EggUpdaterController extends Controller
{
    public function __construct(
        private AlertsMessageBag $alert,
        private SettingsRepositoryInterface $settings,
        private ViewFactory $view,
        private EggUpdaterService $updaterService,
    ) {}

    public function index(): View
    {
        return $this->view->make('admin.settings.egg-updater', [
            'enabled' => $this->settings->get('egg-updater:enabled', '0'),
            'frequency' => $this->settings->get('egg-updater:frequency', 'manual'),
            'auto_apply' => $this->settings->get('egg-updater:auto-apply', '0'),
            'notify' => $this->settings->get('egg-updater:notify', '0'),
            'eggs' => Egg::query()
                ->whereNotNull('update_url')
                ->where('update_url', '!=', '')
                ->get(),
            'unallowedEggs' => $this->updaterService->getDisallowedUrlEggs(),
            'allowedHosts' => config('app.allowed_egg_hosts', ''),
        ]);
    }

    public function update(): RedirectResponse
    {
        $keys = [
            'egg-updater:enabled',
            'egg-updater:frequency',
            'egg-updater:auto-apply',
            'egg-updater:notify',
        ];

        foreach ($keys as $key) {
            $inputKey = str_replace(':', '_', $key);
            $value = request()->input($inputKey);

            if ($key === 'egg-updater:enabled' || $key === 'egg-updater:auto-apply' || $key === 'egg-updater:notify') {
                $value = $value ? '1' : '0';
            }

            $this->settings->set($key, $value ?? '');
        }

        $this->alert->success('Egg updater settings have been updated.')->flash();

        return redirect()->route('admin.settings.egg-updater');
    }
}
