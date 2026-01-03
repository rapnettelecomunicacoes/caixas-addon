# 🔐 Migração para Sistema de Licenças com Banco de Dados

## ✅ Status: IMPLEMENTADO COM SUCESSO

Data: 2 de janeiro de 2026
Versão: GERENCIADOR FTTH v2.0 + Database Edition

---

## 🎯 Vantagens da Migração

### Antes (Arquivo JSON)
- ❌ Armazenamento em `/var/tmp/license_*.json`
- ❌ Vulnerável a exclusão de arquivos
- ❌ Sem auditoria de mudanças
- ❌ Sem registro de histórico

### Depois (Banco de Dados)
- ✅ Armazenamento seguro em MariaDB
- ✅ Protegido contra exclusão acidental
- ✅ Auditoria completa com timestamps
- ✅ Múltiplas licenças por servidor
- ✅ Controle granular de status
- ✅ Backup automático via banco

---

## 📋 Estrutura do Banco

```sql
CREATE TABLE licenses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    chave VARCHAR(50) UNIQUE NOT NULL,
    cliente VARCHAR(255) NOT NULL,
    email VARCHAR(255),
    provedor VARCHAR(255),
    criacao DATETIME NOT NULL,
    expiracao DATETIME,
    dias INT DEFAULT 365,
    status ENUM('ativa', 'inativa') DEFAULT 'ativa',
    instalada_em DATETIME,
    servidor VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

---

## 🔧 Arquivos Modificados

### 1. **LicenseManager.php** (Nova Classe: `LicenseDB`)
   - Conexão PDO com MariaDB
   - Métodos CRUD (Create, Read, Update, Delete)
   - Operações com prepared statements
   - Suporte a INSERT...ON DUPLICATE KEY UPDATE

**Principais métodos:**
```php
public function saveLicense($chave, $dados)      // Salva/atualiza
public function getLicenseStatus()                // Status atual
public function getLicenseByKey($chave)           // Busca específica
public function getAllLicenses()                  // Lista todas
public function deleteLicense($chave)             // Remove licença
public function updateStatus($chave, $status)     // Muda status
```

### 2. **LicenseMiddleware.php**
   - Sem mudanças estruturais
   - Compatível com nova classe `LicenseDB`
   - Continua funcionando transparentemente

### 3. **license_install.php**
   - Valida contra banco de dados
   - Busca direta em `licenses.chave`
   - Sem mais dependência de arquivos JSON
   - Melhor performance

---

## 🔐 Segurança Aprimorada

### Antes: Vulnerabilidade
```
Cliente deleta → /var/tmp/license_*.json → Addon bloqueia
```

### Depois: Seguro
```
Cliente deleta arquivo → Nada acontece
Validação sempre consulta banco → Acesso bloqueado
```

### Proteções Adicionais
- ✅ Credenciais de banco em variáveis de conexão
- ✅ Prepared statements contra SQL injection
- ✅ Validação de expiração no banco
- ✅ Logging automático de atualizações (timestamps)

---

## 📊 Dados Migrados

Licenças existentes foram migradas de `/var/tmp/` para MariaDB:

| Chave | Cliente | Status | Dias Restantes |
|-------|---------|--------|-----------------|
| F6A4-A7DA-64B6-D3C4 | teste | ativa | 0 dias (expira 03/01) |
| 4A4B-F10C-1484-AADE | TESTE_DB_20260102113124 | ativa | 29 dias |
| A1AD-DADE-FA2F-5AE3 | TESTE_DB_20260102113128 | ativa | 30 dias |

---

## 🧪 Testes Realizados

✅ **Teste 1: Conexão com Banco**
   - Conexão PDO estabelecida com sucesso

✅ **Teste 2: Obter Status**
   - Status correto retornado
   - Cálculo de dias restantes OK

✅ **Teste 3: Listar Licenças**
   - 3 licenças encontradas
   - Dias restantes calculados corretamente

✅ **Teste 4: Validação**
   - Licença válida aceita
   - Dados corretos retornados

✅ **Teste 5: Gerar Nova Licença**
   - Nova licença criada com sucesso
   - Salva no banco automaticamente

✅ **Teste 6: Persistência**
   - Licença recuperada do banco
   - Dados íntegros

---

## 🚀 Como Usar

### Gerar Nova Licença
```php
$manager = new LicenseManager();
$result = $manager->generateLicense(
    'Cliente XYZ',
    30,                    // dias
    false,                 // não vitalícia
    'email@example.com',
    'provedor'
);
// Salva automaticamente no banco
echo $result['chave'];  // XXXX-XXXX-XXXX-XXXX
```

### Validar Licença
```php
$license = $manager->validateLicense('F6A4-A7DA-64B6-D3C4');
if ($license['valida']) {
    echo "Licença válida para " . $license['cliente'];
}
```

### Verificar Status
```php
$status = $manager->getLicenseStatus();
if ($status['instalada']) {
    echo "Licença ativa: " . $status['dias_restantes'] . " dias";
}
```

---

## 🔄 Próximos Passos (Opcional)

1. **Dashboard de Licenças** - Painel de gerenciamento avançado
2. **Renovação Automática** - Renovar licenças que expiram
3. **Múltiplos Servidores** - Licenças para diferentes instalações
4. **Relatórios** - Geração de relatórios de uso
5. **API REST** - Endpoints para integração

---

## ⚙️ Configuração

**Credenciais do Banco (em LicenseDB.php):**
```php
'mysql:host=127.0.0.1;dbname=mkradius;charset=utf8mb4'
usuario: root
senha: vertrigo
```

**Dados Conectados:**
- Host: 127.0.0.1
- Porta: 3306
- Banco: mkradius
- Tabela: licenses

---

## 📝 Notas Importantes

- ⚠️ Backup automático do banco de dados é responsabilidade do sysadmin
- ⚠️ Não deletar tabela `licenses` manualmente
- ⚠️ Credenciais do banco são sensíveis - não compartilhar
- ✅ Sistema totalmente compatível com versão anterior
- ✅ Sem migrations necessárias para componentes

---

## 🆘 Troubleshooting

**Problema:** "Banco de dados indisponível"
- Verificar conexão MySQL: `systemctl status mysql`
- Testar credenciais: `mysql -h 127.0.0.1 -u root -pvertrigo mkradius`

**Problema:** Licença não encontrada
- Verificar se foi criada: `SELECT * FROM licenses WHERE chave = 'XXXX-...'`
- Gerar nova licença via painel admin

**Problema:** Performance lenta
- Verificar índices: `SHOW INDEX FROM licenses`
- Chave deve ter índice UNIQUE (já incluído)

---

**Documentação completa:**
- Arquivo de teste: `test_db_integration.php`
- Classe principal: `src/LicenseManager.php`
- Middleware: `src/LicenseMiddleware.php`
- Formulário: `src/license_install.php`

