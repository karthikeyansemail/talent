#!/bin/bash
# =============================================================================
# Nalam Pulse — Automated Installer
# Supports: Ubuntu 22.04 LTS
# Called by: CloudFormation UserData, Azure CustomScript, GCP startup-script
#
# Required environment variables (set by the cloud template):
#   NALAM_LICENSE   — license key from your subscription email
#   NALAM_DOMAIN    — domain name, e.g. pulse.yourcompany.com
#   NALAM_SMTP_HOST — SMTP hostname
#   NALAM_SMTP_PORT — SMTP port (default 587)
#   NALAM_SMTP_USER — SMTP username / from-address
#   NALAM_SMTP_PASS — SMTP password
#   NALAM_AI_KEY    — OpenAI API key (or Azure OpenAI key)
# =============================================================================
set -euo pipefail

NALAM_SMTP_PORT="${NALAM_SMTP_PORT:-587}"
INSTALL_DIR="/opt/nalampulse"
REPO_RAW="https://raw.githubusercontent.com/nalampulse/deploy/main"
LOG_FILE="/var/log/nalampulse-install.log"

log() { echo "[$(date '+%H:%M:%S')] $*" | tee -a "$LOG_FILE"; }

log "=========================================="
log "  Nalam Pulse — Installer v1.0"
log "  Domain : ${NALAM_DOMAIN}"
log "=========================================="

# ── 1. System packages ───────────────────────────────────────────────────────
log "Installing system packages..."
export DEBIAN_FRONTEND=noninteractive
apt-get update -qq
apt-get install -y -qq \
    apt-transport-https ca-certificates curl gnupg lsb-release \
    certbot python3-certbot-nginx nginx openssl

# ── 2. Docker ────────────────────────────────────────────────────────────────
log "Installing Docker..."
if ! command -v docker &>/dev/null; then
    curl -fsSL https://download.docker.com/linux/ubuntu/gpg \
        | gpg --dearmor -o /usr/share/keyrings/docker-archive-keyring.gpg
    echo "deb [arch=$(dpkg --print-architecture) signed-by=/usr/share/keyrings/docker-archive-keyring.gpg] \
        https://download.docker.com/linux/ubuntu $(lsb_release -cs) stable" \
        > /etc/apt/sources.list.d/docker.list
    apt-get update -qq
    apt-get install -y -qq docker-ce docker-ce-cli containerd.io docker-compose-plugin
fi

systemctl enable docker
systemctl start docker
log "Docker $(docker --version) installed."

# ── 3. Create install directory ───────────────────────────────────────────────
log "Setting up install directory at ${INSTALL_DIR}..."
mkdir -p "${INSTALL_DIR}"
cd "${INSTALL_DIR}"

# ── 4. Fetch docker-compose.yml ───────────────────────────────────────────────
log "Downloading docker-compose.yml and nginx config..."
curl -fsSL "${REPO_RAW}/docker-compose.yml" -o docker-compose.yml
curl -fsSL "${REPO_RAW}/nginx.conf" -o nginx.conf

# ── 5. Generate secrets ───────────────────────────────────────────────────────
DB_PASSWORD=$(openssl rand -hex 24)
APP_KEY="base64:$(openssl rand -base64 32)"

# ── 6. Write .env ─────────────────────────────────────────────────────────────
log "Writing .env..."
cat > .env << ENV
APP_NAME="Nalam Pulse"
APP_ENV=production
APP_DEBUG=false
APP_KEY=${APP_KEY}
APP_URL=https://${NALAM_DOMAIN}

LOG_CHANNEL=stack
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=talent_db
DB_USERNAME=talent
DB_PASSWORD=${DB_PASSWORD}

CACHE_DRIVER=file
SESSION_DRIVER=file
QUEUE_CONNECTION=database

MAIL_MAILER=smtp
MAIL_HOST=${NALAM_SMTP_HOST}
MAIL_PORT=${NALAM_SMTP_PORT}
MAIL_USERNAME=${NALAM_SMTP_USER}
MAIL_PASSWORD=${NALAM_SMTP_PASS}
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=no-reply@${NALAM_DOMAIN}
MAIL_FROM_NAME="Nalam Pulse"

OPENAI_API_KEY=${NALAM_AI_KEY}
AI_SERVICE_URL=http://ai-service:8000

NALAM_LICENSE_KEY=${NALAM_LICENSE}

MYSQL_ROOT_PASSWORD=${DB_PASSWORD}
MYSQL_DATABASE=talent_db
MYSQL_USER=talent
MYSQL_PASSWORD=${DB_PASSWORD}
ENV

chmod 600 .env
log ".env written (DB password auto-generated)."

# ── 7. Start containers ───────────────────────────────────────────────────────
log "Pulling Docker images (this may take 3-5 minutes)..."
docker compose pull

log "Starting containers..."
docker compose up -d

# Wait for DB to be healthy
log "Waiting for database to be ready..."
for i in $(seq 1 30); do
    if docker compose exec -T db mysqladmin ping -h localhost --silent 2>/dev/null; then
        log "Database ready."
        break
    fi
    sleep 3
done

# ── 8. Run Laravel setup ──────────────────────────────────────────────────────
log "Running migrations and seeding..."
docker compose exec -T app php artisan migrate --force --seed
docker compose exec -T app php artisan optimize

log "Laravel setup complete."

# ── 9. Nginx + SSL ────────────────────────────────────────────────────────────
log "Configuring Nginx..."
cat > /etc/nginx/sites-available/nalampulse << NGINX
server {
    listen 80;
    server_name ${NALAM_DOMAIN};

    location / {
        proxy_pass http://127.0.0.1:8080;
        proxy_set_header Host \$host;
        proxy_set_header X-Real-IP \$remote_addr;
        proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto \$scheme;
        proxy_read_timeout 120s;
    }
}
NGINX

ln -sf /etc/nginx/sites-available/nalampulse /etc/nginx/sites-enabled/
rm -f /etc/nginx/sites-enabled/default
nginx -t && systemctl reload nginx

log "Obtaining SSL certificate via Let's Encrypt..."
certbot --nginx \
    -d "${NALAM_DOMAIN}" \
    --non-interactive \
    --agree-tos \
    -m "admin@${NALAM_DOMAIN}" \
    --redirect

# Auto-renew cron
(crontab -l 2>/dev/null; echo "0 3 * * * certbot renew --quiet && systemctl reload nginx") | crontab -

# ── 10. Done ──────────────────────────────────────────────────────────────────
ADMIN_EMAIL=$(docker compose exec -T app php artisan tinker --execute="echo App\Models\User::where('role','org_admin')->first()?->email ?? 'admin@example.com';" 2>/dev/null | tail -1 || echo "admin@acme.com")

log ""
log "=========================================="
log "  Installation Complete!"
log ""
log "  URL     : https://${NALAM_DOMAIN}"
log "  Admin   : ${ADMIN_EMAIL}"
log "  Password: password  ← CHANGE THIS NOW"
log ""
log "  Next step: Point your DNS"
log "    ${NALAM_DOMAIN} → $(curl -s ifconfig.me)"
log ""
log "  Container status:"
docker compose ps
log "=========================================="
