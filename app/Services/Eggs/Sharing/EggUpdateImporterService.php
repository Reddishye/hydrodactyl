<?php

namespace Pterodactyl\Services\Eggs\Sharing;

use Pterodactyl\Models\Egg;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Pterodactyl\Models\EggVariable;
use Illuminate\Database\ConnectionInterface;
use Pterodactyl\Services\Eggs\EggParserService;

class EggUpdateImporterService
{
    /**
     * EggUpdateImporterService constructor.
     */
    public function __construct(protected ConnectionInterface $connection, protected EggParserService $parser)
    {
    }

    /**
     * Update an existing Egg using an uploaded JSON file.
     *
     * @throws \Pterodactyl\Exceptions\Service\InvalidFileUploadException|\Throwable
     */
    public function handle(Egg $egg, UploadedFile $file): Egg
    {
        $parsed = $this->parser->handle($file);

        return $this->connection->transaction(function () use ($egg, $parsed) {
            $egg = $this->parser->fillFromParsed($egg, $parsed);
            $egg->applied_update_hash = null;
            $egg->last_update_hash = null;
            $egg->last_etag = null;
            $egg->last_modified = null;
            $egg->last_update_error = null;
            $egg->last_update_check_at = null;
            $egg->last_update_applied_at = null;
            $egg->save();

            // Update existing variables or create new ones.
            foreach ($parsed['variables'] ?? [] as $variable) {
                EggVariable::unguarded(function () use ($egg, $variable) {
                    $egg->variables()->updateOrCreate([
                        'env_variable' => $variable['env_variable'],
                    ], Collection::make($variable)->except('egg_id', 'env_variable')->toArray());
                });
            }

            $imported = array_map(fn ($value) => $value['env_variable'], $parsed['variables'] ?? []);

            $egg->variables()->whereNotIn('env_variable', $imported)->delete();

            return $egg->refresh();
        });
    }
}
