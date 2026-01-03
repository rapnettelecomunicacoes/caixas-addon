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
    echo -e "${BLUE}║     INSTALADOR - GERENCIADOR FTTH v${ADDON_VERSION}                  ${BLUE}║${NC}"
    echo -e "${BLUE}║     Autor: Patrick Nascimento                              ${BLUE}║${NC}"
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
    
    # Obter diretório do script (onde foi executado)
    SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
    
    # Verificar se estamos no diretório correto (contém src, index.php, etc)
    if [ ! -f "$SCRIPT_DIR/index.php" ] && [ ! -d "$SCRIPT_DIR/src" ]; then
        print_error "Arquivo 'index.php' ou diretório 'src' não encontrado"
        print_info "Certifique-se de executar este script no raiz do repositório"
        exit 1
    fi
    
    # Criar diretório pai se não existir
    mkdir -p "$(dirname "$ADDON_PATH")"
    
    # Criar diretório do addon se não existir
    mkdir -p "$ADDON_PATH"
    
    # Copiar TODOS os arquivos do script_dir para ADDON_PATH
    cp -r "$SCRIPT_DIR"/* "$ADDON_PATH/" 2>/dev/null || true
    cp -r "$SCRIPT_DIR"/.[^.]* "$ADDON_PATH/" 2>/dev/null || true
    
    if [ -f "$ADDON_PATH/index.php" ]; then
        print_success "Arquivos copiados com sucesso"
    else
        print_error "Erro ao copiar arquivos"
        exit 1
    fi
}

backup_addon() {
    print_info "Verificando instalação anterior..."
    
    if [ -d "$ADDON_PATH" ] && [ -f "$ADDON_PATH/index.php" ]; then
        BACKUP_PATH="${ADDON_PATH}.backup.$(date +%Y%m%d_%H%M%S)"
        print_warning "Addon já existe. Criando backup em $BACKUP_PATH"
        mv "$ADDON_PATH" "$BACKUP_PATH"
        print_success "Backup criado"
    fi
}

set_permissions() {
    print_info "Configurando permissões..."
    
    # Definir proprietário
    chown -R "$ADDON_OWNER:$ADDON_GROUP" "$ADDON_PATH"
    
    # Definir permissões (755 para diretórios, 644 para arquivos)
    find "$ADDON_PATH" -type d -exec chmod 755 {} \;
    find "$ADDON_PATH" -type f -exec chmod 644 {} \;
    
    # Deixar scripts executáveis
    find "$ADDON_PATH" -name "*.sh" -exec chmod 755 {} \;
    find "$ADDON_PATH" -name "install*.sh" -exec chmod 755 {} \;
    
    print_success "Permissões configuradas"
}

create_license_dir() {
    print_info "Criando diretório de licenças..."
    
    LICENSE_DIR="/var/tmp"
    mkdir -p "$LICENSE_DIR"
    
    # Se não existir licença de teste, criar uma
    if [ ! -f "$LICENSE_DIR/license_"*.json ]; then
        print_info "Gerando licença de teste..."
        php -r "
        \$licenseData = [
            'chave' => md5('teste-' . time()),
            'cliente' => 'Teste',
            'email' => 'admin@test',
            'provedor' => 'LOCAL',
            'criacao' => date('Y-m-d'),
            'expiracao' => date('Y-m-d', strtotime('+1 year')),
            'status' => 'ativa'
        ];
        \$file = '$LICENSE_DIR/license_' . \$licenseData['chave'] . '.json';
        file_put_contents(\$file, json_encode(\$licenseData, JSON_PRETTY_PRINT));
        chmod(\$file, 0644);
        " 2>/dev/null || print_warning "Não foi possível gerar licença de teste"
    fi
    
    print_success "Diretório de licenças pronto"
}

verify_installation() {
    print_info "Verificando instalação..."
    
    if [ ! -f "$ADDON_PATH/index.php" ]; then
        print_error "Arquivo index.php não encontrado"
        return 1
    fi
    
    if [ ! -d "$ADDON_PATH/src" ]; then
        print_error "Diretório src não encontrado"
        return 1
    fi
    
    # Verificar se AuthHandler foi instalado
    if [ ! -f "$ADDON_PATH/src/auth_handler.php" ]; then
        print_warning "AuthHandler não encontrado (versão antiga?)"
    else
        print_success "AuthHandler instalado ✓"
    fi
    
    print_success "Instalação verificada com sucesso"
    return 0
}

# ============================================================================
# MAIN
# ============================================================================

main() {
    print_header
    
    check_requirements
    backup_addon
    copy_addon_files
    set_permissions
    create_license_dir
    verify_installation
    
    if [ $? -eq 0 ]; then
        echo ""
        echo -e "${GREEN}╔═══════════════════════════════════════════════════════════╗${NC}"
        echo -e "${GREEN}║     INSTALAÇÃO CONCLUÍDA COM SUCESSO!                    ${GREEN}║${NC}"
        echo -e "${GREEN}╚═══════════════════════════════════════════════════════════╝${NC}"
        echo ""
        print_success "Addon instalado em: $ADDON_PATH"
        print_info "Acesse: https://seu-servidor/admin/addons/caixas/"
        echo ""
    else
        echo ""
        print_error "Houve problemas na instalação"
        exit 1
    fi
}

# Executar main
main "$@"
