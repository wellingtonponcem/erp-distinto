#!/bin/bash
# ============================================================
#  deploy.sh — Envia commits locais para o GitHub e atualiza
#              o servidor Hostinger via SSH
# ============================================================
#  Uso:
#    ./deploy.sh                  → só sincroniza (pull no server)
#    ./deploy.sh "mensagem"       → commit + push + pull no server
# ============================================================

set -e

SERVER_USER="u306254544"
SERVER_HOST="147.93.38.189"
SERVER_PORT="65002"
SERVER_PATH="/home/u306254544/domains/wedistinto.com/public_html/sistema"
SSH_PASS='!@Jeane&w#1'

# ── Cores ──────────────────────────────────────────────────
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

ok()   { echo -e "${GREEN}✓ $1${NC}"; }
info() { echo -e "${YELLOW}→ $1${NC}"; }
err()  { echo -e "${RED}✗ $1${NC}"; exit 1; }

# ── 1. Commit + Push (opcional) ────────────────────────────
if [ -n "$1" ]; then
    info "Adicionando arquivos ao stage..."
    git add -A

    info "Criando commit: $1"
    git commit -m "$1" || { echo "Nada para commitar."; }

    info "Enviando para o GitHub..."
    git push origin main
    ok "GitHub atualizado."
else
    info "Nenhuma mensagem de commit fornecida — pulando etapa de commit/push."
fi

# ── 2. Pull no servidor ────────────────────────────────────
info "Conectando ao servidor Hostinger..."
SSHPASS="$SSH_PASS" sshpass -e ssh -p "$SERVER_PORT" \
    -o StrictHostKeyChecking=no \
    "$SERVER_USER@$SERVER_HOST" \
    "cd $SERVER_PATH && git pull origin main 2>&1"

ok "Servidor atualizado com sucesso!"
echo ""
echo "  wedistinto.com         → landing page"
echo "  wedistinto.com/sistema → ERP (login)"
echo ""
