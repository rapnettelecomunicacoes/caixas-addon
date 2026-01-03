#!/bin/bash

# ============================================================================
# INSTALADOR AUTOMÁTICO - GERENCIADOR FTTH v2.0
# Autor: Patrick Nascimento
# Data: 2 de Janeiro de 2026 - VERSÃO 3.1 (Com detecção PHP-FPM)
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
TEMP_DIR="/tmp/caixas-install-$$"
ZIP_URL="https://github.com/rapnettelecomunicacoes/caixas-addon/archive/refs/heads/main.zip"

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

cleanup() {
    if [ -d "$TEMP_DIR" ]; then
        rm -rf "$TEMP_DIR"
    fi
}

trap cleanup EXIT

# ============================================================================
# DETECÇÃO E CONFIGURAÇÃO DO SOCKET PHP-FPM
# ============================================================================

detect_php_socket() {
    print_info "Detectando socket PHP-FPM disponível..."
    
    # Lista de sockets a procurar em ordem de preferência
    SOCKET_PATHS=(
        "/run/php-api.sock"
        "/run/php-admin.sock"
        "/run/php-central.sock"
        "/run/php-publico.sock"
        "/run/php-boleto.sock"
        "/run/php-retorno.sock"
        "/run/php-fpm.sock"
        "/var/run/php-fpm.sock"
        "/var/run/php7.3-fpm.sock"
        "/var/run/php8.0-fpm.sock"
    )
    
    for socket in "${SOCKET_PATHS[@]}"; do
        if [ -e "$socket" ] && [ -S "$socket" ]; then
            print_success "Socket encontrado: $socket"
            echo "$socket"
            return 0
        fi
    done
    
    # Se nenhum socket foi encontrado, usar o padrão (será criado pelo PHP-FPM depois)
    print_warning "Nenhum socket PHP-FPM encontrado. Usando socket padrão: /run/php-api.sock"
    echo "/run/php-api.sock"
    return 0
}

configure_apache_socket() {
    local PHP_SOCKET=$1
    local API_CONF="/etc/apache2/conf-available/api.conf"
    
    print_info "Atualizando configuração Apache com socket: $PHP_SOCKET"
    
    # Se o arquivo api.conf não existir, criar
    if [ ! -f "$API_CONF" ]; then
        print_warning "api.conf não encontrado, criando arquivo..."
        cat > "$API_CONF" << EOFCONF
# SISTEMA MK-AUTH64 DEFAULT APACHE

Alias /api /opt/mk-auth/api
<Directory /opt/mk-auth/api>
        <FilesMatch "\.(php|hhvm)$">
                SetHandler "proxy:unix:${PHP_SOCKET}|fcgi://127.0.0.1/"
        </FilesMatch>
        Options Indexes FollowSymLinks Includes ExecCGI
        AllowOverride All
        Order deny,allow
        Require all granted
</Directory>

# vim: syntax=apache ts=4 sw=4 sts=4 sr noet
EOFCONF
        print_success "api.conf criado"
    else
        # Atualizar socket no arquivo existente
        if grep -q "SetHandler.*proxy:unix:" "$API_CONF"; then
            # Substituir socket existente
            sed -i "s|SetHandler.*proxy:unix:[^|]*|SetHandler \"proxy:unix:${PHP_SOCKET}\"|g" "$API_CONF"
            print_success "Socket atualizado no api.conf"
        else
            print_warning "Padrão de socket não encontrado no api.conf"
        fi
    fi
    
    # Recarregar Apache se estiver rodando
    if command -v apache2ctl &> /dev/null; then
        if apache2ctl configtest 2>/dev/null | grep -q "Syntax OK"; then
            apache2ctl graceful 2>/dev/null || true
            print_success "Apache recarregado com sucesso"
        else
            print_warning "Erro na configuração do Apache (não recarregado)"
        fi
    fi
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
    
    # Verificar se curl está disponível
    if ! command -v curl &> /dev/null; then
        print_error "curl não está instalado"
        exit 1
    fi
    
    # Verificar se unzip está disponível
    if ! command -v unzip &> /dev/null; then
        print_error "unzip não está instalado"
        exit 1
    fi
    
    print_success "Requisitos validados"
}

# ============================================================================
# DOWNLOAD DOS ARQUIVOS
# ============================================================================

