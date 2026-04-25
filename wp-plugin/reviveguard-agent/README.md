# ReviveGuard Site Agent

Connects your WordPress site to the [ReviveGuard](https://app.reviveguard.com) monitoring platform.

## Requirements

- WordPress 5.8+
- PHP 7.4+
- WP-CLI installed on the server (recommended, required for update commands)

## Installation

1. Download `reviveguard-agent.zip` from your ReviveGuard dashboard
2. Go to **Plugins → Add New → Upload Plugin**
3. Upload the zip file and activate
4. Go to **Settings → ReviveGuard**
5. Paste your agent token (from your ReviveGuard site dashboard)
6. Click **Save Settings**
7. Click **Send Test Heartbeat** to confirm the connection

## What it does

- Sends a heartbeat every 5 minutes with site health data (WP version, PHP version, plugin count, disk usage)
- Receives commands from the platform (backup, updates, plugin inventory)
- Uploads backups to Backblaze B2
- Runs plugin/theme/core updates via WP-CLI (or WP native API as fallback)
- Logs all activity to `wp-content/uploads/reviveguard/debug.log`

## Notes

- WP Cron runs when your site receives traffic. For low-traffic sites, add a server cron:
  ```
  */5 * * * * curl -s https://yoursite.com/wp-cron.php > /dev/null
  ```
- All credentials stored encrypted in `wp_options`
- Zero Composer dependencies — pure WordPress PHP

## License

Proprietary — WaybackRevive LLC
