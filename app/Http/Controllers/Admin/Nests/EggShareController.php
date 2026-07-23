<?php

namespace Pterodactyl\Http\Controllers\Admin\Nests;

use Pterodactyl\Models\Egg;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Prologue\Alerts\AlertsMessageBag;
use Pterodactyl\Http\Controllers\Controller;
use Symfony\Component\HttpFoundation\Response;
use Pterodactyl\Services\Eggs\Sharing\EggExporterService;
use Pterodactyl\Services\Eggs\Sharing\EggImporterService;
use Pterodactyl\Http\Requests\Admin\Egg\EggImportFormRequest;
use Pterodactyl\Http\Requests\Admin\Egg\EggImportUrlFormRequest;
use Pterodactyl\Services\Eggs\Sharing\EggUpdateImporterService;
use Pterodactyl\Services\Eggs\EggUpdaterService;
use Pterodactyl\Exceptions\Model\InvalidFileUploadException;

class EggShareController extends Controller
{
    /**
     * EggShareController constructor.
     */
    public function __construct(
        protected AlertsMessageBag $alert,
        protected EggExporterService $exporterService,
        protected EggImporterService $importerService,
        protected EggUpdateImporterService $updateImporterService,
        protected EggUpdaterService $updaterService,
    ) {}

    /**
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function export(Egg $egg): Response
    {
        $filename = trim(preg_replace('/\W/', '-', kebab_case($egg->name)), '-');

        return response($this->exporterService->handle($egg->id), 200, [
            'Content-Transfer-Encoding' => 'binary',
            'Content-Description' => 'File Transfer',
            'Content-Disposition' => 'attachment; filename=egg-' . $filename . '.json',
            'Content-Type' => 'application/json',
        ]);
    }

    /**
     * Import a new service option using an XML file.
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     * @throws \Pterodactyl\Exceptions\Service\Egg\BadJsonFormatException
     * @throws \Pterodactyl\Exceptions\Service\InvalidFileUploadException
     */
    public function import(EggImportFormRequest $request): RedirectResponse
    {
        $egg = $this->importerService->handle($request->file('import_file'), $request->input('import_to_nest'));
        $this->alert->success(trans('admin/nests.eggs.notices.imported'))->flash();

        return redirect()->route('admin.nests.egg.view', ['egg' => $egg->id]);
    }

    /**
     * Import a new service option from a URL.
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     * @throws \Pterodactyl\Exceptions\Service\Egg\BadJsonFormatException
     * @throws \Pterodactyl\Exceptions\Service\InvalidFileUploadException
     */
    public function importFromUrl(EggImportUrlFormRequest $request): RedirectResponse
    {
        try {
            $allowed_hosts = array_map(function ($item) {
                return trim($item);
            }, explode(',', config('app.allowed_egg_hosts', '')));
            $parsed_url = parse_url($request->input('import_file_url'));

            if (!is_array($parsed_url) || !isset($parsed_url['host']) || !in_array($parsed_url['host'], $allowed_hosts)) {
                $this->alert->danger('The Egg import URL is not from an allowed host.')->flash();
                return redirect()->back();
            }
            if (!isset($parsed_url['scheme']) || !in_array($parsed_url['scheme'], ['http', 'https'])) {
                $this->alert->danger('The Egg import URL scheme is invalid.')->flash();
                return redirect()->back();
            }

            $response = @file_get_contents($request->input('import_file_url'));

            if ($response === false) {
                $this->alert->danger('Fetching the Egg from the URL failed.')->flash();
                return redirect()->back();
            }

            $egg = $this->importerService->handleFromString($response, $request->input('import_to_nest'));
            $this->alert->success(trans('admin/nests.eggs.notices.imported'))->flash();

            return redirect()->route('admin.nests.egg.view', ['egg' => $egg->id]);
        } catch (\Throwable $e) {
            $this->alert->danger($e->getMessage());
            return redirect()->back();
        }
    }

    /**
     * Update an existing Egg using a new imported file.
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     * @throws \Pterodactyl\Exceptions\Service\Egg\BadJsonFormatException
     * @throws \Pterodactyl\Exceptions\Service\InvalidFileUploadException
     */
    public function update(EggImportFormRequest $request, Egg $egg): RedirectResponse|JsonResponse
    {
        try {
            $this->updateImporterService->handle($egg, $request->file('import_file'));
            $msg = trans('admin/nests.eggs.notices.updated_via_import');

            if ($request->expectsJson()) {
                return response()->json(['status' => 'imported', 'message' => $msg]);
            }

            $this->alert->success($msg)->flash();
        } catch (\Throwable $e) {
            if ($request->expectsJson()) {
                return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
            }

            $this->alert->danger($e->getMessage())->flash();
        }

        return redirect()->route('admin.nests.egg.view', ['egg' => $egg]);
    }

    /**
     * Check an egg for updates from its update_url.
     */
    public function checkUpdate(Request $request, Egg $egg): RedirectResponse|JsonResponse
    {
        $result = $this->updaterService->check($egg);

        if ($result['status'] === 'update_available') {
            $names = array_keys($result['diff']);
            $msg = trans('admin/nests.eggs.notices.update_available', [
                'name' => $result['egg']->name,
                'changes' => implode(', ', $names),
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'update_available',
                    'message' => $msg,
                    'egg_id' => $result['egg']->id,
                    'diff' => $result['diff'],
                ]);
            }

            $this->alert->warning($msg)->flash();
            return back()->with('update_diff', $result['diff'])->with('update_egg_id', $result['egg']->id);
        }

        if ($result['status'] === 'up_to_date') {
            $msg = trans('admin/nests.eggs.notices.update_no_changes', [
                'name' => $result['egg']->name,
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'up_to_date',
                    'message' => $msg,
                    'egg_id' => $result['egg']->id,
                ]);
            }

            $this->alert->success($msg)->flash();
            return back();
        }

        $msg = trans('admin/nests.eggs.notices.update_check_failed', [
            'name' => $result['egg']->name,
            'error' => $result['error'] ?? 'Unknown error',
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'status' => 'error',
                'message' => $msg,
                'error' => $result['error'] ?? 'Unknown error',
                'egg_id' => $result['egg']->id,
            ], 422);
        }

        $this->alert->danger($msg)->flash();
        return back();
    }

    /**
     * Apply an available update to an egg.
     *
     * @throws \Throwable
     */
    public function applyUpdate(Request $request, Egg $egg): RedirectResponse|JsonResponse
    {
        try {
            $this->updaterService->apply($egg);
            $msg = trans('admin/nests.eggs.notices.update_applied', [
                'name' => $egg->name,
                'url' => $egg->update_url,
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'applied',
                    'message' => $msg,
                    'egg_id' => $egg->id,
                ]);
            }

            $this->alert->success($msg)->flash();
        } catch (\Throwable $e) {
            $msg = trans('admin/nests.eggs.notices.update_check_failed', [
                'name' => $egg->name,
                'error' => $e->getMessage(),
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $msg,
                    'error' => $e->getMessage(),
                    'egg_id' => $egg->id,
                ], 422);
            }

            $this->alert->danger($msg)->flash();
        }

        return redirect()->route('admin.nests.egg.view', $egg->id);
    }
}
