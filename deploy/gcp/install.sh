#!/bin/bash
# =============================================================================
# Nalam Pulse — One-Command GCP Installer
#
# Usage:
#   From Google Cloud Shell:
#     git clone https://github.com/karthikeyansemail/talent.git /tmp/nalam
#     bash /tmp/nalam/deploy/gcp/install.sh
#
#   Or directly on a fresh Ubuntu 22.04 VM:
#     git clone https://github.com/karthikeyansemail/talent.git /opt/nalam
#     bash /opt/nalam/deploy/gcp/install.sh --local
#
# Handles: VM creation, Docker, nginx, SSL, DB seeding — everything.
# =============================================================================
set -euo pipefail

# Colors
RED='\033[0;31m'; GREEN='\033[0;32m'; YELLOW='\033[1;33m'; CYAN='\033[0;36m'; NC='\033[0m'

print_header() { echo -e "\n${CYAN}═══════════════════════════════════════════════${NC}"; echo -e "${CYAN}  $1${NC}"; echo -e "${CYAN}═══════════════════════════════════════════════${NC}\n"; }
print_step()   { echo -e "${GREEN}[$(date +%H:%M:%S)] ✓ $1${NC}"; }
print_warn()   { echo -e "${YELLOW}[$(date +%H:%M:%S)] ⚠ $1${NC}"; }
print_error()  { echo -e "${RED}[$(date +%H:%M:%S)] ✗ $1${NC}"; }
ask()          { local v; read -p "  $1: " v; echo "$v"; }
ask_default()  { local v; read -p "  $1 [$2]: " v; echo "${v:-$2}"; }
ask_secret()   { local v; read -s -p "  $1: " v; echo ""; echo "$v"; }

# ─── Detect mode ──────────────────────────────────────────────────────────────
LOCAL_MODE=false
if [[ "${1:-}" == "--local" ]]; then
    LOCAL_MODE=true
fi

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_DIR="$(cd "$SCRIPT_DIR/../.." && pwd)"

print_header "Nalam Pulse — GCP Installer"

# ─── Step 1: Gather info ─────────────────────────────────────────────────────
# Supports both interactive (wizard) and non-interactive (env vars) modes.
# For fully automated deployment, set these env vars before running:
#   NALAM_TYPE=demo NALAM_DOMAIN=demo.nalampulse.com NALAM_SSL=y bash install.sh
#
SERVER_IP=$(curl -s ifconfig.me 2>/dev/null || hostname -I | awk '{print $1}')

if [[ -n "${NALAM_TYPE:-}" ]]; then
    # Non-interactive mode — use env vars
    INSTANCE_TYPE="${NALAM_TYPE}"
    DOMAIN="${NALAM_DOMAIN:-$SERVER_IP}"
    DB_PASSWORD="${NALAM_DB_PASS:-NalamPulse$(openssl rand -hex 4)}"
    SETUP_SSL="${NALAM_SSL:-n}"
    echo -e "  ${GREEN}Non-interactive mode (from env vars)${NC}"
else
    # Interactive wizard
    echo -e "${YELLOW}Answer a few questions to configure your deployment:${NC}\n"
    INSTANCE_TYPE=$(ask_default "Instance type (demo/production)" "demo")
    DOMAIN=$(ask_default "Domain name" "${SERVER_IP}")
    DB_PASSWORD=$(ask_default "Database password" "NalamPulse$(openssl rand -hex 4)")
    echo ""
    SETUP_SSL=$(ask_default "Set up SSL now? (requires DNS already pointing to this server)" "n")
fi

if [[ "$INSTANCE_TYPE" == "demo" ]]; then
    APP_NAME="Nalam Pulse Demo"
    SEED_DATA=true
    DEMO_MODE=true
    echo -e "  ${GREEN}Demo mode: Will seed all 4 demo orgs + weekly auto-refresh${NC}"
else
    APP_NAME="Nalam Pulse"
    SEED_DATA=false
    DEMO_MODE=false
    echo -e "  ${GREEN}Production mode: Clean database, super admin only${NC}"
fi

echo ""
echo -e "${YELLOW}Configuration summary:${NC}"
echo "  Instance:  $INSTANCE_TYPE"
echo "  App Name:  $APP_NAME"
echo "  Domain:    $DOMAIN"
echo "  DB Pass:   ${DB_PASSWORD:0:4}****"
echo "  Seed Data: $SEED_DATA"
echo "  SSL:       $SETUP_SSL"
echo ""

