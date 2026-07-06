<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\File;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipArchive;

/**
 * Serves the ReviveGuard WordPress agent plugin as a zip for self-service install.
 */
class PluginDownloadController extends Controller
{
    public function __invoke(): BinaryFileResponse|RedirectResponse
    {
        $customUrl = config('services.reviveguard.plugin_download_url');
        if ($customUrl) {
            return redirect()->away($customUrl);
        }

        $bundledZip = public_path('downloads/reviveguard-agent.zip');
        if (is_file($bundledZip)) {
            return response()->download($bundledZip, 'reviveguard-agent.zip', [
                'Content-Type' => 'application/zip',
            ]);
        }

        $pluginPath = $this->resolvePluginSourcePath();

        if ($pluginPath === null) {
            abort(503, 'Plugin package is not available on this server. Contact support or upload the plugin manually.');
        }

        if (! class_exists(ZipArchive::class)) {
            abort(500, 'ZipArchive PHP extension is required to build the plugin package.');
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

    private function resolvePluginSourcePath(): ?string
    {
        $candidates = array_filter([
            config('services.reviveguard.plugin_path'),
            dirname(base_path()) . DIRECTORY_SEPARATOR . 'wp-plugin' . DIRECTORY_SEPARATOR . 'reviveguard-agent',
            base_path('packages' . DIRECTORY_SEPARATOR . 'reviveguard-agent'),
        ]);

        foreach ($candidates as $path) {
            if (is_dir($path)) {
                return $path;
            }
        }

        return null;
    }
}
