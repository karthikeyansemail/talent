#!/bin/bash
# =============================================================================
# Nalam Pulse — DEMO instance setup (demo.nalampulse.com)
# Run this in Google Cloud Shell.
# Project: fiery-province-425807-v3
# =============================================================================
set -euo pipefail

PROJECT="fiery-province-425807-v3"
ZONE="us-central1-a"
REGION="us-central1"
VM_NAME="nalam-demo"
MACHINE="e2-medium"
DOMAIN="demo.nalampulse.com"

echo ""
echo "============================================"
echo "  Nalam Pulse DEMO — GCP Deployment"
echo "  Project: ${PROJECT}"
echo "  Domain:  ${DOMAIN}"
echo "============================================"
echo ""

gcloud config set project "${PROJECT}"

# ── 1. Reserve static IP ──────────────────────────────────────────────────────
echo "[1/5] Reserving static IP..."
gcloud compute addresses create "${VM_NAME}-ip" \
    --region="${REGION}" --quiet 2>/dev/null || echo "  (reusing existing)"

STATIC_IP=$(gcloud compute addresses describe "${VM_NAME}-ip" \
    --region="${REGION}" --format="value(address)")
echo "  Static IP: ${STATIC_IP}"
echo ""
echo "  >>> ADD DNS NOW: ${DOMAIN} → ${STATIC_IP}"
echo "  >>> (you can continue while DNS propagates)"
echo ""

# ── 2. Firewall ───────────────────────────────────────────────────────────────
echo "[2/5] Firewall rules..."
gcloud compute firewall-rules create "${VM_NAME}-allow-web" \
    --allow tcp:80,tcp:443,tcp:22 \
    --target-tags "${VM_NAME}" \
    --quiet 2>/dev/null || echo "  (rule exists)"

# ── 3. Create VM ──────────────────────────────────────────────────────────────
echo "[3/5] Creating VM..."
gcloud compute instances create "${VM_NAME}" \
    --zone="${ZONE}" \
    --machine-type="${MACHINE}" \
    --image-family=ubuntu-2204-lts \
    --image-project=ubuntu-os-cloud \
    --boot-disk-size=30GB \
    --boot-disk-type=pd-ssd \
    --address="${STATIC_IP}" \
    --tags="${VM_NAME}" \
    --quiet 2>/dev/null || echo "  (VM already exists)"

echo "  VM ready. Waiting 15s for SSH..."
sleep 15

# ── 4. Install Docker on the VM ───────────────────────────────────────────────
echo "[4/5] Installing Docker on VM..."
gcloud compute ssh "${VM_NAME}" --zone="${ZONE}" --command="
    sudo apt-get update -qq
    sudo apt-get install -y -qq docker.io docker-compose-v2 git certbot > /dev/null
    sudo systemctl enable docker
    sudo systemctl start docker
    sudo usermod -aG docker \$(whoami)
    echo 'Docker installed.'
"

# ── 5. Done — print next steps ─────────────────────────────────────────────────
echo ""
echo "============================================"
echo "  VM READY: ${VM_NAME}"
echo "  IP: ${STATIC_IP}"
echo ""
echo "  NEXT STEPS (copy-paste these):"
echo ""
echo "  1. Upload your code to the VM:"
echo "     cd /path/to/talent"
echo "     gcloud compute scp --recurse --zone=${ZONE} \\"
echo "       --compress . ${VM_NAME}:/tmp/talent-upload"
echo ""
echo "     OR if you have a git repo:"
echo "     gcloud compute ssh ${VM_NAME} --zone=${ZONE} -- \\"
echo "       'git clone https://github.com/YOUR/talent.git /opt/nalam'"
echo ""
echo "  2. SSH into VM and run the deploy:"
echo "     gcloud compute ssh ${VM_NAME} --zone=${ZONE}"
echo ""
echo "  3. On the VM, run:"
echo "     sudo mv /tmp/talent-upload /opt/nalam  # if you used scp"
echo "     cd /opt/nalam"
echo "     # Set DB password:"
echo "     export DB_PASSWORD='CHANGE_ME_STRONG_PASSWORD'"
echo "     export APP_URL='https://${DOMAIN}'"
echo "     export APP_NAME='Nalam Pulse Demo'"
echo "     # Build and start:"
echo "     docker compose -f deploy/gcp/docker-compose.build.yml up -d --build"
echo "     # Wait ~2 min for build, then seed demo data:"
echo "     docker exec nalam-demo-app-1 php artisan db:seed --force"
echo "     # SSL:"
echo "     sudo certbot certonly --standalone -d ${DOMAIN}"
echo ""
echo "  4. Login at https://${DOMAIN}"
echo "     admin@nalampulse.com / password"
echo "     admin@acme.com / password"
echo "============================================"
