<?php
defined('ABSPATH') || exit;

/**
 * Orchestrates the heartbeat: collects site data, sends to API, handles response.
 * Receives commands in the heartbeat response and queues them for execution.
 */
final class ReviveGuard_Heartbeat
{
    public function run(): void
    {
        $token = ReviveGuard_TokenStore::get();
        if (empty($token)) {
            // Plugin installed but not yet configured — skip silently
            return;
        }

        $siteInfo  = new ReviveGuard_SiteInfo();
        $client    = new ReviveGuard_ApiClient();
        $inventory = new ReviveGuard_PluginInventory();

        $payload  = $siteInfo->collect();
        $response = $client->post('/api/v1/agent/heartbeat', $payload);

        if ($response === null) {
            // API call failed — ApiClient already logged and updated status
            return;
        }

        update_option('reviveguard_last_heartbeat', time());
        update_option('reviveguard_connection_status', 'connected');

        // Send plugin list only when something has changed (avoid noise)
        if ($inventory->hasChanged()) {
            $this->sendPluginList($client, $inventory);
        }

        // Process command received from server
        // API returns: { status, command, params, command_id }
        if (! empty($response['command']) && ! empty($response['command_id'])) {
            $runner = new ReviveGuard_CommandRunner();
            $runner->queue([
                'command_id' => $response['command_id'],
                'command'    => $response['command'],
                'params'     => $response['params'] ?? [],
            ]);
        }
    }

    private function sendPluginList(ReviveGuard_ApiClient $client, ReviveGuard_PluginInventory $inventory): void
    {
        $plugins = $inventory->collect();
        $result  = $client->post('/api/v1/agent/plugin-list', ['plugins' => $plugins]);

        if ($result !== null) {
            $inventory->saveChecksum($plugins);
        }
    }
}
