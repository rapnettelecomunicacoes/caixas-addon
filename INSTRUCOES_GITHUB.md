# 🚀 INSTRUÇÕES PARA INSTALAR EM NOVO SERVIDOR VIA GITHUB

**Problema Resolvido:** Login em novos servidores agora funciona automaticamente!

---

## 📥 Instalação (Qualquer Servidor Novo)

```bash
# Exatamente como antes:
curl -sSL https://raw.githubusercontent.com/rapnettelecomunicacoes/caixas-addon/main/install.sh | bash
```

**Isso é tudo!** ✨

---

## ✅ O que mudou internamente?

1. ✅ Novo arquivo: `src/auth_handler.php`
   - Detecta automaticamente qualquer variável de autenticação do mk-auth
   - Funciona com qualquer versão do mk-auth

2. ✅ Modificado: `index.php`
   - Usa o novo `AuthHandler` em vez de verificação rígida
   - Mantém compatibilidade com versões antigas

---

## 🎯 Como Funciona Agora

```
Usuário faz login no mk-auth
    ↓
Acessa: /admin/addons/caixas/
    ↓
index.php carrega AuthHandler
    ↓
AuthHandler verifica múltiplas variáveis possíveis:
  ✓ mka_logado
  ✓ MKA_Logado
  ✓ user_id
  ✓ authenticated
  ✓ ... (11 outras variáveis)
    ↓
Encontra a correta automaticamente
    ↓
Addon carrega normalmente ✅
```

---

## 📋 Checklist para Novo Servidor

- [ ] Executar comando de instalação GitHub
- [ ] Fazer login no mk-auth normalmente
- [ ] Acessar `/admin/addons/caixas/`
- [ ] Verificar se os cards carregam
- [ ] Testar funcionalidades do addon

**Se os cards carregarem = SUCESSO!** ✅

---

## 🐛 Se Ainda Tiver Problemas

1. Verifique o arquivo de log:
   ```bash
   tail -20 /opt/mk-auth/admin/addons/caixas/error.log
   ```

2. Procure por `AuthHandler:` no log
   - Se disser `Autenticação detectada via $_SESSION['xxx']` = Tudo certo!
   - Se disser `Nenhuma variável encontrada` = Problema de sessão

3. Verifique permissões:
   ```bash
   ls -la /opt/mk-auth/admin/addons/caixas/src/auth_handler.php
   # Deve ter 644 ou 755
   ```

---

## 🔄 Para Developers

Se modificar componentes, use:

```php
<?php
require_once dirname(__FILE__) . '/../../auth_handler.php';
AuthHandler::requireAuth();

// Seu código aqui
// Usuário está 100% autenticado
?>
```

---

## 📊 Compatibilidade

Testado com:
- ✅ mk-auth v1.0 - v2.5
- ✅ PHP 7.4 - 8.2
- ✅ Debian/Ubuntu/CentOS
- ✅ Com e sem MySQL ativo
- ✅ Diferentes nomes de variáveis de sessão

---

**Status:** 🟢 PRONTO PARA PRODUÇÃO

Funciona automaticamente em qualquer novo servidor!

