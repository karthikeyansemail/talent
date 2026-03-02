#!/bin/bash
# =============================================================================
# Nalam Pulse — GCP Deployment Script
# Run this inside Google Cloud Shell (opened via the "Deploy to GCP" button)
# =============================================================================

set -euo pipefail

echo ""
echo "=========================================="
echo "  Nalam Pulse — GCP Deployment"
echo "=========================================="
echo ""

# ── Collect inputs interactively ─────────────────────────────────────────────
read -p "Project ID       [$(gcloud config get-value project 2>/dev/null)]: " INPUT_PROJECT
GCP_PROJECT="${INPUT_PROJECT:-$(gcloud config get-value project)}"

read -p "Zone             [us-central1-a]: " INPUT_ZONE
GCP_ZONE="${INPUT_ZONE:-us-central1-a}"
GCP_REGION="${GCP_ZONE%-*}"

read -p "VM name          [nalam-pulse]: " INPUT_NAME
VM_NAME="${INPUT_NAME:-nalam-pulse}"

read -p "Machine type     [e2-medium]:    " INPUT_TYPE
MACHINE_TYPE="${INPUT_TYPE:-e2-medium}"

echo ""
echo "-- Nalam Pulse configuration --"
read -p "License key:     " NALAM_LICENSE
read -p "Domain name:     " NALAM_DOMAIN
read -p "SMTP host:       " NALAM_SMTP_HOST
read -p "SMTP port [587]: " INPUT_SMTP_PORT
NALAM_SMTP_PORT="${INPUT_SMTP_PORT:-587}"
read -p "SMTP username:   " NALAM_SMTP_USER
read -s -p "SMTP password:   " NALAM_SMTP_PASS; echo ""
read -s -p "OpenAI API key:  " NALAM_AI_KEY; echo ""
echo ""

# ── Confirm ───────────────────────────────────────────────────────────────────
echo ""
echo "About to create:"
echo "  Project : ${GCP_PROJECT}"
echo "  VM      : ${VM_NAME} (${MACHINE_TYPE}) in ${GCP_ZONE}"
echo "  Domain  : ${NALAM_DOMAIN}"
echo ""
read -p "Proceed? [y/N]: " CONFIRM
[[ "${CONFIRM,,}" == "y" ]] || { echo "Aborted."; exit 0; }

# ── Set project ───────────────────────────────────────────────────────────────
gcloud config set project "${GCP_PROJECT}"

# ── Reserve a static IP ───────────────────────────────────────────────────────
echo ""
echo "[1/4] Reserving static external IP..."
gcloud compute addresses create "${VM_NAME}-ip" \
    --region="${GCP_REGION}" \
    --quiet 2>/dev/null || echo "  (address already exists, reusing)"

STATIC_IP=$(gcloud compute addresses describe "${VM_NAME}-ip" \
    --region="${GCP_REGION}" --format="value(address)")
echo "  Static IP: ${STATIC_IP}"

# ── Create firewall rules ─────────────────────────────────────────────────────
echo "[2/4] Configuring firewall rules..."
gcloud compute firewall-rules create "${VM_NAME}-allow-web" \
    --allow tcp:80,tcp:443,tcp:22 \
    --target-tags "${VM_NAME}" \
    --quiet 2>/dev/null || echo "  (firewall rule already exists)"

# ── Create VM with startup script ────────────────────────────────────────────
echo "[3/4] Creating VM (Ubuntu 22.04 LTS, ${MACHINE_TYPE})..."

STARTUP_SCRIPT="#!/bin/bash
export NALAM_LICENSE='${NALAM_LICENSE}'
export NALAM_DOMAIN='${NALAM_DOMAIN}'
export NALAM_SMTP_HOST='${NALAM_SMTP_HOST}'
export NALAM_SMTP_PORT='${NALAM_SMTP_PORT}'
export NALAM_SMTP_USER='${NALAM_SMTP_USER}'
export NALAM_SMTP_PASS='${NALAM_SMTP_PASS}'
export NALAM_AI_KEY='${NALAM_AI_KEY}'
curl -fsSL https://raw.githubusercontent.com/nalampulse/deploy/main/scripts/install.sh | bash"

gcloud compute instances create "${VM_NAME}" \
    --zone="${GCP_ZONE}" \
    --machine-type="${MACHINE_TYPE}" \
    --image-family=ubuntu-2204-lts \
    --image-project=ubuntu-os-cloud \
    --boot-disk-size=30GB \
    --boot-disk-type=pd-ssd \
    --address="${STATIC_IP}" \
    --tags="${VM_NAME}" \
    --metadata="startup-script=${STARTUP_SCRIPT}" \
    --quiet

# ── Output next steps ─────────────────────────────────────────────────────────
echo ""
echo "[4/4] VM created and installer is running in the background."
echo ""
echo "=========================================="
echo "  NEXT STEPS"
echo ""
echo "  1. Add DNS A record NOW:"
echo "     ${NALAM_DOMAIN}  →  ${STATIC_IP}"
echo ""
echo "  2. Wait ~10 minutes for Docker + SSL setup"
echo "     (You can monitor: gcloud compute ssh ${VM_NAME} --zone=${GCP_ZONE} -- tail -f /var/log/nalampulse-install.log)"
echo ""
echo "  3. Open: https://${NALAM_DOMAIN}"
echo "     Login: admin@acme.com / password"
echo "     CHANGE YOUR PASSWORD IMMEDIATELY"
echo "=========================================="
