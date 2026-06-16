<?php

namespace Pterodactyl\Repositories\Eloquent;

use Pterodactyl\Models\S3;
use Pterodactyl\Contracts\Repository\S3RepositoryInterface;

class S3Repository extends EloquentRepository implements S3RepositoryInterface
{
    /**
     * Return the model backing this repository.
     */
    public function model(): string
    {
        return S3::class;
    }
}
