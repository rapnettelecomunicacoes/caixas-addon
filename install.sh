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
    echo -e "${BLUE}╔════════════════════════════════════════════════════════════╗${NC}"
    echo -e "${BLUE}║     INSTALADOR - GERENCIADOR FTTH v${ADDON_VERSION}              ${BLUE}║${NC}"
    echo -e "${BLUE}║     Autor: Patrick Nascimento                  ${BLUE}║${NC}"
    echo -e "${BLUE}╚════════════════════════════════════════════════════════════╝${NC}"
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

check_root() {
    if [ "$EUID" -ne 0 ]; then
        print_error "Este script deve ser executado como root (use: sudo ./install.sh)"
        exit 1
    fi
}

check_mkauth() {
    if [ ! -d "/opt/mk-auth" ]; then
        print_error "mkauth não encontrado em /opt/mk-auth"
        exit 1
    fi
    print_success "mkauth encontrado"
}

check_addon_dir() {
    if [ ! -d "/opt/mk-auth/admin/addons" ]; then
        print_error "Diretório de addons não encontrado"
        exit 1
    fi
    print_success "Diretório de addons encontrado"
}

backup_existing_addon() {
    if [ -d "$ADDON_PATH" ]; then
        print_warning "Addon já existe em $ADDON_PATH"
        read -p "Deseja fazer backup da versão existente? (s/n) " -n 1 -r
        echo
        if [[ $REPLY =~ ^[Ss]$ ]]; then
            BACKUP_DIR="$ADDON_PATH-backup-$(date +%Y%m%d-%H%M%S)"
            cp -r "$ADDON_PATH" "$BACKUP_DIR"
            print_success "Backup criado em: $BACKUP_DIR"
        fi
        
        read -p "Deseja sobrescrever o addon existente? (s/n) " -n 1 -r
        echo
        if [[ ! $REPLY =~ ^[Ss]$ ]]; then
            print_warning "Instalação cancelada"
            exit 1
        fi
        rm -rf "$ADDON_PATH"
    fi
}

copy_addon_files() {
    print_info "Copiando arquivos do addon..."
    
    if [ ! -d "caixas" ]; then
        print_error "Diretório 'caixas' não encontrado no diretório atual"
        exit 1
    fi
    
    cp -r caixas/ "$ADDON_PATH"
    
    if [ $? -eq 0 ]; then
        print_success "Arquivos copiados com sucesso"
    else
        print_error "Erro ao copiar arquivos"
        exit 1
    fi
}

set_permissions() {
    print_info "Ajustando permissões..."
    
    chown -R $ADDON_OWNER:$ADDON_GROUP "$ADDON_PATH"
    chmod -R 755 "$ADDON_PATH"
    find "$ADDON_PATH" -type f -name "*.php" -o -name "*.hhvm" | xargs chmod 644
    find "$ADDON_PATH" -type d | xargs chmod 755
    
    if [ $? -eq 0 ]; then
        print_success "Permissões ajustadas"
    else
        print_error "Erro ao ajustar permissões"
        exit 1
    fi
}

verify_structure() {
    print_info "Verificando estrutura do addon..."
    
    local files_required=(
        "$ADDON_PATH/manifest.json"
        "$ADDON_PATH/index.php"
        "$ADDON_PATH/addons.class.php"
        "$ADDON_PATH/src/app.php"
    )
    
    for file in "${files_required[@]}"; do
        if [ ! -f "$file" ]; then
            print_error "Arquivo obrigatório não encontrado: $file"
            exit 1
        fi
    done
    
    print_success "Estrutura válida"
}

verify_manifest() {
    print_info "Verificando manifest.json..."
    
    if grep -q '"name"' "$ADDON_PATH/manifest.json"; then
        local addon_name=$(grep '"name"' "$ADDON_PATH/manifest.json" | head -1 | sed 's/.*"name"[[:space:]]*:[[:space:]]*"\([^"]*\)".*/\1/')
        print_success "Addon: $addon_name"
    fi
}

test_web_access() {
    print_info "Testando acesso web..."
    
    if command -v curl &> /dev/null; then
        local response=$(curl -s -o /dev/null -w "%{http_code}" "http://localhost/admin/addons/")
        if [ "$response" = "200" ] || [ "$response" = "301" ] || [ "$response" = "302" ]; then
            print_success "Painel web acessível"
        else
            print_warning "Painel web retornou código: $response"
        fi
    else
        print_warning "curl não instalado, skipping web test"
    fi
}

show_summary() {
    echo ""
    echo -e "${BLUE}╔════════════════════════════════════════════════════════════╗${NC}"
    echo -e "${BLUE}║            RESUMO DA INSTALAÇÃO                ${BLUE}║${NC}"
    echo -e "${BLUE}╚════════════════════════════════════════════════════════════╝${NC}"
    echo ""
    echo -e "Addon:        ${GREEN}GERENCIADOR FTTH${NC}"
    echo -e "Versão:       ${GREEN}$ADDON_VERSION${NC}"
    echo -e "Caminho:      ${GREEN}$ADDON_PATH${NC}"
    echo -e "Proprietário: ${GREEN}$ADDON_OWNER:$ADDON_GROUP${NC}"
    echo ""
    echo -e "${YELLOW}Próximos Passos:${NC}"
    echo -e "1. Acesse o painel: http://seu-servidor/admin/addons/"
    echo -e "2. Localize '${GREEN}GERENCIADOR FTTH${NC}' na lista"
    echo -e "3. Configure as credenciais de banco de dados se necessário"
    echo -e "4. Teste a conectividade SSH para o módulo OLT"
    echo ""
    echo -e "${YELLOW}Arquivos Importantes:${NC}"
    echo -e "  • Configuração DB: $ADDON_PATH/src/cto/config/database.php"
    echo -e "  • Configuração API: $ADDON_PATH/src/cto/config/api.php"
    echo -e "  • Logs: $ADDON_PATH/error.log"
    echo ""
}

# ============================================================================
# EXECUÇÃO PRINCIPAL
# ============================================================================

main() {
    print_header
    
    print_info "Iniciando instalação..."
    echo ""
    
    # Verificações pré-instalação
    print_info "Realizando verificações pré-instalação..."
    check_root
    check_mkauth
    check_addon_dir
    echo ""
    
    # Backup se necessário
    backup_existing_addon
    echo ""
    
    # Copiar e instalar
    print_info "Instalando addon..."
    copy_addon_files
    set_permissions
    echo ""
    
    # Verificações pós-instalação
    print_info "Realizando verificações pós-instalação..."
    verify_structure
    verify_manifest
    test_web_access
    echo ""
    
    # Resumo final
    show_summary
    
    print_success "Instalação concluída com êxito!"
}

# ============================================================================
# EXECUÇÃO
# ============================================================================

main "$@"
