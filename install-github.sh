#!/bin/bash

# ============================================================================
# INSTALADOR AUTOMÁTICO - GERENCIADOR FTTH v2.0
# Via GitHub - Comando Único
# Uso: bash <(curl -s https://raw.githubusercontent.com/SEU-USUARIO/caixas-addon/main/install.sh)
# ============================================================================

set -e

ADDON_NAME="caixas"
ADDON_PATH="/opt/mk-auth/admin/addons/$ADDON_NAME"
ADDON_OWNER="www-data"
VERSION="2.0"

# ✏️  EDITE AQUI COM SEU GITHUB
GITHUB_USER="SEU-USUARIO-GITHUB"
GITHUB_REPO="caixas-addon"
GITHUB_BRANCH="main"

# URL final
REPO_URL="https://github.com/${GITHUB_USER}/${GITHUB_REPO}/archive/refs/heads/${GITHUB_BRANCH}.tar.gz"

echo "🚀 Instalador Automático - GERENCIADOR FTTH v${VERSION}"
echo "🐙 GitHub: ${GITHUB_USER}/${GITHUB_REPO}"
echo "═══════════════════════════════════════════════════════════"
echo ""

# 1. Verificar se mkauth existe
if [ ! -d "/opt/mk-auth/admin/addons" ]; then
    echo "❌ Erro: mkauth não encontrado em /opt/mk-auth"
    exit 1
fi

# 2. Fazer backup se addon existir
if [ -d "$ADDON_PATH" ]; then
    echo "⚠️  Addon já existe. Fazendo backup..."
    BACKUP_DIR="$ADDON_PATH-backup-$(date +%Y%m%d-%H%M%S)"
    cp -r "$ADDON_PATH" "$BACKUP_DIR"
    echo "✅ Backup: $BACKUP_DIR"
    rm -rf "$ADDON_PATH"
fi

# 3. Criar pasta
mkdir -p "$ADDON_PATH"
cd /tmp

# 4. Baixar do GitHub
echo "📥 Baixando do GitHub..."
echo "URL: $REPO_URL"
echo ""

if ! command -v curl &> /dev/null; then
    echo "❌ Erro: curl não instalado"
    exit 1
fi

# Baixar com retentativa
for i in {1..3}; do
    echo "Tentativa $i/3..."
    if curl -L --progress-bar -o caixas-github.tar.gz "$REPO_URL" 2>/dev/null; then
        if [ -f "caixas-github.tar.gz" ] && [ -s "caixas-github.tar.gz" ]; then
            echo "✅ Download concluído"
            break
        fi
    fi
    if [ $i -lt 3 ]; then
        echo "⏳ Aguardando 2 segundos antes de tentar novamente..."
        sleep 2
    fi
done

if [ ! -f "caixas-github.tar.gz" ]; then
    echo "❌ Erro: Falha ao baixar do GitHub"
    exit 1
fi

# 5. Extrair
echo "📦 Extraindo arquivos..."
tar -xzf caixas-github.tar.gz

# A estrutura do GitHub é: ${GITHUB_REPO}-${GITHUB_BRANCH}/
EXTRACTED_DIR="${GITHUB_REPO}-${GITHUB_BRANCH}"

if [ -d "$EXTRACTED_DIR" ]; then
    cp -r "$EXTRACTED_DIR"/* "$ADDON_PATH/" 2>/dev/null || true
    rm -rf "$EXTRACTED_DIR" caixas-github.tar.gz
    echo "✅ Arquivos extraídos"
else
    echo "⚠️  Estrutura diferente, copiando tudo..."
    cp -r ./* "$ADDON_PATH/" 2>/dev/null || true
    rm -rf caixas-github.tar.gz
fi

# 6. Ajustar permissões
echo "🔐 Ajustando permissões..."
chown -R $ADDON_OWNER:$ADDON_OWNER "$ADDON_PATH" 2>/dev/null || true
chmod -R 755 "$ADDON_PATH" 2>/dev/null || true
find "$ADDON_PATH" -type f \( -name "*.php" -o -name "*.hhvm" \) -exec chmod 644 {} \; 2>/dev/null || true

# 7. Verificar instalação
if [ ! -f "$ADDON_PATH/manifest.json" ]; then
    echo "❌ Erro: manifest.json não encontrado"
    echo "Verifique se seu repositório GitHub tem a estrutura correta:"
    echo "  caixas-addon/"
    echo "  ├── manifest.json"
    echo "  ├── index.php"
    echo "  ├── addons.class.php"
    echo "  └── src/"
    exit 1
fi

# 8. Resumo
echo ""
echo "═══════════════════════════════════════════════════════════"
echo "✅ INSTALAÇÃO CONCLUÍDA COM SUCESSO!"
echo "═══════════════════════════════════════════════════════════"
echo ""
echo "📍 Addon instalado em: $ADDON_PATH"
echo "🌐 Acesse: http://seu-servidor/admin/addons/"
echo "🐙 GitHub: https://github.com/${GITHUB_USER}/${GITHUB_REPO}"
echo ""

