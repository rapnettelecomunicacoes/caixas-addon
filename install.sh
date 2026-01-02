#!/bin/bash

# GERENCIADOR FTTH v2.0 - Script de Instalação
# GitHub: https://github.com/rapnettelecomunicacoes/caixas-addon

GITHUB_URL="https://raw.githubusercontent.com/rapnettelecomunicacoes/caixas-addon/main"
ZIP_URL="$GITHUB_URL/caixas-addon-v2.0.zip"
ADDON_DIR="/opt/mk-auth/admin/addons/caixas"
ADDON_JS="/opt/mk-auth/admin/addons/addon.js"
TEMP_DIR="/tmp/caixas_install_$$"

echo "==============================================="
echo "Instalando GERENCIADOR FTTH v2.0..."
echo "==============================================="

# Criar diretório temporário
mkdir -p "$TEMP_DIR"
cd "$TEMP_DIR"

# Baixar ZIP
echo "⏳ Baixando addon..."
wget -q "$ZIP_URL" -O caixas-addon-v2.0.zip 2>/dev/null || curl -s -o caixas-addon-v2.0.zip "$ZIP_URL"

if [ ! -f caixas-addon-v2.0.zip ]; then
    echo "❌ Erro ao baixar o arquivo ZIP"
    exit 1
fi

# Extrair para pasta temporária
echo "⏳ Extraindo arquivos..."
unzip -q caixas-addon-v2.0.zip

# Mover para local final (remove pasta raiz do ZIP)
if [ -d "caixas" ]; then
    # Se tem pasta caixas, move o conteúdo
    rm -rf "$ADDON_DIR"
    mv caixas "$ADDON_DIR"
else
    # Se não tem, os arquivos já estão aqui
    rm -rf "$ADDON_DIR"
    mkdir -p "$ADDON_DIR"
    mv * "$ADDON_DIR/" 2>/dev/null || true
fi

# Limpar temporário
cd /
rm -rf "$TEMP_DIR"

# Permissões
echo "⏳ Configurando permissões..."
chown -R www-data:www-data "$ADDON_DIR"
chmod -R 755 "$ADDON_DIR"

# Registrar no menu
if ! grep -q "Gerenciador FTTH" "$ADDON_JS"; then
    echo "⏳ Registrando no menu..."
    cat >> "$ADDON_JS" << 'ADDON_CODE'
const caixas = window.location.protocol + "//" + window.location.hostname + (window.location.port ? ':' + window.location.port: '') + "/admin/addons/caixas/";
add_menu.opcoes('{"plink": "' + caixas + '?_route=painel", "ptext": "Gerenciador FTTH"}');
ADDON_CODE
fi

echo ""
echo "✅ Instalação concluída!"
echo "📍 URL: /admin/addons/caixas/?_route=painel"
echo "==============================================="
