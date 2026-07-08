#!/bin/bash
# ============================================================
#  deploy.sh — Deploy para o servidor Hostinger via SSH
# ============================================================
#  Uso:
#    ./deploy.sh                  → só sincroniza servidor
#    ./deploy.sh "mensagem"       → commit + push + sync servidor
# ============================================================

set -e

SERVER_USER="u306254544"
SERVER_HOST="147.93.38.189"
SERVER_PORT="65002"
SSH_KEY="$HOME/.ssh/id_rsa"

# Caminhos no servidor
PATH_ERP="/home/u306254544/domains/wedistinto.com/public_html/sistema"
PATH_SITE="/home/u306254544/domains/wedistinto.com/public_html"
PATH_ROTEIROS="/home/u306254544/domains/wedistinto.com/public_html/roteiros"

GREEN='\033[0;32m'; YELLOW='\033[1;33m'; NC='\033[0m'
ok()   { echo -e "${GREEN}✓ $1${NC}"; }
info() { echo -e "${YELLOW}→ $1${NC}"; }

ssh_cmd() {
    ssh -p "$SERVER_PORT" -i "$SSH_KEY" -o StrictHostKeyChecking=no \
        "$SERVER_USER@$SERVER_HOST" "$1"
}

# ── 1. Commit + Push (opcional) ────────────────────────────
# Nota: Este script faz push apenas do repositório onde é executado (ERP)
if [ -n "$1" ]; then
    info "Adicionando ao stage..."
    git add -A
    git commit -m "$1" || echo "Nada para commitar."
    info "Enviando para o GitHub..."
    git push origin main
    ok "GitHub atualizado."
fi

# ── 2. Sync no servidor ─────────────────────────────────────
info "Atualizando servidor..."
ssh_cmd "
    echo '→ ERP (erp-distinto)...'
    cd $PATH_ERP && git pull origin main 2>&1 | tail -3

    echo '→ Roteiros (meus-roteiros)...'
    cd $PATH_ROTEIROS && git pull origin main 2>&1 | tail -3

    echo '→ Site (distinto-site)...'
    cd $PATH_SITE && git pull origin main 2>&1 | tail -3
"

ok "Servidor atualizado!"
echo ""
echo "  wedistinto.com         → site da agência"
echo "  wedistinto.com/sistema → ERP"
echo "  wedistinto.com/roteiros → roteiros"
