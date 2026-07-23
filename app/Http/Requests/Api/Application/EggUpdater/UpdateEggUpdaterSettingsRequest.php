<?php

namespace Pterodactyl\Http\Requests\Api\Application\EggUpdater;

use Pterodactyl\Services\Acl\Api\AdminAcl;
use Pterodactyl\Http\Requests\Api\Application\ApplicationApiRequest;

class UpdateEggUpdaterSettingsRequest extends ApplicationApiRequest
{
    protected ?string $resource = AdminAcl::RESOURCE_EGGS;

    protected int $permission = AdminAcl::WRITE;

    public function rules(): array
    {
        return [
            'egg_updater_enabled' => ['sometimes', 'in:0,1'],
            'egg_updater_frequency' => ['sometimes', 'string'],
            'egg_updater_auto_apply' => ['sometimes', 'in:0,1'],
        ];
    }
}