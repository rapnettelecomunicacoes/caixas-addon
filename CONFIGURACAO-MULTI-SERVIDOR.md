# Configuração Multi-Servidor - GERENCIADOR FTTH v2.0

## 📋 Visão Geral

O GERENCIADOR FTTH v2.0 agora suporta **instalação em múltiplos servidores**, com cada servidor usando seu **próprio banco de dados local**.

## 🚀 Como Funciona

1. **Instalação padrão**: O addon traz uma configuração padrão que funciona com `localhost`
2. **Configuração local**: Se você criar um arquivo `database.local.php`, o addon usa aquele ao invés da configuração padrão
3. **Multi-servidor**: Cada servidor pode ter sua própria configuração personalizada

## ⚙️ Instalação Rápida (Automática)

### Opção 1: Script Interativo (Recomendado)

```bash
sudo /opt/mk-auth/admin/addons/caixas/configure-server.sh
```

Este script irá:
- ✅ Solicitar credenciais do seu banco local
- ✅ Criar arquivo `database.local.php` automaticamente
- ✅ Testar conexão com o banco
- ✅ Validar se a tabela existe

### Opção 2: Manual

1. **Copiar arquivo de exemplo:**
```bash
sudo cp /opt/mk-auth/admin/addons/caixas/src/cto/config/database.local.example.php \
        /opt/mk-auth/admin/addons/caixas/src/cto/config/database.local.php
```

2. **Editar com suas credenciais:**
```bash
sudo nano /opt/mk-auth/admin/addons/caixas/src/cto/config/database.local.php
```

3. **Ajustar permissões:**
```bash
sudo chown www-data:www-data /opt/mk-auth/admin/addons/caixas/src/cto/config/database.local.php
sudo chmod 640 /opt/mk-auth/admin/addons/caixas/src/cto/config/database.local.php
```

## 📁 Estrutura de Arquivos

```
/opt/mk-auth/admin/addons/caixas/
├── src/cto/config/
│   ├── database.php                    ← Configuração padrão (nunca modificar)
│   ├── database.local.php              ← Configuração DESTE servidor (criar/editar)
│   ├── database.local.example.php      ← Modelo de referência
│   └── api.php                         ← Configuração de APIs
├── configure-server.sh                 ← Script automático de config
└── CONFIGURACAO-MULTI-SERVIDOR.md      ← Este arquivo
```

## 🔧 Arquivo database.local.php

### Exemplo para Servidor 172.16.123.6 (origem):
```php
<?php
$Host = 'localhost';           // Banco local deste servidor
$user = 'root';
$pass = 'vertrigo';
$db_name = 'mkradius';         // Seu banco local
$table_name = 'mp_caixa';
$socket = '/var/run/mysqld/mysqld.sock';
```

### Exemplo para Servidor 45.160.84.65 (novo):
```php
<?php
$Host = 'localhost';           // Banco local deste servidor
$user = 'root';
$pass = 'vertrigo';
$db_name = 'mkradius';         // Seu banco local
$table_name = 'mp_caixa';
$socket = '/var/run/mysqld/mysqld.sock';
```

> **Nota**: Ambos usam `localhost` porque cada servidor conecta ao SEU MySQL local!

## ✅ Checklist de Instalação

- [ ] Executar `sudo /opt/mk-auth/admin/addons/caixas/configure-server.sh`
- [ ] Confirmar que a conexão foi bem-sucedida
- [ ] Verificar se a tabela `mp_caixa` foi encontrada
- [ ] Acessar o addon no mkauth: Addons → GERENCIADOR FTTH
- [ ] Validar que os dados do SEU servidor aparecem (não do outro servidor)
- [ ] Testar funcionalidades: adicionar, editar, deletar CTOs
- [ ] Testar clique nos cards (verficar AJAX)

## 🐛 Troubleshooting

### Problema: "Erro ao conectar ao banco de dados"

**Solução:**
1. Verificar credenciais em `database.local.php`
2. Verificar se MySQL está rodando: `sudo service mysql status`
3. Testar conexão manual:
```bash
mysql -h localhost -u root -p mkradius -e "SELECT COUNT(*) FROM mp_caixa;"
```

### Problema: "Ainda mostra dados do outro servidor"

**Causas possíveis:**
1. `database.local.php` não foi criado corretamente
2. PHP não foi recarregado (limpar cache do navegador e do sistema)
3. Banco não foi sincronizado entre servidores

**Solução:**
```bash
# Verificar se arquivo existe
ls -l /opt/mk-auth/admin/addons/caixas/src/cto/config/database.local.php

# Validar sintaxe PHP
php -l /opt/mk-auth/admin/addons/caixas/src/cto/config/database.local.php

# Limpar cache do PHP (se usando OPcache)
sudo systemctl restart php-fpm
sudo systemctl restart apache2
```

### Problema: "AJAX requests pedindo login"

**Solução:**
1. Verificar permissões dos arquivos:
```bash
sudo chown -R www-data:www-data /opt/mk-auth/admin/addons/caixas
sudo chmod -R 755 /opt/mk-auth/admin/addons/caixas
```

2. Verificar logs de erro:
```bash
tail -50 /var/log/apache2/error.log
tail -50 /var/log/mysql/error.log
```

## 🔄 Sincronização Entre Servidores

Se você precisa que ambos os servidores tenham **os mesmos dados**, execute em cada servidor:

### Para Copiar Banco de Outro Servidor:
```bash
# De outro servidor
mysqldump -h 172.16.123.6 -u root -p mkradius mp_caixa > ctos_backup.sql

# Para o servidor novo
mysql -h localhost -u root -p mkradius < ctos_backup.sql
```

Ou use o comando direto:
```bash
mysqldump -h 172.16.123.6 -u root -p mkradius mp_caixa | \
  mysql -h localhost -u root -p mkradius
```

## 📝 Debug

Para ativar logs de configuração, edite `database.local.php` e descomente:

```php
define('DEBUG_DATABASE_CONFIG', true);
```

Depois verifique:
```bash
tail -20 /var/log/apache2/error.log | grep DATABASE
```

## 📞 Suporte

Se tiver problemas:

1. Verifique o arquivo de configuração foi criado
2. Teste a conexão com o banco manualmente
3. Verifique permissões dos arquivos
4. Consulte os logs: `/var/log/apache2/error.log`

---
**Versão**: 2.0 | **Data**: 2 de Janeiro de 2026
