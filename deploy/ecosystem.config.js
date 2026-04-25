// PM2 ecosystem — ReviveGuard production processes
// Run: pm2 start ecosystem.config.js
// Run: pm2 startup && pm2 save   (to restart on server reboot)

module.exports = {
    apps: [
        // ─── Puppeteer PDF microservice ──────────────────────────────────────────
        {
            name: 'reviveguard-puppeteer',
            script: 'server.js',
            cwd: '/var/www/reviveguard/puppeteer-service',
            instances: 1,
            autorestart: true,
            watch: false,
            max_memory_restart: '512M',
            env: {
                NODE_ENV: 'production',
                PORT: 3002,
                HOST: '127.0.0.1',
            },
            error_file: '/var/log/pm2/puppeteer-error.log',
            out_file: '/var/log/pm2/puppeteer-out.log',
            log_date_format: 'YYYY-MM-DD HH:mm:ss',
        },

        // ─── Uptime Kuma ─────────────────────────────────────────────────────────
        // Note: Uptime Kuma has its own update mechanism — don't set autorestart_on_exit
        {
            name: 'uptime-kuma',
            script: 'server.js',
            cwd: '/opt/uptime-kuma',
            instances: 1,
            autorestart: true,
            watch: false,
            max_memory_restart: '256M',
            env: {
                NODE_ENV: 'production',
                PORT: 3001,
                HOST: '127.0.0.1',
            },
            error_file: '/var/log/pm2/uptime-kuma-error.log',
            out_file: '/var/log/pm2/uptime-kuma-out.log',
            log_date_format: 'YYYY-MM-DD HH:mm:ss',
        },
    ],
};
