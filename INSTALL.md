# 🚀 GUIA DE INSTALAÇÃO - GERENCIADOR FTTH

## Informações do Addon
- **Nome:** GERENCIADOR FTTH
- **Versão:** 2.0
- **Autor:** Patrick Nascimento
- **Local de Instalação:** `/opt/mk-auth/admin/addons/caixas`

---

## 📋 Pré-requisitos

Antes de instalar, verifique se seu servidor mkauth possui:

```bash
# 1. Verificar versão do PHP/HHVM
php -v
hhvm --version  # se usar HHVM

# 2. Verificar permissões de escrita
ls -ld /opt/mk-auth/admin/addons/

# 3. Verificar se www-data tem acesso
sudo -u www-data touch /opt/mk-auth/admin/addons/test && rm /opt/mk-auth/admin/addons/test

# 4. Verificar dependências do SSH (para módulo OLT)
which ssh
which scp
which php-ssh2  # ou phpseclib
```

---

## 🌐 OPÇÃO 1: Via SCP (Recomendado para 1 servidor)

### Passo 1: No servidor ORIGEM (onde o addon está)
```bash
cd /opt/mk-auth/admin/addons
tar -czf caixas-addon-v2.0.tar.gz caixas/

# Verificar o arquivo
ls -lh caixas-addon-v2.0.tar.gz
```

### Passo 2: Transferir para servidor DESTINO
```bash
# Substituir pelos dados do seu servidor
scp -r caixas-addon-v2.0.tar.gz usuario@IP_SERVIDOR_DESTINO:/tmp/

# OU copiar diretório inteiro
scp -r caixas/ usuario@IP_SERVIDOR_DESTINO:/opt/mk-auth/admin/addons/
```

### Passo 3: No servidor DESTINO, instalar
```bash
# Se transferiu o tar.gz
cd /opt/mk-auth/admin/addons/
tar -xzf /tmp/caixas-addon-v2.0.tar.gz

# OU se copiou o diretório diretamente, apenas ajuste permissões
sudo chown -R www-data:www-data /opt/mk-auth/admin/addons/caixas
sudo chmod -R 755 /opt/mk-auth/admin/addons/caixas
```

---

## 🔗 OPÇÃO 2: Via Git Clone

### Se o addon está em um repositório Git
```bash
# No servidor DESTINO
cd /opt/mk-auth/admin/addons/
git clone https://seu-repo/caixas.git
# ou
git clone git@seu-servidor:seu-repo/caixas.git

# Ajustar permissões
sudo chown -R www-data:www-data caixas
sudo chmod -R 755 caixas
```

---

## 📦 OPÇÃO 3: Via Script de Deploy Automático

### Criar script de instalação (save como `install.sh`)

```bash
#!/bin/bash

# Cores para output
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${YELLOW}=== Instalador GERENCIADOR FTTH v2.0 ===${NC}"

# Variáveis
ADDON_NAME="caixas"
ADDON_PATH="/opt/mk-auth/admin/addons/$ADDON_NAME"
ADDON_OWNER="www-data"
ADDON_GROUP="www-data"

# 1. Verificar se mkauth está instalado
if [ ! -d "/opt/mk-auth" ]; then
    echo -e "${RED}❌ Erro: mkauth não encontrado em /opt/mk-auth${NC}"
    exit 1
fi

# 2. Verificar se addon já existe
if [ -d "$ADDON_PATH" ]; then
    echo -e "${YELLOW}⚠️  Addon já existe em $ADDON_PATH${NC}"
    read -p "Deseja sobrescrever? (s/n) " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Ss]$ ]]; then
        echo -e "${YELLOW}Instalação cancelada.${NC}"
        exit 1
    fi
    rm -rf "$ADDON_PATH"
fi

# 3. Copiar arquivos
echo -e "${YELLOW}📂 Copiando arquivos do addon...${NC}"
cp -r caixas/ "$ADDON_PATH"

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ Arquivos copiados com sucesso${NC}"
else
    echo -e "${RED}❌ Erro ao copiar arquivos${NC}"
    exit 1
fi

# 4. Ajustar permissões
echo -e "${YELLOW}🔐 Ajustando permissões...${NC}"
sudo chown -R $ADDON_OWNER:$ADDON_GROUP "$ADDON_PATH"
sudo chmod -R 755 "$ADDON_PATH"
sudo chmod -R 644 "$ADDON_PATH"/*.php "$ADDON_PATH"/*.hhvm

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ Permissões ajustadas${NC}"
else
    echo -e "${RED}❌ Erro ao ajustar permissões${NC}"
    exit 1
fi

# 5. Verificar estrutura
echo -e "${YELLOW}🔍 Verificando estrutura...${NC}"
if [ -f "$ADDON_PATH/manifest.json" ] && [ -f "$ADDON_PATH/index.php" ]; then
    echo -e "${GREEN}✅ Estrutura válida${NC}"
else
    echo -e "${RED}❌ Estrutura inválida - faltam arquivos essenciais${NC}"
    exit 1
fi

# 6. Teste de conectividade (opcional)
if [ -f "$ADDON_PATH/src/cto/config/database.php" ]; then
    echo -e "${YELLOW}⚠️  Verifique as credenciais de banco de dados em:${NC}"
    echo "   $ADDON_PATH/src/cto/config/database.php"
fi

echo ""
echo -e "${GREEN}✅ Instalação concluída com sucesso!${NC}"
echo -e "${GREEN}📍 Addon instalado em: $ADDON_PATH${NC}"
echo ""
echo -e "Próximos passos:"
echo -e "1. Acesse o painel: https://seu-servidor/admin/addons/"
echo -e "2. Localize 'GERENCIADOR FTTH' na lista de addons"
echo -e "3. Configure as credenciais de banco de dados se necessário"
echo ""
```

