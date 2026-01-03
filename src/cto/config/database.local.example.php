<?php
/**
 * Configuração Local do Banco de Dados (por servidor)
 * Database Local Configuration (per server)
 * 
 * INSTRUÇÕES:
 * 1. Copie este arquivo para 'database.local.php' no mesmo diretório
 * 2. Modifique os valores abaixo com as credenciais do SEU servidor
 * 3. O addon carregará este arquivo automaticamente ao invés da configuração padrão
 * 
 * INSTRUCTIONS:
 * 1. Copy this file to 'database.local.php' in the same directory
 * 2. Modify the values below with your SERVER's credentials
 * 3. The addon will automatically load this file instead of the default config
 */

// ============================================================================
// DEFINA AQUI AS CREDENCIAIS DO SEU BANCO DE DADOS LOCAL
// CONFIGURE HERE YOUR LOCAL DATABASE CREDENTIALS
// ============================================================================

// Host do MySQL/MariaDB do ESTE servidor
// MySQL/MariaDB host for THIS server
$Host = 'localhost';

// Usuário do banco de dados
// Database user
$user = 'root';

// Senha do banco de dados
// Database password
$pass = 'vertrigo';

// Nome do banco de dados
// Database name
$db_name = 'mkradius';

// Nome da tabela de CTOs
// CTOs table name
$table_name = 'mp_caixa';

// Socket Unix (deixe como está, o sistema detecta automaticamente)
// Unix socket (leave as is, system will auto-detect)
$socket = '/var/run/mysqld/mysqld.sock';

// Opcional: Ativar debug (log de conexões)
// Optional: Enable debug (connection logging)
// define('DEBUG_DATABASE_CONFIG', true);
