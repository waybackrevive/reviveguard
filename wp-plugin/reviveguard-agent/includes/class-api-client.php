<?php
defined('ABSPATH') || exit;

/**
 * Handles all outgoing HTTP requests to the ReviveGuard platform API.
 * Uses wp_remote_post — zero external dependencies.
 */
final class ReviveGuard_ApiClient
{
    private string $token;
    private string $baseUrl;
    private int $timeout = 15;

    public function __construct()
    {
        $this->token   = ReviveGuard_TokenStore::get();
        $this->baseUrl = (string) get_option('reviveguard_api_base_url', REVIVEGUARD_API_BASE);
    }

    /**
     * POST JSON to an API endpoint. Returns decoded response array or null on failure.
     */
    public function post(string $endpoint, array $data): ?array
    {
        if (empty($this->token)) {
            ReviveGuard_DebugLogger::warning('API call skipped — no token configured');
            return null;
        }

        $url  = rtrim($this->baseUrl, '/') . $endpoint;
        $body = wp_json_encode($data);

        if ($body === false) {
            ReviveGuard_DebugLogger::error("JSON encode failed for endpoint {$endpoint}");
            return null;
        }

        $response = wp_remote_post($url, [
            'headers'   => [
                'Authorization' => 'Bearer ' . $this->token,
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json',
                'User-Agent'    => 'ReviveGuard-Agent/' . REVIVEGUARD_VERSION,
            ],
            'body'      => $body,
            'timeout'   => $this->timeout,
            'sslverify' => true,
        ]);

        if (is_wp_error($response)) {
            ReviveGuard_DebugLogger::error('API request failed: ' . $response->get_error_message());
            $this->incrementFailureCount();
            return null;
        }

        $statusCode = (int) wp_remote_retrieve_response_code($response);

        if ($statusCode === 401) {
            update_option('reviveguard_connection_status', 'auth_error');
            ReviveGuard_DebugLogger::error('API returned 401 — token may be invalid or revoked');
            return null;
        }

        if ($statusCode === 429) {
            ReviveGuard_DebugLogger::warning('API rate limit hit (429) — backing off');
            $this->incrementFailureCount();
            return null;
        }

        if ($statusCode < 200 || $statusCode >= 300) {
            ReviveGuard_DebugLogger::error("API returned HTTP {$statusCode} for {$endpoint}");
            $this->incrementFailureCount();
            return null;
        }

        $this->resetFailureCount();

        $decoded = json_decode(wp_remote_retrieve_body($response), true);
        return is_array($decoded) ? $decoded : [];
    }

    private function incrementFailureCount(): void
    {
        $count = (int) get_option('reviveguard_failure_count', 0) + 1;
        update_option('reviveguard_failure_count', $count);

        if ($count >= 3) {
            update_option('reviveguard_connection_status', 'error');
        }
    }

    private function resetFailureCount(): void
    {
        update_option('reviveguard_failure_count', 0);
        update_option('reviveguard_connection_status', 'connected');
    }
}
