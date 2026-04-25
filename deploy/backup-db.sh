#!/usr/bin/env bash
# ─── ReviveGuard: pg_dump → Backblaze B2 ─────────────────────────────────────
# Schedule via cron: 0 2 * * * /var/www/reviveguard/deploy/backup-db.sh
# Requires: pg_dump, rclone (configured with b2 remote named "b2")
#
# rclone setup (one time):
#   rclone config → new remote → name: b2 → Backblaze B2 → enter key_id + app_key

set -euo pipefail

DB_NAME="${DB_DATABASE:-reviveguard}"
DB_USER="${DB_USERNAME:-reviveguard_user}"
DB_HOST="${DB_HOST:-127.0.0.1}"
DB_PORT="${DB_PORT:-5432}"
B2_BUCKET="${BACKBLAZE_BUCKET_NAME:-reviveguard-backups}"
BACKUP_DIR="/tmp/reviveguard-dbbackups"
TIMESTAMP=$(date +"%Y-%m-%d_%H-%M-%S")
BACKUP_FILE="${BACKUP_DIR}/reviveguard_${TIMESTAMP}.sql.gz"
RETENTION_DAYS=30

# Load env vars from Laravel .env if PGPASSWORD not set
if [ -z "${PGPASSWORD:-}" ]; then
  source <(grep -E '^(DB_PASSWORD)=' /var/www/reviveguard/.env | sed 's/DB_PASSWORD/PGPASSWORD/')
fi

mkdir -p "$BACKUP_DIR"

echo "[backup-db] Dumping $DB_NAME to $BACKUP_FILE..."
PGPASSWORD="$PGPASSWORD" pg_dump \
  -h "$DB_HOST" \
  -p "$DB_PORT" \
  -U "$DB_USER" \
  -F p \
  "$DB_NAME" | gzip > "$BACKUP_FILE"

echo "[backup-db] Uploading to B2 bucket: $B2_BUCKET..."
rclone copy "$BACKUP_FILE" "b2:${B2_BUCKET}/database-backups/"

echo "[backup-db] Cleaning up old backups (>${RETENTION_DAYS} days)..."
rclone delete "b2:${B2_BUCKET}/database-backups/" \
  --min-age "${RETENTION_DAYS}d"

rm -f "$BACKUP_FILE"

echo "[backup-db] Done at $(date '+%Y-%m-%d %H:%M:%S')"
