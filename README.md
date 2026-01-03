# 🌐 GERENCIADOR FTTH

**Sistema de Gerenciamento de Caixas de Terminação Óptica (CTO) para redes FTTH**

| Campo | Informação |
|-------|-----------|
| **Nome** | GERENCIADOR FTTH |
| **Versão** | 2.0 |
| **Autor** | Patrick Nascimento |
| **Data** | 1º de Janeiro de 2026 |
| **Tamanho** | 5.3 MB |
| **Arquivos** | 553+ |

---

## 🚀 Instalação Rápida

### 1️⃣ Via Script Automático (Recomendado)
```bash
sudo bash install.sh
```

### 2️⃣ Via SCP Remoto
```bash
# No servidor ORIGEM
cd /opt/mk-auth/admin/addons
tar -czf caixas-v2.0.tar.gz caixas/
scp caixas-v2.0.tar.gz user@servidor_destino:/tmp/

# No servidor DESTINO
cd /opt/mk-auth/admin/addons
tar -xzf /tmp/caixas-v2.0.tar.gz
sudo chown -R www-data:www-data caixas
sudo chmod -R 755 caixas
```

### 3️⃣ Via Deploy Automático
```bash
bash deploy.sh usuario@servidor_destino
```

### 4️⃣ Via Git Clone
```bash
cd /opt/mk-auth/admin/addons
git clone https://seu-repo/caixas.git
sudo chown -R www-data:www-data caixas
```

---

## 📁 Estrutura

```
caixas/
├── manifest.json              # Metadados do addon
├── index.php / index.hhvm     # Painel principal
├── addons.class.php           # Classe Manifest
├── install.sh                 # Script de instalação
├── deploy.sh                  # Script de deploy remoto
├── INSTALL.md                 # Guia completo de instalação
├── src/
│   ├── app.php               # Roteador
│   └── cto/
│       ├── config/           # Banco de dados e API
│       ├── models/           # Classes de modelo
│       ├── database/         # Scripts de BD
│       ├── css/              # Estilos
│       ├── js/               # Scripts JS
│       └── componente/       # Componentes principais
│           ├── inicio/       # Dashboard
│           ├── adicionar/    # Adicionar CTO
│           ├── editar/       # Editar CTO
│           ├── maps/         # Mapa Google
│           ├── mapadectos/   # Mapa de CTOs
│           ├── olt/          # Gerenciamento OLT (SSH/Telnet)
│           ├── backup/       # Sistema de backup
│           ├── viabilidade/  # Análise de viabilidade
│           └── configurar/   # Configurações
└── .htaccess                  # Rewrite rules Apache
```

---

## ⚙️ Configuração

Após instalar, configure os arquivos:

### 1. Banco de Dados
```bash
nano /opt/mk-auth/admin/addons/caixas/src/cto/config/database.php
```

Editar:
- `$host` - IP/hostname do servidor BD
- `$user` - Usuário do banco
- `$password` - Senha
- `$database` - Nome do banco

### 2. API (Opcional)
```bash
nano /opt/mk-auth/admin/addons/caixas/src/cto/config/api.php
```

### 3. Credenciais OLT (Para Módulo SSH)
Configure em:
```bash
/opt/mk-auth/admin/addons/caixas/src/cto/componente/olt/conexao.php
```

---

## 🎯 Funcionalidades Principais

| Módulo | Descrição |
|--------|-----------|
| **Dashboard** | Visualização de estatísticas de CTOs |
| **Adicionar CTO** | Registrar nova caixa de terminação |
| **Editar CTO** | Modificar dados de CTO existente |
| **Mapa Google** | Visualização geográfica interativa |
| **Mapa de CTOs** | Mapa com informações detalhadas |
| **OLT Manager** | Gerenciar equipamentos OLT via SSH |
| **Viabilidade** | Analisar viabilidade de atendimento |
| **Backup** | Sistema nativo de backup |
| **Configurações** | Ajustes gerais do sistema |

---

## 🔧 Módulo OLT Avançado

Suporte para múltiplos fabricantes:

- **CIANET** (G8PS)
- **ZTE** (C320, C620)
- **Intelbras**
- **TP-Link**
- **Fiberhome**
- **Huawei**
- **Parks**

Funcionalidades:
- Conectar via SSH/Telnet
- Autorizar/Desautorizar ONUs
- Consultar ONUs
- Provisionar equipamentos
- Deletar ONUs offline

---

## ✅ Checklist Pós-Instalação

- [ ] Addon aparece em `/admin/addons/`
- [ ] Banco de dados configurado
- [ ] Permissões de arquivo corretas (755)
- [ ] Proprietário é www-data
- [ ] Sem erros em `/error.log`
- [ ] Painel carrega normalmente
- [ ] OLT conecta via SSH (se usar)

---

## 🐛 Solução de Problemas

### Addon não aparece
```bash
# Verificar manifest.json
cat /opt/mk-auth/admin/addons/caixas/manifest.json

# Verificar permissões
ls -la /opt/mk-auth/admin/addons/caixas/
```

### Erro de conexão BD
```bash
# Testar conexão
php -r "include 'src/cto/config/database.php'; echo 'OK';"

# Editar config
nano src/cto/config/database.php
```

### SSH para OLT não funciona
```bash
# Instalar dependências
cd src/cto/componente/olt
composer install

# Testar conexão
php teste_ssh.php
```

---

## 📞 Suporte e Documentação

- **Guia Completo:** [INSTALL.md](INSTALL.md)
- **Logs:** `/error.log`
- **Config DB:** `src/cto/config/database.php`
- **Config API:** `src/cto/config/api.php`

---

## 📝 Changelog v2.0

✅ Atualizado nome para GERENCIADOR FTTH  
✅ Novo autor: Patrick Nascimento  
✅ Melhorias na interface  
✅ Documentação de instalação  
✅ Scripts de deploy automático  
✅ Suporte a múltiplas OLTs  

---

## 📄 Licença

Desenvolvido para MK-AUTH Admin Panel

---

**Desenvolvido com ❤️ por Patrick Nascimento**
