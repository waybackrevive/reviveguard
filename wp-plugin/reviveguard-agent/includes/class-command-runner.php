<?php
defined('ABSPATH') || exit;

/**
 * Receives commands from the heartbeat response, stores them, and executes them
 * on the next WP Cron tick (keeps heartbeat response fast).
 *
 * Supported commands: run_backup | run_updates | get_plugin_list
 */
final class ReviveGuard_CommandRunner
{
    private const PENDING_OPTION = 'reviveguard_pending_command';

    /**
     * Store a command for execution on next available cron run.
     *
     * @param array{command_id: string, command: string, params: array<string, mixed>} $command
     */
    public function queue(array $command): void
    {
        // If a command is already running, log and skip
        $existing = get_option(self::PENDING_OPTION);
        if (! empty($existing)) {
            ReviveGuard_DebugLogger::warning(
                'Command queued while another is pending — skipping: ' . $command['command']
            );
            return;
        }

        update_option(self::PENDING_OPTION, wp_json_encode($command));

        // Schedule immediate execution via WP Cron
        if (! wp_next_scheduled('reviveguard_run_command')) {
            wp_schedule_single_event(time(), 'reviveguard_run_command');
        }

        // Spawn cron now (non-blocking) so the command doesn't wait for next traffic
        spawn_cron();
    }

    /**
     * Execute the pending command. Called by the reviveguard_run_command cron hook.
     */
    public function execute(): void
    {
        $raw = get_option(self::PENDING_OPTION);
        if (empty($raw)) {
            return;
        }

        $command = json_decode($raw, true);
        if (! is_array($command) || empty($command['command_id']) || empty($command['command'])) {
            ReviveGuard_DebugLogger::error('Invalid pending command payload: ' . (string) $raw);
            delete_option(self::PENDING_OPTION);
            return;
        }

        $commandId = (string) $command['command_id'];
        $action    = (string) $command['command'];
        $params    = isset($command['params']) && is_array($command['params']) ? $command['params'] : [];

        ReviveGuard_DebugLogger::info("Executing command: {$action} (id={$commandId})");

        $result = $this->dispatch($action, $commandId, $params);

        // Clear pending before reporting back (so a failed report doesn't re-run)
        delete_option(self::PENDING_OPTION);

        $client = new ReviveGuard_ApiClient();
        $client->post('/api/v1/agent/command-result', [
            'command_id' => $commandId,
            'status'     => $result['status'] === 'success' ? 'success' : 'failed',
            'result'     => $result,
            'error'      => $result['error'] ?? null,
        ]);
    }

    /**
     * @param  array<string, mixed> $params
     * @return array<string, mixed>
     */
    private function dispatch(string $action, string $commandId, array $params): array
    {
        switch ($action) {
            case 'run_backup':
                $handler = new ReviveGuard_BackupHandler();
                return $handler->run($commandId, $params);

            case 'run_wp_updates':
            case 'run_updates':
                $handler = new ReviveGuard_UpdateHandler();
                return $handler->run($commandId);

            case 'get_plugin_list':
                $inventory = new ReviveGuard_PluginInventory();
                $plugins   = $inventory->collect();
                $client    = new ReviveGuard_ApiClient();
                $client->post('/api/v1/agent/plugin-list', ['plugins' => $plugins]);
                $inventory->saveChecksum($plugins);
                return ['status' => 'success'];

            default:
                ReviveGuard_DebugLogger::warning("Unknown command received: {$action}");
                return ['status' => 'failed', 'error' => "Unknown command: {$action}"];
        }
    }
}

// Hook command execution to cron event
add_action('reviveguard_run_command', function (): void {
    $runner = new ReviveGuard_CommandRunner();
    $runner->execute();
});
