#!/bin/bash
mkdir -p /opt/mk-auth/admin/addons/caixas
cd /opt/mk-auth/admin/addons/caixas
wget -q https://github.com/rapnettelecomunicacoes/caixas-addon/raw/main/caixas-addon-v2.0.zip -O addon.zip
unzip -o addon.zip
rm -f addon.zip
echo "✅ Instalação concluída!"
