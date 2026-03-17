#!/bin/bash
# =============================================================================
# Nalam Pulse — Create GCP VM + Run Installer
#
# Run this from Google Cloud Shell:
#   git clone https://github.com/karthikeyansemail/talent.git /tmp/nalam
#   bash /tmp/nalam/deploy/gcp/create-vm.sh
#
# This creates the VM, uploads code, and runs install.sh on it.
# Total time: ~8-10 minutes.
# =============================================================================
set -euo pipefail

RED='\033[0;31m'; GREEN='\033[0;32m'; YELLOW='\033[1;33m'; CYAN='\033[0;36m'; NC='\033[0m'
print_header() { echo -e "\n${CYAN}═══════════════════════════════════════════════${NC}"; echo -e "${CYAN}  $1${NC}"; echo -e "${CYAN}═══════════════════════════════════════════════${NC}\n"; }
ask_default()  { local v; read -p "  $1 [$2]: " v; echo "${v:-$2}"; }

print_header "Nalam Pulse — GCP VM Setup"

# ── Gather inputs ─────────────────────────────────────────────────────────────
PROJECT=$(gcloud config get-value project 2>/dev/null || echo "")
PROJECT=$(ask_default "GCP Project ID" "$PROJECT")
VM_NAME=$(ask_default "VM name" "nalam-pulse")
ZONE=$(ask_default "Zone" "us-central1-a")
MACHINE=$(ask_default "Machine type" "e2-medium")
DISK=$(ask_default "Boot disk size (GB)" "30")

REGION="${ZONE%-*}"

echo ""
echo -e "${YELLOW}Will create:${NC}"
echo "  Project:  $PROJECT"
echo "  VM:       $VM_NAME ($MACHINE) in $ZONE"
echo "  Disk:     ${DISK}GB SSD"
echo ""
read -p "  Proceed? [Y/n]: " CONFIRM
[[ "${CONFIRM,,}" != "n" ]] || { echo "Aborted."; exit 0; }

gcloud config set project "$PROJECT"

# ── 1. Reserve static IP ─────────────────────────────────────────────────────
echo ""
echo -e "${GREEN}[1/5] Reserving static IP...${NC}"
gcloud compute addresses create "${VM_NAME}-ip" \
    --region="$REGION" --quiet 2>/dev/null || true

STATIC_IP=$(gcloud compute addresses describe "${VM_NAME}-ip" \
    --region="$REGION" --format="value(address)")
echo -e "  ${GREEN}Static IP: ${STATIC_IP}${NC}"

# ── 2. Firewall ───────────────────────────────────────────────────────────────
echo -e "${GREEN}[2/5] Firewall rules...${NC}"
gcloud compute firewall-rules create "${VM_NAME}-allow-web" \
    --allow tcp:80,tcp:443,tcp:22 \
    --target-tags "${VM_NAME}" \
    --quiet 2>/dev/null || echo "  (rule exists)"

# ── 3. Create VM ──────────────────────────────────────────────────────────────
echo -e "${GREEN}[3/5] Creating VM...${NC}"
gcloud compute instances create "${VM_NAME}" \
    --zone="$ZONE" \
    --machine-type="$MACHINE" \
    --image-family=ubuntu-2204-lts \
    --image-project=ubuntu-os-cloud \
    --boot-disk-size="${DISK}GB" \
    --boot-disk-type=pd-ssd \
    --address="$STATIC_IP" \
    --tags="${VM_NAME}" \
    --quiet 2>/dev/null || echo "  (VM exists)"

echo "  Waiting for VM SSH to be ready (up to 90s)..."
TRIES=0
while true; do
    TRIES=$((TRIES+1))
    if gcloud compute ssh "$VM_NAME" --zone="$ZONE" --command="echo ok" 2>/dev/null; then
        break
    fi
    if [[ $TRIES -ge 15 ]]; then
        echo -e "${RED}  SSH not ready after 90s. Try manually:${NC}"
        echo "  gcloud compute ssh $VM_NAME --zone=$ZONE"
        exit 1
    fi
    sleep 6
done

# ── 4. Upload code to VM ─────────────────────────────────────────────────────
echo -e "${GREEN}[4/5] Uploading code to VM...${NC}"

# Clone directly on the VM (faster than scp)
gcloud compute ssh "$VM_NAME" --zone="$ZONE" --command="
    sudo rm -rf /opt/nalam 2>/dev/null || true
    sudo git clone https://github.com/karthikeyansemail/talent.git /opt/nalam
    sudo chown -R \$(whoami):\$(whoami) /opt/nalam
    echo 'Code uploaded.'
"

# ── 5. Run installer on VM ───────────────────────────────────────────────────
echo -e "${GREEN}[5/5] Running installer on VM...${NC}"
echo ""
echo -e "${YELLOW}═══════════════════════════════════════════════${NC}"
echo -e "${YELLOW}  Connecting to VM — the installer will ask${NC}"
echo -e "${YELLOW}  you a few questions (domain, demo/prod, etc)${NC}"
echo -e "${YELLOW}═══════════════════════════════════════════════${NC}"
echo ""
echo -e "  ${CYAN}Your static IP is: ${STATIC_IP}${NC}"
echo -e "  ${CYAN}Point your domain's A record to this IP.${NC}"
echo ""

gcloud compute ssh "$VM_NAME" --zone="$ZONE" -- "bash /opt/nalam/deploy/gcp/install.sh --local"

echo ""
echo -e "${GREEN}═══════════════════════════════════════════════${NC}"
echo -e "${GREEN}  VM: ${VM_NAME}${NC}"
echo -e "${GREEN}  IP: ${STATIC_IP}${NC}"
echo -e "${GREEN}  SSH: gcloud compute ssh ${VM_NAME} --zone=${ZONE}${NC}"
echo -e "${GREEN}═══════════════════════════════════════════════${NC}"
