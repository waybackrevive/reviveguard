<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Backblaze B2 service — upload files and generate signed download URLs.
 *
 * Auth flow:
 *  1. b2_authorize_account  → authorizationToken + apiUrl + downloadUrl
 *  2. b2_get_upload_url     → uploadUrl + uploadAuthToken  (per upload)
 *  3. b2_upload_file        → upload binary content
 *  4. b2_get_download_authorization → signed URL token (for private buckets)
 */
class BackblazeService
{
    private string $keyId;
    private string $appKey;
    private string $bucketId;
    private string $bucketName;

    public function __construct()
    {
        $this->keyId      = (string) config('services.backblaze.key_id', '');
        $this->appKey     = (string) config('services.backblaze.app_key', '');
        $this->bucketId   = (string) config('services.backblaze.bucket_id', '');
        $this->bucketName = (string) config('services.backblaze.bucket_name', '');
    }

    // ── Public API ────────────────────────────────────────────────────────────

    /**
     * Upload a file to B2. Returns the fileKey (path) on success, null on failure.
     *
     * @param  string  $fileKey   Path/name to store in B2 (e.g. "reports/2025-01/site-123.pdf")
     * @param  string  $content   Raw binary file content
     * @param  string  $mimeType  MIME type (default application/pdf)
     */
    public function uploadFile(string $fileKey, string $content, string $mimeType = 'application/pdf'): ?string
    {
        if (! $this->isConfigured()) {
            Log::info('BackblazeService: B2 not configured, skipping upload');
            return null;
        }

        try {
            $auth = $this->authorize();
            if (! $auth) {
                return null;
            }

            $uploadEndpoint = $this->getUploadUrl($auth['authorizationToken'], $auth['apiUrl']);
            if (! $uploadEndpoint) {
                return null;
            }

            $sha1 = sha1($content);

            $response = Http::withHeaders([
                'Authorization'     => $uploadEndpoint['authorizationToken'],
                'X-Bz-File-Name'    => rawurlencode($fileKey),
                'Content-Type'      => $mimeType,
                'Content-Length'    => strlen($content),
                'X-Bz-Content-Sha1' => $sha1,
            ])
            ->timeout(30)
            ->withBody($content, $mimeType)
            ->post($uploadEndpoint['uploadUrl']);

            if ($response->successful()) {
                Log::info('BackblazeService: file uploaded', ['key' => $fileKey]);
                return $fileKey;
            }

            Log::error('BackblazeService: upload failed', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
            return null;
        } catch (\Throwable $e) {
            Log::error('BackblazeService: uploadFile exception', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Generate a signed (pre-authorized) download URL for a B2 object.
     *
     * @param  string  $bucket   B2 bucket name
     * @param  string  $fileKey  Object key / path within the bucket
     * @param  int     $ttl      Expiry in seconds (default 1 hour)
     */
    public function getSignedUrl(string $bucket, string $fileKey, int $ttl = 3600): string
    {
        if (! $this->isConfigured()) {
            return '#';
        }

        try {
            $auth = $this->authorize();
            if (! $auth) {
                return '#';
            }

            $response = Http::withHeaders(['Authorization' => $auth['authorizationToken']])
                ->timeout(10)
                ->post("{$auth['apiUrl']}/b2api/v3/b2_get_download_authorization", [
                    'bucketId'               => $this->bucketId,
                    'fileNamePrefix'         => $fileKey,
                    'validDurationInSeconds' => $ttl,
                ]);

            if ($response->successful()) {
                $token       = $response->json('authorizationToken');
                $downloadUrl = $auth['downloadUrl'];
                return "{$downloadUrl}/file/{$bucket}/{$fileKey}?Authorization={$token}";
            }

            Log::warning('BackblazeService: getSignedUrl failed', ['status' => $response->status()]);
        } catch (\Throwable $e) {
            Log::error('BackblazeService: getSignedUrl exception', ['error' => $e->getMessage()]);
        }

        return '#';
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    private function isConfigured(): bool
    {
        return ! empty($this->keyId) && ! empty($this->appKey);
    }

    /**
     * @return array{authorizationToken: string, apiUrl: string, downloadUrl: string}|null
     */
    private function authorize(): ?array
    {
        $response = Http::withBasicAuth($this->keyId, $this->appKey)
            ->timeout(10)
            ->get('https://api.backblazeb2.com/b2api/v3/b2_authorize_account');

        if (! $response->successful()) {
            Log::error('BackblazeService: authorize failed', ['status' => $response->status()]);
            return null;
        }

        return [
            'authorizationToken' => $response->json('authorizationToken'),
            'apiUrl'             => $response->json('apiInfo.storageApi.apiUrl')
                                    ?? $response->json('apiUrl'),
            'downloadUrl'        => $response->json('apiInfo.storageApi.downloadUrl')
                                    ?? $response->json('downloadUrl'),
        ];
    }

    /**
     * @return array{uploadUrl: string, authorizationToken: string}|null
     */
    private function getUploadUrl(string $authToken, string $apiUrl): ?array
    {
        $response = Http::withHeaders(['Authorization' => $authToken])
            ->timeout(10)
            ->post("{$apiUrl}/b2api/v3/b2_get_upload_url", [
                'bucketId' => $this->bucketId,
            ]);

        if (! $response->successful()) {
            Log::error('BackblazeService: getUploadUrl failed', ['status' => $response->status()]);
            return null;
        }

        return [
            'uploadUrl'          => $response->json('uploadUrl'),
            'authorizationToken' => $response->json('authorizationToken'),
        ];
    }
}

