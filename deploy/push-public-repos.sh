#!/bin/bash
# =============================================================================
# Run this from the root of your private repo AFTER:
#   1. Creating github.com/nalampulse/deploy (public)
#   2. Creating github.com/nalampulse/ai-service (public)
#
# Usage:
#   bash deploy/push-public-repos.sh
# =============================================================================
set -euo pipefail

PRIVATE_ROOT="$(cd "$(dirname "$0")/.." && pwd)"
WORK_DIR="$(mktemp -d)"

echo "Working in: ${WORK_DIR}"

# ── Push deploy repo ──────────────────────────────────────────────────────────
echo ""
echo "[1/2] Pushing nalampulse/deploy..."
mkdir -p "${WORK_DIR}/deploy"
cp -r "${PRIVATE_ROOT}/deploy/." "${WORK_DIR}/deploy/"

cd "${WORK_DIR}/deploy"
git init
git add .
git commit -m "chore: update deployment templates"
git remote add origin https://github.com/nalampulse/deploy.git
git push origin HEAD:main --force

echo "✓ nalampulse/deploy updated"

# ── Push ai-service repo ──────────────────────────────────────────────────────
echo ""
echo "[2/2] Pushing nalampulse/ai-service..."
mkdir -p "${WORK_DIR}/ai-service"
cp -r "${PRIVATE_ROOT}/ai-service/." "${WORK_DIR}/ai-service/"

# Remove any .env or secret files if accidentally present
rm -f "${WORK_DIR}/ai-service/.env"

cd "${WORK_DIR}/ai-service"
git init
git add .
git commit -m "chore: sync ai-service from main repo"
git remote add origin https://github.com/nalampulse/ai-service.git
git push origin HEAD:main --force

echo "✓ nalampulse/ai-service updated"

# ── Cleanup ───────────────────────────────────────────────────────────────────
rm -rf "${WORK_DIR}"
echo ""
echo "Done. Both public repos are up to date."
echo "  https://github.com/nalampulse/deploy"
echo "  https://github.com/nalampulse/ai-service"