### Executar o script
```bash
# Dar permissão de execução
chmod +x install.sh

# Executar
sudo ./install.sh

# OU com um comando único
bash install.sh
```

---

## 🎯 OPÇÃO 4: Instalação Manual Rápida (Uma Linha)

```bash
# Copiar addon para o servidor remoto
scp -r /opt/mk-auth/admin/addons/caixas user@192.168.x.x:/opt/mk-auth/admin/addons/ && \
ssh user@192.168.x.x "sudo chown -R www-data:www-data /opt/mk-auth/admin/addons/caixas && \
sudo chmod -R 755 /opt/mk-auth/admin/addons/caixas && \
echo '✅ Instalação completa!'"
```

---

## ✅ Verificação Pós-Instalação

```bash
# 1. Verificar se addon está acessível
curl -I http://seu-servidor/admin/addon.php?addon=caixas

# 2. Verificar permissões
ls -la /opt/mk-auth/admin/addons/caixas/

# 3. Verificar logs
tail -f /opt/mk-auth/admin/addons/caixas/error.log

# 4. Testar conexão com banco de dados (se necessário)
php -r "include '/opt/mk-auth/admin/addons/caixas/src/cto/config/database.php'; echo 'DB OK';"
```

---

## 🐛 Solução de Problemas

### Erro: "Permission denied"
```bash
sudo chown -R www-data:www-data /opt/mk-auth/admin/addons/caixas
sudo chmod -R 755 /opt/mk-auth/admin/addons/caixas
```

### Erro: "File not found"
```bash
# Verificar se manifest.json existe
test -f /opt/mk-auth/admin/addons/caixas/manifest.json && echo "OK" || echo "ERRO"
```

### Erro: Conexão SSH para OLT não funciona
```bash
# Verificar se phpseclib está instalado
composer -d /opt/mk-auth/admin/addons/caixas/src/cto/componente/olt install
```

### Erro: Banco de dados não conecta
```bash
# Editar arquivo de config
nano /opt/mk-auth/admin/addons/caixas/src/cto/config/database.php
# Verificar credenciais: host, user, password, database
```

---

## 🔄 Atualizar para Versão Mais Nova

```bash
# 1. Fazer backup da versão atual
cd /opt/mk-auth/admin/addons/
cp -r caixas caixas-backup-v2.0

# 2. Limpar arquivos antigos (manter configurações)
rm -rf caixas/src caixas/*.php caixas/*.hhvm

# 3. Copiar novos arquivos
scp -r novo-caixas/* usuario@servidor:/opt/mk-auth/admin/addons/caixas/

# 4. Ajustar permissões novamente
sudo chown -R www-data:www-data /opt/mk-auth/admin/addons/caixas
sudo chmod -R 755 /opt/mk-auth/admin/addons/caixas
```

---

## 📚 Documentação Adicional

- **Config DB:** `src/cto/config/database.php`
- **Config API:** `src/cto/config/api.php`
- **Models:** `src/cto/models/`
- **Controllers:** `src/cto/componente/*/controller.php`

---

## 📞 Suporte

Para dúvidas ou problemas:
1. Verifique os logs em `/opt/mk-auth/admin/addons/caixas/error.log`
2. Consulte as credenciais de banco de dados
3. Verifique permissões de arquivo e diretório
4. Teste conectividade SSH/Telnet (para módulo OLT)

---

**Versão:** 2.0  
**Autor:** Patrick Nascimento  
**Data:** 1º de Janeiro de 2026
