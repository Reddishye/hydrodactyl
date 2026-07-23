<?php

namespace Pterodactyl\Console\Commands\Eggs;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Pterodactyl\Models\Egg;
use Pterodactyl\Services\Eggs\EggUpdaterService;
use Pterodactyl\Contracts\Repository\SettingsRepositoryInterface;

class CheckEggUpdatesCommand extends Command
{
    protected $signature = 'egg:check-updates
        {--apply : Apply updates automatically (overrides settings)}
        {--egg= : Check specific egg ID only}
        {--dry-run : Preview only, no changes applied}';

    protected $description = 'Check all eggs with update_url for available updates';

    public function __construct(
        protected EggUpdaterService $updaterService,
        protected SettingsRepositoryInterface $settings,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $eggId = $this->option('egg');
        $apply = $this->option('apply');
        $dryRun = $this->option('dry-run');

        if ($dryRun && $apply) {
            $this->warn('--dry-run overrides --apply. Running in dry-run mode.');
            $apply = false;
        }

        if (!$eggId) {
            $enabled = filter_var($this->settings->get('egg-updater:enabled', false), FILTER_VALIDATE_BOOLEAN);
            if (!$enabled) {
                $this->info('Egg updater is disabled via settings. Skipping.');
                return static::SUCCESS;
            }

            $frequencyHours = match ($this->settings->get('egg-updater:frequency', 'daily')) {
                'every_6_hours' => 6,
                'every_12_hours' => 12,
                default => 24,
            };

            $oldestCheck = Egg::whereNotNull('update_url')
                ->where('update_url', '!=', '')
                ->where('exclude_from_updates', false)
                ->min('last_update_check_at');

            if ($oldestCheck !== null) {
                $hoursSinceOldest = Carbon::now()->diffInHours($oldestCheck, false);
                if ($hoursSinceOldest < $frequencyHours) {
                    $this->info("Frequency: every {$frequencyHours}h. Last check was " . round($hoursSinceOldest) . "h ago. Next check due in " . ($frequencyHours - round($hoursSinceOldest)) . 'h. Skipping.');
                    return static::SUCCESS;
                }
            }

            if (!$apply) {
                $autoApply = filter_var($this->settings->get('egg-updater:auto-apply', false), FILTER_VALIDATE_BOOLEAN);
                if ($autoApply) {
                    $apply = true;
                }
            }
        }

        if ($eggId) {
            $egg = Egg::find($eggId);
            if (!$egg) {
                $this->error("Egg with ID {$eggId} not found.");
                return static::FAILURE;
            }
            $results = collect([$this->updaterService->check($egg)]);
        } else {
            $results = $this->updaterService->checkAll();
        }

        if ($results->isEmpty()) {
            $this->info('No eggs to check (auto-updates disabled or no eggs with update_url).');
            return static::SUCCESS;
        }

        $headers = ['ID', 'Name', 'Status', 'Detail'];
        $rows = [];
        $hasUpdates = false;
        $errors = false;

        foreach ($results as $result) {
            $egg = $result['egg'];
            $status = match ($result['status']) {
                'up_to_date' => '<fg=green>✓ Up to date</>',
                'update_available' => '<fg=yellow>⚠ Update avail.</>',
                default => '<fg=red>✗ Error</>',
            };

            $detail = match ($result['status']) {
                'up_to_date' => '',
                'update_available' => implode(', ', array_keys($result['diff'])),
                default => $result['error'] ?? 'Unknown error',
            };

            $rows[] = [$egg->id, $egg->name, $status, $detail];

            if ($result['status'] === 'update_available') {
                $hasUpdates = true;
                if ($apply && !$dryRun) {
                    try {
                        $this->updaterService->apply($egg);
                        $this->info("  → Applied update for {$egg->name}");
                    } catch (\Throwable $e) {
                        $this->error("  → Failed to apply update for {$egg->name}: {$e->getMessage()}");
                        $errors = true;
                    }
                }
            }

            if ($result['status'] === 'error') {
                $errors = true;
            }
        }

        $this->table($headers, $rows);

        if ($dryRun && $hasUpdates) {
            $this->warn('Dry-run mode: no changes applied. Use --apply to update.');
        }

        if ($hasUpdates && !$apply && !$dryRun) {
            $this->warn('Updates available. Run with --apply to apply, or use admin panel.');
        }

        return $errors ? static::FAILURE : static::SUCCESS;
    }
}
