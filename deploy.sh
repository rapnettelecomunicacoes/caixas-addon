#!/bin/bash

# ============================================================================
# DEPLOY RÁPIDO - GERENCIADOR FTTH
# Uso: ./deploy.sh usuario@servidor
# Exemplo: ./deploy.sh admin@192.168.1.100
# ============================================================================

if [ -z "$1" ]; then
    echo "Uso: $0 usuario@servidor"
    echo "Exemplo: $0 admin@192.168.1.100"
    exit 1
fi

REMOTE="$1"
ADDON_NAME="caixas"
VERSION="2.0"

echo "🚀 Iniciando deploy de GERENCIADOR FTTH v${VERSION}..."
echo "📍 Destino: $REMOTE"
echo ""

# 1. Criar arquivo compactado
echo "📦 Compactando addon..."
cd /opt/mk-auth/admin/addons
tar --exclude='.git' --exclude='*.tar.gz' --exclude='.DS_Store' \
    -czf ${ADDON_NAME}-v${VERSION}.tar.gz ${ADDON_NAME}/
echo "✅ Compactado: ${ADDON_NAME}-v${VERSION}.tar.gz"
echo ""

# 2. Transferir arquivo
echo "📤 Transferindo arquivo..."
scp ${ADDON_NAME}-v${VERSION}.tar.gz ${REMOTE}:/tmp/
if [ $? -ne 0 ]; then
    echo "❌ Erro na transferência via SCP"
    exit 1
fi
echo "✅ Arquivo transferido"
echo ""

# 3. Instalar no servidor remoto
echo "⚙️  Instalando no servidor remoto..."
ssh ${REMOTE} << 'EOF'
    cd /opt/mk-auth/admin/addons
    tar -xzf /tmp/caixas-v2.0.tar.gz
    sudo chown -R www-data:www-data caixas
    sudo chmod -R 755 caixas
    echo "✅ Instalação remota concluída"
EOF

if [ $? -ne 0 ]; then
    echo "❌ Erro na instalação remota"
    exit 1
fi
echo ""

# 4. Limpeza
echo "🧹 Limpando arquivos temporários..."
rm ${ADDON_NAME}-v${VERSION}.tar.gz
ssh ${REMOTE} "rm /tmp/${ADDON_NAME}-v${VERSION}.tar.gz"
echo "✅ Limpeza concluída"
echo ""

echo "🎉 Deploy concluído com sucesso!"
echo ""
echo "📍 Acesse o addon em:"
echo "   http://$(ssh ${REMOTE} hostname -I | awk '{print $1}')/admin/addons/"
