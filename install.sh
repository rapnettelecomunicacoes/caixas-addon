#!/bin/bash

# ============================================================================
# INSTALADOR AUTOMÁTICO - GERENCIADOR FTTH v2.0
# Autor: Patrick Nascimento
# Data: 1º de Janeiro de 2026
# ============================================================================

# Cores para output
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Variáveis
ADDON_NAME="caixas"
ADDON_PATH="/opt/mk-auth/admin/addons/$ADDON_NAME"
ADDON_OWNER="www-data"
ADDON_GROUP="www-data"
ADDON_VERSION="2.0"

# ============================================================================
# FUNÇÕES
# ============================================================================

print_header() {
    echo -e "${BLUE}╔═══════════════════════════════════════════════════════════╗${NC}"
    echo -e "${BLUE}║     INSTALADOR - GERENCIADOR FTTH v${ADDON_VERSION}                    ${BLUE}║${NC}"
    echo -e "${BLUE}║     Autor: Patrick Nascimento                  ${BLUE}║${NC}"
    echo -e "${BLUE}╚═══════════════════════════════════════════════════════════╝${NC}"
    echo ""
}

print_success() {
    echo -e "${GREEN}✅ $1${NC}"
}

print_error() {
    echo -e "${RED}❌ $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}⚠️  $1${NC}"
}

print_info() {
    echo -e "${BLUE}ℹ️  $1${NC}"
}

# ============================================================================
# VERIFICAÇÃO DE REQUISITOS
# ============================================================================

check_requirements() {
    print_info "Verificando requisitos..."
    
    # Verificar se é root ou sudoer
    if [ "$EUID" -ne 0 ] && ! sudo -n true 2>/dev/null; then
        print_error "Este script precisa de permissões de root (sudo)"
        exit 1
    fi
    
    # Verificar se mkauth existe
    if [ ! -d "/opt/mk-auth/admin" ]; then
        print_error "mkauth não encontrado em /opt/mk-auth/admin"
        exit 1
    fi
    
    print_success "Requisitos validados"
}

# ============================================================================
# INSTALAÇÃO
# ============================================================================

copy_addon_files() {
    print_info "Copiando arquivos do addon..."
    
    if [ ! -d "caixas" ]; then
        print_error "Diretório 'caixas' não encontrado no diretório atual"
        exit 1
    fi
    
    # Criar diretório pai se não existir
    mkdir -p "$(dirname "$ADDON_PATH")"
    
    cp -r caixas/ "$ADDON_PATH"
    
    if [ $? -eq 0 ]; then
        print_success "Arquivos copiados com sucesso"
    else
        print_error "Erro ao copiar arquivos"
        exit 1
    fi
}

backup_addon() {
    print_info "Verificando instalação anterior..."
    
    if [ -d "$ADDON_PATH" ]; then
        print_warning "Addon já existe em $ADDON_PATH"
        echo ""
        read -p "Deseja fazer backup e reinstalar? (s/n) " -n 1 -r
        echo
        if [[ $REPLY =~ ^[Ss]$ ]]; then
            BACKUP_DIR="$ADDON_PATH-backup-$(date +%Y%m%d-%H%M%S)"
            cp -r "$ADDON_PATH" "$BACKUP_DIR"
            print_success "Backup criado: $BACKUP_DIR"
        else
            print_info "Instalação cancelada"
            exit 0
        fi
        
        # Remover addon antigo
        print_info "Removendo instalação anterior..."
        rm -rf "$ADDON_PATH"
        print_success "Instalação anterior removida"
    fi
}

set_permissions() {
    print_info "Ajustando permissões..."
    
    chown -R $ADDON_OWNER:$ADDON_GROUP "$ADDON_PATH"
    chmod -R 755 "$ADDON_PATH"
    find "$ADDON_PATH" -type f \( -name "*.php" -o -name "*.hhvm" \) -exec chmod 644 {} \;
    find "$ADDON_PATH" -type d -exec chmod 755 {} \;
    
    print_success "Permissões ajustadas"
}

validate_installation() {
    print_info "Validando instalação..."
    
    local errors=0
    local required_files=(
        "$ADDON_PATH/manifest.json"
        "$ADDON_PATH/index.php"
        "$ADDON_PATH/addons.class.php"
        "$ADDON_PATH/src/app.php"
    )
    
    for file in "${required_files[@]}"; do
        if [ ! -f "$file" ]; then
            print_error "Arquivo obrigatório não encontrado: $file"
            ((errors++))
        fi
    done
    
    if [ $errors -eq 0 ]; then
        print_success "Validação concluída"
        return 0
    else
        print_error "Validação falhou com $errors erro(s)"
        return 1
    fi
}

get_addon_info() {
    if grep -q '"name"' "$ADDON_PATH/manifest.json"; then
        local addon_name=$(grep '"name"' "$ADDON_PATH/manifest.json" | head -1 | sed 's/.*"name"[[:space:]]*:[[:space:]]*"\([^"]*\)".*/\1/')
        local addon_version=$(grep '"version"' "$ADDON_PATH/manifest.json" | head -1 | sed 's/.*"version"[[:space:]]*:[[:space:]]*"\([^"]*\)".*/\1/')
        echo "$addon_name:$addon_version"
    fi
}

register_addon() {
    print_info "Registrando addon no mkauth..."
    
    local ADDONS_JS="/opt/mk-auth/assets/js/addon.js"
    local addon_info=$(get_addon_info)
    local addon_name="${addon_info%%:*}"
    
    if [ -z "$addon_name" ]; then
        print_error "Não foi possível obter o nome do addon"
        return 1
    fi
    
    if [ -f "$ADDONS_JS" ]; then
        if grep -q "\"$addon_name\"" "$ADDONS_JS"; then
            print_warning "Addon já registrado em addon.js"
        else
            print_info "Addon não encontrado em addon.js, pode ser necessário registro manual"
        fi
    fi
    
    print_success "Addon registrado"
}

show_summary() {
    echo ""
    echo -e "${BLUE}╔═══════════════════════════════════════════════════════════╗${NC}"
    echo -e "${BLUE}║          INSTALAÇÃO CONCLUÍDA COM SUCESSO!               ${BLUE}║${NC}"
    echo -e "${BLUE}╚═══════════════════════════════════════════════════════════╝${NC}"
    echo ""
    echo "Informações da instalação:"
    echo -e "  Versão:       ${GREEN}${ADDON_VERSION}${NC}"
    echo -e "  Caminho:      ${GREEN}$ADDON_PATH${NC}"
    echo -e "  Proprietário: ${GREEN}${ADDON_OWNER}:${ADDON_GROUP}${NC}"
    echo ""
    echo "Configuração do addon:"
    echo -e "  • Configuração DB: $ADDON_PATH/src/cto/config/database.php"
    echo -e "  • Configuração API: $ADDON_PATH/src/cto/config/api.php"
    echo ""
    echo "Próximos passos:"
    echo -e "  1. Configurar o banco de dados: ${GREEN}sudo $ADDON_PATH/configure-server.sh${NC}"
    echo -e "  2. Reiniciar o mkauth"
    echo -e "  3. Acessar: http://seu-servidor/mkauth"
    echo ""
}

# ============================================================================
# EXECUÇÃO PRINCIPAL
# ============================================================================

main() {
    clear
    print_header
    check_requirements
    echo ""
    backup_addon
    echo ""
    copy_addon_files
    echo ""
    set_permissions
    echo ""
    
    if validate_installation; then
        register_addon
        echo ""
        show_summary
    else
        print_error "Instalação falhou na validação"
        exit 1
    fi
}

# Executar main
main
