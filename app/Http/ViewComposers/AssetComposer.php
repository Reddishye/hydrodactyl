<?php

namespace Pterodactyl\Http\ViewComposers;

use Illuminate\View\View;
use Illuminate\Support\Facades\Log;
use Pterodactyl\Services\Helpers\AssetHashService;
use Pterodactyl\Services\Captcha\CaptchaManager;
use Pterodactyl\Contracts\Repository\SettingsRepositoryInterface;

class AssetComposer
{
  protected CaptchaManager $captcha;
  protected SettingsRepositoryInterface $settings;

  public function __construct(CaptchaManager $captcha, SettingsRepositoryInterface $settings)
  {
    $this->captcha = $captcha;
    $this->settings = $settings;
  }

  /**
   * Provide access to the asset service in the views.
   */
  public function compose(View $view): void
  {
    $logoType = config('app.logo.type');
    $logoValue = config('app.logo.value');
    $logoUrl = match ($logoType) {
      'upload' => $logoValue ? url('storage/' . $logoValue) : null,
      'link' => $logoValue,
      default => null,
    };

    $view->with('siteConfiguration', [
      'name' => config('app.name') ?? 'Hydrodactyl',
      'locale' => config('app.locale') ?? 'en',
      'timezone' => config('app.timezone') ?? '',
      'logo' => $logoUrl,
      'captcha' => [
        'enabled' => $this->captcha->getDefaultDriver() !== 'none',
        'provider' => $this->captcha->getDefaultDriver(),
        'siteKey' => $this->getSiteKeyForCurrentProvider(),
        'scriptIncludes' => $this->captcha->getScriptIncludes(),
      ],
    ]);
  }

  /**
   * Get the site key for the currently active captcha provider.
   */
  private function getSiteKeyForCurrentProvider(): string
  {
    $provider = $this->captcha->getDefaultDriver();

    if ($provider === 'none') {
      return '';
    }

    try {
      $driver = $this->captcha->driver();
      if (method_exists($driver, 'getSiteKey')) {
        return $driver->getSiteKey();
      }
    } catch (\Exception $e) {
      // Silently fail to avoid exposing errors to frontend
    }

    return '';
  }
}