if [[ -z "${NALAM_TYPE:-}" ]]; then
    read -p "  Proceed? [Y/n]: " CONFIRM
    [[ "${CONFIRM,,}" != "n" ]] || { echo "Aborted."; exit 0; }
fi

# ─── Step 2: Install Docker (if not present) ─────────────────────────────────
print_header "Step 1/6 — Installing Docker"

if command -v docker &>/dev/null; then
    print_step "Docker already installed ($(docker --version | cut -d' ' -f3))"
else
    sudo apt-get update -qq
    sudo apt-get install -y -qq docker.io docker-compose-v2 > /dev/null 2>&1
    sudo systemctl enable docker
    sudo systemctl start docker
    sudo usermod -aG docker "$(whoami)" 2>/dev/null || true
    print_step "Docker installed"
fi

# ─── Step 3: Install nginx ───────────────────────────────────────────────────
print_header "Step 2/6 — Installing Nginx"

if command -v nginx &>/dev/null; then
    print_step "Nginx already installed"
else
    sudo apt-get install -y -qq nginx > /dev/null 2>&1
    print_step "Nginx installed"
fi

# Configure nginx reverse proxy
sudo tee /etc/nginx/sites-available/nalam > /dev/null <<NGINX
server {
    listen 80;
    server_name ${DOMAIN} _;
    client_max_body_size 50M;

    location / {
        proxy_pass http://127.0.0.1:8080;
        proxy_set_header Host \$host;
        proxy_set_header X-Real-IP \$remote_addr;
        proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto \$scheme;
        proxy_read_timeout 300s;
    }
}
NGINX

sudo ln -sf /etc/nginx/sites-available/nalam /etc/nginx/sites-enabled/
sudo rm -f /etc/nginx/sites-enabled/default
sudo nginx -t > /dev/null 2>&1 && sudo systemctl restart nginx
print_step "Nginx configured (port 80 → Docker 8080)"

# ─── Step 4: Set up code ─────────────────────────────────────────────────────
print_header "Step 3/6 — Setting Up Application"

APP_DIR="/opt/nalam"

if [[ "$LOCAL_MODE" == true ]] && [[ -d "$APP_DIR" ]]; then
    print_step "Code already at $APP_DIR"
elif [[ -d "$REPO_DIR/.git" ]]; then
    if [[ "$APP_DIR" != "$REPO_DIR" ]]; then
        sudo cp -r "$REPO_DIR" "$APP_DIR"
        sudo chown -R "$(whoami):$(whoami)" "$APP_DIR"
    fi
    print_step "Code copied to $APP_DIR"
else
    sudo git clone https://github.com/karthikeyansemail/talent.git "$APP_DIR"
    sudo chown -R "$(whoami):$(whoami)" "$APP_DIR"
    print_step "Code cloned to $APP_DIR"
fi

# Generate APP_KEY
APP_KEY="base64:$(openssl rand -base64 32)"
print_step "APP_KEY generated"

# Create Laravel .env
cat > "$APP_DIR/deploy/gcp/.env" <<EOF
APP_NAME="${APP_NAME}"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://${DOMAIN}
APP_KEY=${APP_KEY}
DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=talent_db
DB_USERNAME=talent
DB_PASSWORD=${DB_PASSWORD}
CACHE_DRIVER=file
SESSION_DRIVER=file
QUEUE_CONNECTION=database
MAIL_MAILER=log
MAIL_HOST=
MAIL_USERNAME=
MAIL_PASSWORD=
AI_SERVICE_URL=http://ai-service:8000
DEMO_MODE=${DEMO_MODE}
EOF
chmod 777 "$APP_DIR/deploy/gcp/.env"
print_step "Laravel .env created"

# Create AI service .env (minimal — actual keys configured via Settings page)
cat > "$APP_DIR/ai-service/.env" <<'EOF'
LLM_PROVIDER=openai
OPENAI_API_KEY=placeholder
LOG_LEVEL=info
EOF
print_step "AI service .env created"

# ─── Step 5: Build & Start Docker ────────────────────────────────────────────
print_header "Step 4/6 — Building Docker Images (this takes 3-5 minutes)"

