#!/bin/bash

# QUICK INSTALL COMMANDS - GERENCIADOR FTTH v2.0

# ============================================================================
# COPIAR PARA OUTRO SERVIDOR (Escolha uma opção)
# ============================================================================

# OPÇÃO 1: Via deploy automático (RECOMENDADO)
# bash /opt/mk-auth/admin/addons/caixas/deploy.sh usuario@192.168.1.100

# OPÇÃO 2: Via SCP + Install Script
# scp -r /opt/mk-auth/admin/addons/caixas usuario@192.168.1.100:/tmp/
# ssh usuario@192.168.1.100 "cd /tmp && sudo bash caixas/install.sh"

# OPÇÃO 3: Via TAR + SCP (Método tradicional)
# cd /opt/mk-auth/admin/addons && \
# tar -czf caixas-v2.0.tar.gz caixas/ && \
# scp caixas-v2.0.tar.gz usuario@192.168.1.100:/tmp/ && \
# ssh usuario@192.168.1.100 << 'REMOTE'
# cd /opt/mk-auth/admin/addons && \
# tar -xzf /tmp/caixas-v2.0.tar.gz && \
# sudo chown -R www-data:www-data caixas && \
# sudo chmod -R 755 caixas
# REMOTE

# OPÇÃO 4: Cópia direta via SCP recursivo
# scp -r /opt/mk-auth/admin/addons/caixas usuario@192.168.1.100:/opt/mk-auth/admin/addons/ && \
# ssh usuario@192.168.1.100 "sudo chown -R www-data:www-data /opt/mk-auth/admin/addons/caixas && sudo chmod -R 755 /opt/mk-auth/admin/addons/caixas"

# ============================================================================
# VERIFICAÇÕES PÓS-INSTALAÇÃO
# ============================================================================

# Executar no servidor remoto após instalação:

# ssh usuario@servidor
# 
# # 1. Verificar estrutura
# ls -la /opt/mk-auth/admin/addons/caixas/
# 
# # 2. Verificar manifest
# cat /opt/mk-auth/admin/addons/caixas/manifest.json
# 
# # 3. Verificar permissões
# stat /opt/mk-auth/admin/addons/caixas/ | grep Access
# 
# # 4. Testar acesso web
# curl -I http://localhost/admin/addons/
# 
# # 5. Verificar logs
# tail /opt/mk-auth/admin/addons/caixas/error.log
# 
# # 6. Testar BD (se configurado)
# php -r "include '/opt/mk-auth/admin/addons/caixas/src/cto/config/database.php'; echo 'BD: OK';"

# ============================================================================
# ATUALIZAR VERSÃO EXISTENTE
# ============================================================================

# ssh usuario@servidor
# 
# # Fazer backup
# cd /opt/mk-auth/admin/addons
# cp -r caixas caixas-backup-$(date +%Y%m%d)
# 
# # Remover versão antiga
# sudo rm -rf caixas/*
# 
# # Copiar nova versão
# scp -r /opt/mk-auth/admin/addons/caixas/* usuario@servidor:/opt/mk-auth/admin/addons/caixas/
# 
# # Ajustar permissões
# sudo chown -R www-data:www-data caixas
# sudo chmod -R 755 caixas

# ============================================================================
# REMOVER ADDON COMPLETAMENTE
# ============================================================================

# ssh usuario@servidor
# sudo rm -rf /opt/mk-auth/admin/addons/caixas

# ============================================================================
# TROUBLESHOOTING RÁPIDO
# ============================================================================

# Restaurar permissões
# sudo chown -R www-data:www-data /opt/mk-auth/admin/addons/caixas
# sudo chmod -R 755 /opt/mk-auth/admin/addons/caixas
# sudo find /opt/mk-auth/admin/addons/caixas -type f -name "*.php" -o -name "*.hhvm" | xargs chmod 644

# Resetar propriedade
# sudo chown www-data:www-data /opt/mk-auth/admin/addons/caixas

# Testar conectividade remota
# ping -c 1 servidor_remoto
# ssh -T usuario@servidor_remoto
# ssh usuario@servidor_remoto "php -v"

# ============================================================================
# MONITORAR INSTALAÇÃO
# ============================================================================

# Acompanhar logs em tempo real
# ssh usuario@servidor "tail -f /opt/mk-auth/admin/addons/caixas/error.log"

# Monitorar espaço em disco
# ssh usuario@servidor "du -sh /opt/mk-auth/admin/addons/caixas/"

# ============================================================================
# VARIÁVEIS ÚTEIS
# ============================================================================

export ADDON_NAME="caixas"
export ADDON_VERSION="2.0"
export ADDON_PATH="/opt/mk-auth/admin/addons/caixas"
export ADDON_OWNER="www-data"
export ADDON_GROUP="www-data"
export REMOTE_SERVER="usuario@192.168.1.100"  # EDITE ISSO

# Exemplo de uso:
# echo "Addon: $ADDON_NAME v$ADDON_VERSION"
# echo "Path: $ADDON_PATH"
# echo "Server: $REMOTE_SERVER"

