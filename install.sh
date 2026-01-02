#!/bin/bash
mkdir -p /opt/mk-auth/admin/addons/caixas
cd /opt/mk-auth/admin/addons/caixas
wget -q https://github.com/rapnettelecomunicacoes/caixas-addon/raw/main/caixas-addon-v2.0.zip -O addon.zip
unzip -o addon.zip
rm -f addon.zip
chown -R www-data:www-data /opt/mk-auth/admin/addons/caixas 2>/dev/null
chmod -R 755 /opt/mk-auth/admin/addons/caixas 2>/dev/null
ADDON_JS="/opt/mk-auth/admin/assets/js/addon.js"
if [ -f "$ADDON_JS" ]; then
    if ! grep -q "GERENCIADOR FTTH" "$ADDON_JS"; then
        echo "" >> "$ADDON_JS"
        echo "// ------------------- GERENCIADOR FTTH - Caixas -------------------" >> "$ADDON_JS"
        echo "const caixas = window.location.protocol + \"//\" + window.location.hostname + (window.location.port ? ':' + window.location.port: '') + \"/admin/addons/caixas/\";" >> "$ADDON_JS"
        echo "add_menu.opcoes('{\"plink\": \"' + caixas + '?_route=painel\", \"ptext\": \"Gerenciador FTTH\"}');" >> "$ADDON_JS"
        echo "// ------------------- GERENCIADOR FTTH - Caixas -------------------" >> "$ADDON_JS"
    fi
fi
echo "✅ Addon instalado com sucesso!"
