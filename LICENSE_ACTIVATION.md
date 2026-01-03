# Ativação de Licença - GERENCIADOR FTTH v2.0

## 🔐 Fluxo de Ativação

Quando você instala o addon em um novo servidor, ele vem com a licença **DESATIVADA** por segurança.

### 1️⃣ Instalação

```bash
curl -s https://raw.githubusercontent.com/rapnettelecomunicacoes/caixas-addon/main/install.sh | bash
```

Após a instalação:
- O arquivo `/var/tmp/license_caixas.json` é criado com status `instalada: false`
- O addon está **bloqueado** e só mostra a página de ativação

### 2️⃣ Ativação

1. Acesse o addon: `https://seu-servidor/admin/addons/caixas/`
2. Você será redirecionado para a página de ativação
3. Digite a **chave de licença** no formato: `XXXX-XXXX-XXXX-XXXX`
4. Digite o **nome da sua empresa**
5. Clique em "Ativar Licença"

### 3️⃣ Após Ativação

- O arquivo de licença é atualizado com status `instalada: true`
- A licença é válida por **365 dias**
- Você receberá um aviso 30 dias antes de expirar

## 📄 Arquivo de Licença

Localização: `/var/tmp/license_caixas.json`

## 📞 Suporte

Para obter uma chave de licença ou resolver problemas:
- **Email**: contato@rapnettelecomunicacoes.com.br
- **Telefone**: (79) 99977-3537
