#!/bin/bash

# ============================================================================
# CONFIGURADOR DE BANCO DE DADOS - GERENCIADOR FTTH v2.0
# Database Configuration Script for Multi-Server Setup
# ============================================================================

CONFIG_FILE="/opt/mk-auth/admin/addons/caixas/src/cto/config/database.local.php"
EXAMPLE_FILE="/opt/mk-auth/admin/addons/caixas/src/cto/config/database.local.example.php"

echo "========================================================================"
echo "  CONFIGURADOR DE BANCO DE DADOS - GERENCIADOR FTTH v2.0"
echo "  DATABASE CONFIGURATION - Multi-Server Setup"
echo "========================================================================"
echo ""

if [ -f "$CONFIG_FILE" ]; then
    echo "⚠️  Arquivo database.local.php já existe!"
    echo "   Deseja remover e criar um novo? (s/n)"
    read -r response
    if [ "$response" != "s" ]; then
        echo "Operação cancelada."
        exit 0
    fi
    rm "$CONFIG_FILE"
fi

# Solicitar informações
echo ""
echo "Forneça as credenciais do banco de dados DESTE servidor:"
echo ""

read -p "Host (padrão: localhost): " host
host=${host:-localhost}

read -p "Usuário (padrão: root): " dbuser
dbuser=${dbuser:-root}

read -sp "Senha do banco: " dbpass
echo ""

read -p "Nome da base de dados (padrão: mkradius): " dbname
dbname=${dbname:-mkradius}

read -p "Nome da tabela CTOs (padrão: mp_caixa): " table
table=${table:-mp_caixa}

# Criar arquivo de configuração
cat > "$CONFIG_FILE" << EOFCONFIG
<?php
/**
 * Configuração Local do Banco de Dados
 * Database Local Configuration
 * Servidor configurado em: $(date '+%d/%m/%Y às %H:%M:%S')
 * Server configured at: $(date '+%d/%m/%Y at %H:%M:%S')
 */

\$Host = '$host';
\$user = '$dbuser';
\$pass = '$dbpass';
\$db_name = '$dbname';
\$table_name = '$table';
\$socket = '/var/run/mysqld/mysqld.sock';

EOFCONFIG

# Ajustar permissões
chmod 640 "$CONFIG_FILE"
chown www-data:www-data "$CONFIG_FILE"

echo ""
echo "========================================================================"
echo "✅ Configuração salva com sucesso!"
echo ""
echo "Arquivo: $CONFIG_FILE"
echo ""
echo "Credenciais configuradas:"
echo "  Host: $host"
echo "  Usuário: $dbuser"
echo "  Base de Dados: $dbname"
echo "  Tabela: $table"
echo ""
echo "Testando conexão..."
echo ""

# Testar conexão (usando PHP)
php -r "
\$host = '$host';
\$user = '$dbuser';
\$pass = '$dbpass';
\$db = '$dbname';

@\$conn = mysqli_connect(\$host, \$user, \$pass, \$db);
if (\$conn) {
    echo '✅ Conexão com banco de dados: OK\n';
    
    // Verificar se tabela existe
    \$result = mysqli_query(\$conn, \"SELECT COUNT(*) as total FROM $table LIMIT 1\");
    if (\$result) {
        \$row = mysqli_fetch_assoc(\$result);
        echo '✅ Tabela $table: OK (Total de registros: ' . \$row['total'] . ')\n';
    } else {
        echo '⚠️  Tabela $table: Não encontrada\n';
    }
    mysqli_close(\$conn);
} else {
    echo '❌ Erro ao conectar: ' . mysqli_connect_error() . '\n';
    exit(1);
}
"

echo ""
echo "========================================================================"
echo "Configuração concluída!"
echo "O addon agora usará as credenciais deste servidor."
echo "========================================================================"
