# 📚 GUIA COMPLETO - CONFIGURAR ADDON CAIXAS NO GITHUB

**Data:** 2 de Janeiro de 2026  
**Addon:** GERENCIADOR FTTH v2.0  
**Status:** Passo a Passo

---

## ✅ PASSO 1: Criar Conta no GitHub (Se não tiver)

1. Acesse [github.com](https://github.com)
2. Clique em **Sign up**
3. Preencha:
   - **Username:** seu-usuario
   - **Email:** seu@email.com
   - **Password:** senha-forte
4. Confirme seu email
5. Pronto! Você está logado

---

## 🆕 PASSO 2: Criar Novo Repositório

### Opção A: Via Web (Recomendado)

1. Acesse [github.com/new](https://github.com/new)
2. Preencha os campos:
   - **Repository name:** `caixas-addon`
   - **Description:** `GERENCIADOR FTTH - Addon para mkauth`
   - **Visibility:** ✅ **Public** (importante para comando único funcionar)
3. Clique em **Create repository**
4. Copie a URL que aparecer (algo como `https://github.com/seu-usuario/caixas-addon.git`)

### Opção B: Via Terminal

```bash
gh repo create caixas-addon --public --source=. --remote=origin
```

---

## 📤 PASSO 3: Configurar Git Localmente

### 3.1 - Instalar Git (se não tiver)

```bash
# Ubuntu/Debian
sudo apt-get install git

# CentOS/RHEL
sudo yum install git

# macOS
brew install git
```

### 3.2 - Configurar Identidade Git

```bash
git config --global user.name "Seu Nome"
git config --global user.email "seu@email.com"

# Verificar
git config --global --list
```

---

## 🚀 PASSO 4: Fazer Upload do Addon para GitHub

### 4.1 - Navegue até o diretório do addon

```bash
cd /opt/mk-auth/admin/addons/caixas
```

### 4.2 - Inicializar repositório Git local

```bash
git init
```

### 4.3 - Adicionar todos os arquivos

```bash
git add -A

# Verificar o que será enviado
git status
```

### 4.4 - Fazer o primeiro commit

```bash
git commit -m "Initial commit - GERENCIADOR FTTH v2.0"
```

### 4.5 - Configurar branch principal como 'main'

```bash
git branch -M main
```

### 4.6 - Adicionar repositório remoto (EDITE COM SUA URL)

```bash
# Substitua SEU-USUARIO por seu username do GitHub
git remote add origin https://github.com/SEU-USUARIO/caixas-addon.git

# Verificar
git remote -v
```

### 4.7 - Fazer push (enviar para GitHub)

```bash
git push -u origin main

# Será pedido:
# - Username: seu username do GitHub
# - Password: seu token de acesso (não é a senha)
```

---

## 🔑 PASSO 5: Gerar Token de Acesso (se pedir password)

Se aparecer erro de autenticação, gere um token:

1. Acesse [github.com/settings/tokens](https://github.com/settings/tokens)
2. Clique em **Generate new token (classic)**
3. Preencha:
   - **Token name:** `caixas-addon-push`
   - **Expiration:** 90 days (ou conforme preferir)
   - **Scopes:** ✅ marque `repo`
4. Clique em **Generate token**
5. **Copie o token** (não será mostrado novamente)
6. Use o token como "password" no git push

```bash
# Próxima vez que pedir, cole o token:
git push -u origin main
# Username: seu-usuario
# Password: (cole o token aqui)
```

---

## ✔️ PASSO 6: Verificar Upload no GitHub

1. Acesse [github.com/seu-usuario/caixas-addon](https://github.com/seu-usuario/caixas-addon)
2. Você deve ver:
   - Lista de arquivos do addon
   - Branch `main`
   - Commit message "Initial commit - GERENCIADOR FTTH v2.0"

---

## ⚙️ PASSO 7: Preparar Arquivo de Instalação

O arquivo `install-github.sh` já está pronto, mas precisa ser enviado ao GitHub também.

### 7.1 - Verificar se existe

```bash
ls -la /opt/mk-auth/admin/addons/caixas/install-github.sh
```

### 7.2 - Se não existir, criar

```bash
cp /opt/mk-auth/admin/addons/caixas/install-github.sh ./
```

### 7.3 - Fazer novo commit

```bash
cd /opt/mk-auth/admin/addons/caixas
git add install-github.sh
git commit -m "Add GitHub installer script"
git push
```

---

## 🎯 PASSO 8: Gerar Comando Único

Agora substitua `SEU-USUARIO` e você terá o comando:

```bash
bash <(curl -s https://raw.githubusercontent.com/SEU-USUARIO/caixas-addon/main/install-github.sh)
```

### Exemplo completo:

Se seu username é `patrick-silva`, o comando fica:

```bash
bash <(curl -s https://raw.githubusercontent.com/patrick-silva/caixas-addon/main/install-github.sh)
```

---

## 🧪 PASSO 9: Testar o Comando

Em outro servidor ou máquina:

```bash
# Limpar addon existente (se houver)
sudo rm -rf /opt/mk-auth/admin/addons/caixas

# Testar instalação
bash <(curl -s https://raw.githubusercontent.com/SEU-USUARIO/caixas-addon/main/install-github.sh)
```

---

## 📝 PASSO 10: Atualizar para Novas Versões

Quando tiver atualizações do addon:

```bash
cd /opt/mk-auth/admin/addons/caixas

# Editar arquivos...
# Depois fazer:

git add -A
git commit -m "Update: Nova versão com novas features"
git push
```

---

## 🔗 PASSO 11: Compartilhar com Clientes

Crie um arquivo `README.md` no repositório:

```bash
cat > /opt/mk-auth/admin/addons/caixas/README.md << 'EOF'
# 🚀 GERENCIADOR FTTH v2.0

Addon de gerenciamento de infraestrutura FTTH para mkauth.

## Instalação Rápida

```bash
bash <(curl -s https://raw.githubusercontent.com/SEU-USUARIO/caixas-addon/main/install-github.sh)
```

## Funcionalidades

- ✅ Gerenciamento de CTOs
- ✅ Mapa de infraestrutura
- ✅ Gerenciador OLT
- ✅ Sistema de backup

## Requisitos

- mkauth instalado
- PHP 7.4+
- Banco de dados MySQL/MariaDB

## Suporte

Abra uma issue em: https://github.com/SEU-USUARIO/caixas-addon/issues

EOF

git add README.md
git commit -m "Add README"
git push
```

---

## ✅ RESUMO FINAL

| Passo | Ação | Status |
|-------|------|--------|
| 1 | Criar conta GitHub | ✅ Feito |
| 2 | Criar repositório | ✅ `caixas-addon` |
| 3 | Configurar Git local | ✅ user.name + user.email |
| 4 | Upload de arquivos | ✅ git push |
| 5 | Gerar token (se necessário) | ⚠️ Conforme necessário |
| 6 | Verificar no GitHub | ✅ Ver arquivos online |
| 7 | Adicionar script installer | ✅ `install-github.sh` |
| 8 | Gerar comando único | ✅ `bash <(curl -s ...)` |
| 9 | Testar em outro servidor | ✅ Funciona |
| 10 | Atualizar versões | ✅ git push |
| 11 | Documentar README | ✅ Compartilhar com clientes |

---

## 🆘 TROUBLESHOOTING

### Erro: "Repository not found"
- ✅ Verifique se repositório é PUBLIC
- ✅ Verifique URL (github.com/seu-usuario/caixas-addon)

### Erro: "Permission denied (publickey)"
- ✅ Gere token de acesso (veja PASSO 5)
- ✅ Ou configure SSH key

### Erro: "fatal: 'origin' does not appear to be a 'git' repository"
- ✅ Execute: `git remote -v`
- ✅ Se não aparecer nada, faça: `git remote add origin https://...`

### Arquivo não aparece no GitHub
- ✅ Verifique se fez `git push`
- ✅ Recarregue a página do GitHub (F5)
- ✅ Aguarde alguns segundos

---

## 📚 Links Úteis

- Criar repositório: https://github.com/new
- Gerar token: https://github.com/settings/tokens
- Docs Git: https://git-scm.com/doc
- GitHub Help: https://docs.github.com

---

**Dúvidas? Verifique os passos acima ou abra uma issue no repositório!**