cd "$APP_DIR"
COMPOSE="docker compose --env-file deploy/gcp/.env -f deploy/gcp/docker-compose.build.yml"
sudo $COMPOSE down 2>/dev/null || true
sudo $COMPOSE up -d --build

print_step "Docker containers started"

# Wait for DB + App to be healthy
echo -e "  Waiting for database and app to be ready..."
TRIES=0
while true; do
    TRIES=$((TRIES+1))
    APP_STATUS=$(sudo docker inspect --format='{{.State.Status}}' gcp-app-1 2>/dev/null || echo "missing")
    DB_STATUS=$(sudo docker inspect --format='{{.State.Health.Status}}' gcp-db-1 2>/dev/null || echo "missing")

    if [[ "$DB_STATUS" == "healthy" ]] && [[ "$APP_STATUS" == "running" ]]; then
        # Check if app is truly ready (not restarting)
        sleep 5
        APP_STATUS2=$(sudo docker inspect --format='{{.State.Status}}' gcp-app-1 2>/dev/null || echo "missing")
        if [[ "$APP_STATUS2" == "running" ]]; then
            break
        fi
    fi

    if [[ $TRIES -ge 60 ]]; then
        print_error "Timeout waiting for containers. Check: sudo docker logs gcp-app-1"
        exit 1
    fi
    sleep 3
done
print_step "All containers healthy"

# ─── Step 6: Seed database ───────────────────────────────────────────────────
print_header "Step 5/6 — Setting Up Database"

# Wait for app entrypoint to finish migrations + DB to stabilize
echo -e "  Waiting for app to be fully ready (entrypoint + DB)..."
TRIES=0
while true; do
    TRIES=$((TRIES+1))
    if sudo $COMPOSE exec -T app php -r "
        try {
            new PDO('mysql:host=db;port=3306;dbname=talent_db', 'talent', getenv('DB_PASSWORD'), [PDO::ATTR_TIMEOUT => 3]);
            echo 'db_ok';
        } catch (Exception \$e) { echo 'db_fail'; }
    " 2>/dev/null | grep -q db_ok; then
        # Also check that migrations are done (artisan can boot without error)
        if sudo $COMPOSE exec -T app php artisan tinker --execute="echo 'ready';" 2>/dev/null | grep -q ready; then
            break
        fi
    fi
    if [[ $TRIES -ge 40 ]]; then
        print_error "App/DB not ready after 120s. Check: sudo docker logs gcp-app-1"
        exit 1
    fi
    echo -e "  Not ready yet... (${TRIES}/40)"
    sleep 3
done
print_step "App and database ready"

# Ensure migrations are fully complete before any data import
echo -e "  Running migrations..."
run_attempts=0
while true; do
    run_attempts=$((run_attempts+1))
    if sudo $COMPOSE exec -T app php artisan migrate --force 2>&1 | tail -3; then
        break
    fi
    if [[ $run_attempts -ge 5 ]]; then
        print_error "Migrations failed after $run_attempts attempts"
        exit 1
    fi
    echo -e "  Migration attempt $run_attempts failed, retrying in 10s..."
    sleep 10
done
print_step "Migrations complete"

# Retry wrapper — handles transient DB connection drops on small VMs
run_with_retry() {
    local desc="$1"; shift
    local attempt=0
    while true; do
        attempt=$((attempt+1))
        if "$@" 2>&1; then
            return 0
        fi
        if [[ $attempt -ge 5 ]]; then
            print_error "$desc failed after $attempt attempts"
            return 1
        fi
        print_warn "$desc failed (attempt $attempt/5), retrying in 10s..."
        sleep 10
    done
}

