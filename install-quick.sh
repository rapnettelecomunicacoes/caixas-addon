#!/bin/bash

# ============================================================================
# INSTALADOR RÁPIDO - GERENCIADOR FTTH v2.0
# Para uso em terminal já logado no mkauth
# Uso: bash install-quick.sh
# ============================================================================

ADDON_NAME="caixas"
ADDON_PATH="/opt/mk-auth/admin/addons/$ADDON_NAME"
ADDON_OWNER="www-data"

echo "🚀 Iniciando instalação rápida do GERENCIADOR FTTH..."
echo ""

# 1. Verificar se mkauth existe
if [ ! -d "/opt/mk-auth/admin/addons" ]; then
    echo "❌ Erro: Diretório /opt/mk-auth/admin/addons não encontrado"
    exit 1
fi

# 2. Remover addon existente se houver
if [ -d "$ADDON_PATH" ]; then
    echo "⚠️  Addon já existe. Fazendo backup..."
    BACKUP_DIR="$ADDON_PATH-backup-$(date +%Y%m%d-%H%M%S)"
    cp -r "$ADDON_PATH" "$BACKUP_DIR"
    echo "✅ Backup criado: $BACKUP_DIR"
    rm -rf "$ADDON_PATH"
fi

# 3. Criar pasta do addon
echo "📁 Criando pasta do addon..."
mkdir -p "$ADDON_PATH"
if [ $? -ne 0 ]; then
    echo "❌ Erro ao criar pasta"
    exit 1
fi
echo "✅ Pasta criada"

# 4. Copiar arquivos (do diretório atual ou do local original)
echo "📋 Copiando arquivos..."
if [ -d "./caixas" ]; then
    cp -r ./caixas/* "$ADDON_PATH/" 2>/dev/null
elif [ -d "/tmp/caixas" ]; then
    cp -r /tmp/caixas/* "$ADDON_PATH/" 2>/dev/null
else
    echo "⚠️  Usando arquivos locais..."
    cp -r ./* "$ADDON_PATH/" 2>/dev/null
fi

if [ $? -ne 0 ]; then
    echo "⚠️  Aviso ao copiar arquivos (continuando...)"
fi

# 5. Ajustar permissões
echo "🔐 Ajustando permissões..."
sudo chown -R "$ADDON_OWNER:$ADDON_OWNER" "$ADDON_PATH" 2>/dev/null || chown -R "$ADDON_OWNER:$ADDON_OWNER" "$ADDON_PATH"
sudo chmod -R 755 "$ADDON_PATH" 2>/dev/null || chmod -R 755 "$ADDON_PATH"
find "$ADDON_PATH" -type f -name "*.php" -o -name "*.hhvm" 2>/dev/null | xargs chmod 644
echo "✅ Permissões ajustadas"

# 6. Verificar estrutura
echo "🔍 Verificando estrutura..."
if [ ! -f "$ADDON_PATH/manifest.json" ] || [ ! -f "$ADDON_PATH/index.php" ]; then
    echo "❌ Erro: Arquivos obrigatórios não encontrados"
    exit 1
fi
echo "✅ Estrutura válida"

# 7. Resumo final
echo ""
echo "═══════════════════════════════════════════════════════════"
echo "✅ INSTALAÇÃO CONCLUÍDA COM SUCESSO!"
echo "═══════════════════════════════════════════════════════════"
echo ""
echo "📍 Informações da Instalação:"
echo "   Addon: GERENCIADOR FTTH v2.0"
echo "   Caminho: $ADDON_PATH"
echo "   Proprietário: $ADDON_OWNER"
echo ""
echo "🌐 Acesso Web:"
echo "   http://seu-servidor/admin/addons/"
echo ""
echo "📝 Próximos Passos:"
echo "   1. Verifique a página do addon no painel"
echo "   2. Configure credenciais de BD se necessário"
echo "   3. Teste os módulos OLT"
echo ""