download_addon() {
    print_info "Baixando repositório do GitHub..."
    
    mkdir -p "$TEMP_DIR"
    
    # Baixar ZIP com cache busting
    TIMESTAMP=$(date +%s)
    DOWNLOAD_URL="${ZIP_URL}?t=${TIMESTAMP}"
    
    if ! curl -sSL -o "$TEMP_DIR/addon.zip" "$DOWNLOAD_URL" 2>/dev/null; then
        print_error "Falha ao baixar repositório"
        exit 1
    fi
    
    print_success "Repositório baixado"
    
    # Descompactar ZIP
    print_info "Descompactando arquivos..."
    if ! unzip -q "$TEMP_DIR/addon.zip" -d "$TEMP_DIR" 2>/dev/null; then
        print_error "Erro ao descompactar"
        exit 1
    fi
    
    print_success "Arquivos descompactados"
    
    # Se foi baixado em ZIP (caixas-addon-main), mover para TEMP_DIR
    if [ -d "$TEMP_DIR/caixas-addon-main" ]; then
        mv "$TEMP_DIR/caixas-addon-main"/* "$TEMP_DIR/" 2>/dev/null
        rmdir "$TEMP_DIR/caixas-addon-main" 2>/dev/null
    fi
    
    # Verificar se os arquivos principais existem
    if [ ! -f "$TEMP_DIR/index.php" ]; then
        print_error "Arquivo index.php não encontrado no repositório"
        exit 1
    fi
    
    if [ ! -d "$TEMP_DIR/src" ]; then
        print_error "Diretório src não encontrado no repositório"
        exit 1
    fi
}

# ============================================================================
# INSTALAÇÃO
# ============================================================================

backup_addon() {
    print_info "Verificando instalação anterior..."
    
    if [ -d "$ADDON_PATH" ] && [ -f "$ADDON_PATH/index.php" ]; then
        BACKUP_PATH="${ADDON_PATH}.backup.$(date +%Y%m%d_%H%M%S)"
        print_warning "Addon já existe. Criando backup em $BACKUP_PATH"
        mv "$ADDON_PATH" "$BACKUP_PATH"
        print_success "Backup criado"
    fi
}

copy_addon_files() {
    print_info "Copiando arquivos do addon..."
    
    # Criar diretório do addon
    mkdir -p "$ADDON_PATH"
    
    # Copiar TODOS os arquivos do repositório
    cp -r "$TEMP_DIR"/* "$ADDON_PATH/" 2>/dev/null
    cp -r "$TEMP_DIR"/.[^.]* "$ADDON_PATH/" 2>/dev/null || true
    
    # Remover arquivo .git se existir
    rm -rf "$ADDON_PATH/.git" 2>/dev/null || true
    
    if [ -f "$ADDON_PATH/index.php" ]; then
        print_success "Arquivos copiados com sucesso"
    else
        print_error "Erro ao copiar arquivos"
        exit 1
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
    
    print_success "Permissões configuradas"
}

create_license_dir() {
    print_info "Criando diretório de licenças..."
    
    LICENSE_DIR="/var/tmp"
    mkdir -p "$LICENSE_DIR"
    
    # Se não existir licença, criar uma de teste
    if ! ls "$LICENSE_DIR"/license_*.json 1> /dev/null 2>&1; then
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

run_migrations() {
    print_info "Executando migrações do banco de dados..."
    
    if [ -f "$ADDON_PATH/src/migrations/migrate.php" ]; then
        php "$ADDON_PATH/src/migrations/migrate.php" 2>/dev/null
        if [ $? -eq 0 ]; then
            print_success "Migrações executadas com sucesso"
            return 0
        else
            print_warning "Erro ao executar migrações (continuando...)"
            return 0
        fi
    fi
}

check_license() {
    print_info "Verificando licença..."
    
    LICENSE_FILE="/var/tmp/license_caixas.json"
    
    # Se não existe arquivo de licença, criar um vazio (desativado)
    if [ ! -f "$LICENSE_FILE" ]; then
        print_warning "Arquivo de licença não encontrado. Criando arquivo vazio..."
        
        cat > "$LICENSE_FILE" << 'EOJSON'
{
    "instalada": false,
    "chave": "",
    "cliente": "",
    "expiracao": "",
    "criacao": "",
    "instalada_em": "",
    "servidor": ""
}
EOJSON
        
        # Definir proprietário e permissões para que www-data possa escrever
        chmod 666 "$LICENSE_FILE"
        chown www-data:www-data "$LICENSE_FILE" 2>/dev/null || true
        
        print_success "Arquivo de licença criado (aguardando ativação): $LICENSE_FILE"
    else
        # Garantir que permissões estão corretas
        chmod 666 "$LICENSE_FILE"
        chown www-data:www-data "$LICENSE_FILE" 2>/dev/null || true
        print_success "Arquivo de licença encontrado: $LICENSE_FILE"
    fi
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
    
    # Detectar socket PHP-FPM e configurar Apache
    PHP_SOCKET=$(detect_php_socket)
    configure_apache_socket "$PHP_SOCKET"
    
    download_addon
    backup_addon
    copy_addon_files
    set_permissions
    create_license_dir
    check_license
    run_migrations
    verify_installation
    
    if [ $? -eq 0 ]; then
        echo ""
        echo -e "${GREEN}╔═══════════════════════════════════════════════════════════╗${NC}"
        echo -e "${GREEN}║     INSTALAÇÃO CONCLUÍDA COM SUCESSO!                    ${GREEN}║${NC}"
        echo -e "${GREEN}╚═══════════════════════════════════════════════════════════╝${NC}"
        echo ""
        print_success "Addon instalado em: $ADDON_PATH"
        print_success "Socket PHP-FPM configurado: $PHP_SOCKET"
        print_info "Acesse: https://seu-servidor/admin/addons/caixas/"
        echo ""
        return 0
    else
        echo ""
        print_error "Houve problemas na instalação"
        exit 1
    fi
}

# Executar main
main "$@"