if [[ "$SEED_DATA" == true ]]; then
    DEMO_GZ="$APP_DIR/deploy/gcp/demo-data.sql.gz"
    if [[ -f "$DEMO_GZ" ]]; then
        # Use the full localhost dump — exact replica of dev environment
        echo -e "  Clearing default data before importing demo dump..."
        # Truncate all non-migration tables so the dump can insert cleanly
        sudo $COMPOSE exec -T db mysql -u talent -p"${DB_PASSWORD}" talent_db -e "
            SET FOREIGN_KEY_CHECKS=0;
            SET @tables = NULL;
            SELECT GROUP_CONCAT(table_name) INTO @tables
                FROM information_schema.tables
                WHERE table_schema = 'talent_db' AND table_name != 'migrations';
            SET @sql = CONCAT('TRUNCATE TABLE ', REPLACE(@tables, ',', '; TRUNCATE TABLE '), ';');
            PREPARE stmt FROM @sql;
            EXECUTE stmt;
            DEALLOCATE PREPARE stmt;
            SET FOREIGN_KEY_CHECKS=1;
        " 2>/dev/null || true

        echo -e "  Importing demo data dump (full localhost replica)..."
        gunzip -k "$DEMO_GZ" 2>/dev/null || true
        DEMO_SQL="${DEMO_GZ%.gz}"
        run_with_retry "Demo data import" sudo $COMPOSE exec -T db \
            mysql --binary-mode -u talent -p"${DB_PASSWORD}" talent_db < "$DEMO_SQL"
        rm -f "$DEMO_SQL"
        print_step "Full demo data imported (all orgs, users, hiring, signals, etc.)"

        # Refresh demo data with current-week dates so dashboards look fresh
        echo -e "  Refreshing demo data with current dates..."
        run_with_retry "Demo data refresh" sudo $COMPOSE exec -T app \
            php artisan demo:refresh --weeks=3 --with-slack 2>&1
        print_step "Demo data refreshed with current-week dates"
    else
        # Fallback: run seeders if dump file not found
        print_warn "demo-data.sql not found, falling back to seeders..."
        run_with_retry "Database seed" sudo $COMPOSE exec -T app php artisan db:seed --force
        print_step "Demo data seeded (basic)"
    fi
else
    # Production: create super admin only
    ADMIN_PASS=$(ask_default "Super admin password" "NalamAdmin2026!")
    run_with_retry "Super admin creation" sudo $COMPOSE exec -T app php artisan tinker --execute="
        \$u = \App\Models\User::create([
            'name' => 'Platform Admin',
            'email' => 'admin@nalampulse.com',
            'password' => '${ADMIN_PASS}',
            'role' => 'super_admin',
            'organization_id' => null,
            'is_active' => true,
        ]);
        \App\Models\UserRole::create(['user_id' => \$u->id, 'role' => 'super_admin']);
        echo 'Super admin created.';
    "
    print_step "Super admin created (admin@nalampulse.com)"
fi

# ─── Step 7: SSL (optional) ──────────────────────────────────────────────────
print_header "Step 6/6 — SSL Certificate"

if [[ "${SETUP_SSL,,}" == "y" ]]; then
    if ! command -v certbot &>/dev/null; then
        sudo apt-get install -y -qq certbot > /dev/null 2>&1
    fi

    sudo systemctl stop nginx
    if sudo certbot certonly --standalone -d "$DOMAIN" --non-interactive --agree-tos -m "admin@${DOMAIN}"; then
        # Switch nginx to SSL config
        sudo tee /etc/nginx/sites-available/nalam > /dev/null <<NGINX_SSL
server {
    listen 80;
    server_name ${DOMAIN};
    return 301 https://\$host\$request_uri;
}

server {
    listen 443 ssl http2;
    server_name ${DOMAIN};

    ssl_certificate     /etc/letsencrypt/live/${DOMAIN}/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/${DOMAIN}/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;
    ssl_prefer_server_ciphers on;

    client_max_body_size 50M;

    location / {
        proxy_pass http://127.0.0.1:8080;
        proxy_set_header Host \$host;
        proxy_set_header X-Real-IP \$remote_addr;
        proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto https;
        proxy_read_timeout 300s;
    }
}
NGINX_SSL
        sudo nginx -t > /dev/null 2>&1
        print_step "SSL certificate installed for ${DOMAIN}"
    else
        print_warn "SSL failed — make sure DNS points ${DOMAIN} to this server first"
        print_warn "You can run SSL later: sudo certbot certonly --standalone -d ${DOMAIN}"
    fi
    sudo systemctl start nginx
else
    print_warn "SSL skipped. Set up later with:"
    echo "  sudo certbot certonly --standalone --pre-hook 'systemctl stop nginx' --post-hook 'systemctl start nginx' -d ${DOMAIN}"
    echo "  Then update /etc/nginx/sites-available/nalam with SSL config"
fi

# ─── Done ─────────────────────────────────────────────────────────────────────
print_header "Installation Complete!"

