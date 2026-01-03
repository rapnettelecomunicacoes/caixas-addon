#!/bin/bash

# ============================================================================
# INSTALADOR AUTOMÁTICO - GERENCIADOR FTTH v2.0 (FROM ZIP)
# Download e extrai do GitHub, depois instala
# ============================================================================

set -e

ADDON_NAME="caixas"
ADDON_PATH="/opt/mk-auth/admin/addons/$ADDON_NAME"
ADDON_OWNER="www-data"
TEMP_DIR="/tmp/caixas-$$"

# Cores
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

echo -e "${BLUE}╔═══════════════════════════════════════════════════════════╗${NC}"
echo -e "${BLUE}║     INSTALADOR - GERENCIADOR FTTH v2.0 (FROM ZIP)         ║${NC}"
echo -e "${BLUE}║     Baixando e instalando do GitHub                       ║${NC}"
echo -e "${BLUE}╚═══════════════════════════════════════════════════════════╝${NC}"
echo ""

# 1. Criar diretório temporário
echo -e "${YELLOW}ℹ️  Criando diretório temporário...${NC}"
mkdir -p "$TEMP_DIR"
cd "$TEMP_DIR"

# 2. Baixar repositório
echo -e "${YELLOW}ℹ️  Baixando repositório do GitHub...${NC}"
if ! curl -sSL https://github.com/rapnettelecomunicacoes/caixas-addon/archive/refs/heads/main.zip -o addon.zip; then
    echo -e "${RED}❌ Erro ao baixar repositório${NC}"
    rm -rf "$TEMP_DIR"
    exit 1
fi

# 3. Extrair ZIP
echo -e "${YELLOW}ℹ️  Extraindo arquivos...${NC}"
unzip -q addon.zip

# 4. Encontrar diretório caixas
CAIXAS_DIR=$(find . -maxdepth 3 -type d -name "caixas" | head -1)
if [ ! -d "$CAIXAS_DIR" ]; then
    echo -e "${RED}❌ Diretório 'caixas' não encontrado${NC}"
    rm -rf "$TEMP_DIR"
    exit 1
fi

# 5. Criar diretório pai
echo -e "${YELLOW}ℹ️  Preparando instalação...${NC}"
mkdir -p "$(dirname "$ADDON_PATH")"

# 6. Backup se existir
if [ -d "$ADDON_PATH" ]; then
    BACKUP_DIR="${ADDON_PATH}.backup.$(date +%Y%m%d_%H%M%S)"
    echo -e "${YELLOW}⚠️  Realizando backup de instalação anterior...${NC}"
    cp -r "$ADDON_PATH" "$BACKUP_DIR"
    echo -e "${GREEN}✅ Backup realizado em: $BACKUP_DIR${NC}"
    rm -rf "$ADDON_PATH"
fi

# 7. Instalar
echo -e "${YELLOW}ℹ️  Copiando arquivos...${NC}"
cp -r "$CAIXAS_DIR" "$ADDON_PATH"

# 8. Definir permissões
echo -e "${YELLOW}ℹ️  Configurando permissões...${NC}"
chown -R "$ADDON_OWNER:$ADDON_OWNER" "$ADDON_PATH"
chmod 755 "$ADDON_PATH"
find "$ADDON_PATH" -type f -exec chmod 644 {} \;
find "$ADDON_PATH" -type d -exec chmod 755 {} \;

# 9. Verificar instalação
if [ -f "$ADDON_PATH/index.php" ]; then
    echo -e "${GREEN}✅ Instalação concluída com sucesso!${NC}"
    echo ""
    echo -e "${BLUE}Próximos passos:${NC}"
    echo "1. Execute: sudo $ADDON_PATH/configure-server.sh"
    echo "2. Abra seu navegador em: http://seu-servidor/mkauth"
    echo "3. Vá para: Addons → GERENCIADOR FTTH"
    echo ""
else
    echo -e "${RED}❌ Arquivo index.php não encontrado. Algo deu errado.${NC}"
    rm -rf "$TEMP_DIR"
    exit 1
fi

# 10. Limpar
rm -rf "$TEMP_DIR"

echo -e "${GREEN}✅ Tudo pronto!${NC}"
