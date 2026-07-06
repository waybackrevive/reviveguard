<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\File;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipArchive;

/**
 * Serves the ReviveGuard WordPress agent plugin as a zip for self-service install.
 *
 * Priority: REVIVEGUARD_PLUGIN_DOWNLOAD_URL (CDN) → public/downloads (built on deploy)
 * → on-the-fly zip from wp-plugin source in the monorepo.
 */
class PluginDownloadController extends Controller
{
    public function __invoke(): BinaryFileResponse|RedirectResponse
    {
        $customUrl = config('services.reviveguard.plugin_download_url');
        if ($customUrl) {
            return redirect()->away($customUrl);
        }

        $deployedZip = public_path('downloads/reviveguard-agent.zip');
        if (is_file($deployedZip)) {
            return response()->download($deployedZip, 'reviveguard-agent.zip', [
                'Content-Type' => 'application/zip',
            ]);
        }

        $pluginPath = $this->resolvePluginSourcePath();

        if ($pluginPath === null) {
            abort(503, 'Plugin package is not available. Set REVIVEGUARD_PLUGIN_DOWNLOAD_URL or redeploy to build the package.');
        }

        if (! class_exists(ZipArchive::class)) {
            abort(500, 'ZipArchive PHP extension is required to build the plugin package.');
        }

        $cacheZip = storage_path('framework/cache/reviveguard-agent.zip');
        File::ensureDirectoryExists(dirname($cacheZip));

        if (! is_file($cacheZip) || filemtime($cacheZip) < $this->latestSourceMtime($pluginPath)) {
            $this->buildZip($pluginPath, $cacheZip);
        }

        return response()->download($cacheZip, 'reviveguard-agent.zip', [
            'Content-Type' => 'application/zip',
        ]);
    }

    private function buildZip(string $pluginPath, string $zipPath): void
    {
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
    }

    private function latestSourceMtime(string $pluginPath): int
    {
        $latest = filemtime($pluginPath) ?: 0;

        foreach (File::allFiles($pluginPath) as $file) {
            $latest = max($latest, $file->getMTime());
        }

        return $latest;
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