echo -e "  ${GREEN}Server IP:${NC}  ${SERVER_IP}"
echo -e "  ${GREEN}Domain:${NC}     ${DOMAIN}"
echo -e "  ${GREEN}HTTP:${NC}       http://${SERVER_IP}"
if [[ "${SETUP_SSL,,}" == "y" ]]; then
    echo -e "  ${GREEN}HTTPS:${NC}      https://${DOMAIN}"
fi
echo ""

if [[ "$SEED_DATA" == true ]]; then
    echo -e "  ${YELLOW}Login credentials:${NC}"
    echo ""
    echo -e "  ${CYAN}Acme Technologies — Payment Plans Demo (Jira + GitHub):${NC}"
    echo "  ┌──────────────────┬──────────────────────┬──────────┐"
    echo "  │ Role             │ Email                │ Password │"
    echo "  ├──────────────────┼──────────────────────┼──────────┤"
    echo "  │ Super Admin      │ admin@nalampulse.com │ password │"
    echo "  │ Org Admin        │ admin@acme.com       │ password │"
    echo "  │ HR Manager       │ hr@acme.com          │ password │"
    echo "  │ Resource Manager │ rm@acme.com          │ password │"
    echo "  └──────────────────┴──────────────────────┴──────────┘"
    echo ""
    echo -e "  ${CYAN}Nalam Systems — Full Features Demo (Jira + Slack):${NC}"
    echo "  ┌──────────────────┬──────────────────────────────────┬────────────────────┐"
    echo "  │ Role             │ Email                            │ Password           │"
    echo "  ├──────────────────┼──────────────────────────────────┼────────────────────┤"
    echo "  │ Org Admin        │ admin@nalamsystems.work          │ NalamDemo@Systems1 │"
    echo "  │ HR Manager       │ hrm@nalamsystems.work            │ NalamDemo@Systems1 │"
    echo "  │ Resource Manager │ pm@nalamsystems.work             │ NalamDemo@Systems1 │"
    echo "  └──────────────────┴──────────────────────────────────┴────────────────────┘"
    echo ""
    echo -e "  ${CYAN}Nalam Tech — Microsoft Stack Demo (Azure DevOps + Teams):${NC}"
    echo "  ┌──────────────────┬──────────────────────────────────┬────────────────────┐"
    echo "  │ Role             │ Email                            │ Password           │"
    echo "  ├──────────────────┼──────────────────────────────────┼────────────────────┤"
    echo "  │ Org Admin        │ kevin.lee@nalamtech.work         │ NalamDemo@Tech1    │"
    echo "  │ Resource Manager │ omar.ali@nalamtech.work          │ NalamDemo@Tech1    │"
    echo "  └──────────────────┴──────────────────────────────────┴────────────────────┘"
    echo ""
    echo -e "  ${CYAN}Nalam Labs — Zoho Stack Demo (Zoho Projects + Slack):${NC}"
    echo "  ┌──────────────────┬──────────────────────────────────┬────────────────────┐"
    echo "  │ Role             │ Email                            │ Password           │"
    echo "  ├──────────────────┼──────────────────────────────────┼────────────────────┤"
    echo "  │ Org Admin        │ ravi.das@nalamlabs.work          │ NalamDemo@Systems1 │"
    echo "  │ Resource Manager │ sara.noor@nalamlabs.work         │ NalamDemo@Systems1 │"
    echo "  └──────────────────┴──────────────────────────────────┴────────────────────┘"
    echo ""
    echo -e "  ${GREEN}Auto-refresh:${NC} Demo data refreshes automatically every Monday at 3 AM"
else
    echo -e "  ${YELLOW}Login:${NC} admin@nalampulse.com / (your chosen password)"
fi

echo ""
echo -e "  ${YELLOW}DNS:${NC} Point ${DOMAIN} A record → ${SERVER_IP}"
echo ""
echo -e "  ${YELLOW}Useful commands:${NC}"
echo "  cd /opt/nalam"
echo "  export DC='docker compose --env-file deploy/gcp/.env -f deploy/gcp/docker-compose.build.yml'"
echo "  sudo \$DC logs -f        # View logs"
echo "  sudo \$DC restart app    # Restart app"
echo "  sudo \$DC down && sudo \$DC up -d  # Full restart"
echo ""
echo -e "  ${GREEN}AI Settings:${NC} Configure OpenAI/Azure keys via Settings → AI Configuration in the app"
echo ""
print_step "All done! Open http://${SERVER_IP} in your browser."
