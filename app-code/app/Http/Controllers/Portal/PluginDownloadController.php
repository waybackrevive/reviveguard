<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\File;
use ZipArchive;

/**
 * Serves the ReviveGuard WordPress agent plugin as a zip for self-service install.
 */
class PluginDownloadController extends Controller
{
    public function __invoke(): Response
    {
        $customUrl = config('services.reviveguard.plugin_download_url');
        if ($customUrl) {
            return redirect()->away($customUrl);
        }

        $pluginPath = config('services.reviveguard.plugin_path');

        if (! is_dir($pluginPath)) {
            abort(404, 'Plugin package is not available on this server.');
        }

        $zipPath = storage_path('app/temp/reviveguard-agent.zip');
        File::ensureDirectoryExists(dirname($zipPath));
        File::delete($zipPath);

        $zip = new ZipArchive;
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            abort(500, 'Could not create plugin package.');
        }

        $folderName = 'reviveguard-agent';
        foreach (File::allFiles($pluginPath) as $file) {
            $relative = $folderName . '/' . $file->getRelativePathname();
            $zip->addFile($file->getPathname(), str_replace('\\', '/', $relative));
        }
        $zip->close();

        return response()->download($zipPath, 'reviveguard-agent.zip', [
            'Content-Type' => 'application/zip',
        ])->deleteFileAfterSend(true);
    }
}
