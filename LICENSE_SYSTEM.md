# 🔐 SISTEMA DE LICENCIAMENTO - GERENCIADOR FTTH v2.0

## Visão Geral

O GERENCIADOR FTTH inclui um **sistema completo de licenciamento** que permite:

- ✅ Gerar chaves de licença para clientes
- ✅ Validar e gerenciar licenças
- ✅ Controlar período de validade
- ✅ Bloquear/desbloquear funcionalidades
- ✅ Avisos de expiração próxima

---

## Para Proprietários/Distribuidores

### Acessar o Painel de Administração

1. Conecte-se ao servidor via SSH
2. Acesse o painel de licenças:
   ```bash
   # URL do painel
   http://seu-servidor/admin/addons/caixas/src/license_admin.php
   ```
3. Faça login com suas credenciais mkauth

### Gerar Uma Licença

**Passo 1:** Acesse a aba "Gerar Licença"

**Passo 2:** Preencha os dados:
- **Nome do Cliente**: Nome da empresa do cliente
- **Dias de Validade**: Quantos dias a licença será válida (padrão 365 dias = 1 ano)
- **Permanente**: Marca esta opção para criar uma licença sem expiração

**Passo 3:** Clique em "Gerar Licença"

**Passo 4:** Copie a chave gerada (Formato: XXXX-XXXX-XXXX-XXXX)

**Passo 5:** Compartilhe com o cliente via email seguro

### Exemplos de Geração

#### Exemplo 1: Licença por 1 Ano
```
Cliente: Empresa XYZ Telecomunicações
Dias: 365
Permanente: Não marcado
Resultado: Licença válida por 365 dias a partir de hoje
```

#### Exemplo 2: Licença Permanente
```
Cliente: Empresa ABC Internet
Dias: (ignorado)
Permanente: ✓ Marcado
Resultado: Licença válida para sempre, nunca expira
```

#### Exemplo 3: Licença por 3 Anos
```
Cliente: Telecom do Sul
Dias: 1095
Permanente: Não marcado
Resultado: Licença válida por 3 anos
```

### Acompanhamento de Licenças

Acesse a aba "Status da Licença" para ver:
- Cliente para o qual a licença foi gerada
- Data de criação
- Data de expiração
- Servidor onde foi instalada
- Dias restantes (se aplicável)

---

## Para Clientes

### Instalação de Licença

#### Via Web (Painel)

1. Acesse o GERENCIADOR FTTH:
   ```
   http://seu-servidor/admin/addons/caixas/
   ```

2. Vá até "Configurações" → "Licença" ou "Segurança" → "Ativar Licença"

3. Cole a chave recebida no campo "Chave de Licença"
   - Formato esperado: `XXXX-XXXX-XXXX-XXXX`

4. (Opcional) Insira o nome da sua empresa

5. Clique em "Validar Licença"

6. Pronto! O addon está desbloqueado.

#### Via Comando (Terminal - Opcional)

Se preferir instalar via linha de comando:

```bash
# 1. Conecte-se ao servidor
ssh usuario@seu-servidor

# 2. Navegue até o addon
cd /opt/mk-auth/admin/addons/caixas

# 3. Execute um script PHP para instalar
php -r "
require_once 'src/LicenseManager.php';
\$lic = new LicenseManager();
\$chave = 'XXXX-XXXX-XXXX-XXXX'; // Sua chave aqui
\$resultado = \$lic->validateLicense(\$chave);
if (\$resultado['valida']) {
    echo '✅ Licença válida!';
} else {
    echo '❌ Erro: ' . \$resultado['erro'];
}
"
```

### Verificar Status da Licença

Para verificar se a licença está instalada e ativa:

1. Acesse: `http://seu-servidor/admin/addons/caixas/src/license_client.php`

2. O painel mostrará:
   - ✅ Se a licença está ativa
   - ⚠️ Se está próxima de expirar
   - ❌ Se está expirada

### Renovar Licença

Quando sua licença estiver próxima de expirar (30 dias antes):

1. Contacte o seu provedor
2. Solicite uma nova chave de licença
3. Remova a licença anterior (opcional)
4. Instale a nova chave
5. Pronto!

---

## Formato e Segurança das Chaves

### Estrutura da Chave

```
XXXX-XXXX-XXXX-XXXX
├─ Primeiros 16 caracteres: Identificador único
└─ Derivado de SHA-256 do dados do cliente + chave-mestre
```

### Algoritmo de Criptografia

- **Hash**: SHA-256
- **Dados inclusos**: Nome do cliente, data, versão, chave-mestre
- **Validação**: Verifica integridade no servidor local
- **Armazenamento**: Arquivo `license.json` com permissões 0644

### Segurança

✅ Chaves são criptografadas localmente  
✅ Não requer conexão com internet para validar  
✅ Arquivo de licença protegido  
✅ Cada chave é única e intransferível  
✅ Validação automática em tempo de execução  

---

## Arquivo de Licença

### Localização
```
/opt/mk-auth/admin/addons/caixas/license.json
```

### Conteúdo Exemplo
```json
{
  "chave": "ABCD-EFGH-IJKL-MNOP",
  "cliente": "Empresa XYZ Telecomunicações",
  "criacao": "2026-01-01 10:30:45",
  "expiracao": "2027-01-01 10:30:45",
  "versao": "2.0",
  "instalado_em": "2026-01-01 15:45:30",
  "servidor": "web-prod-01"
}
```

### Permissões
```bash
# Permissões recomendadas
-rw-r--r-- 1 www-data www-data 256 jan  1 15:45 license.json
```

---

## Avisos e Notificações

### Aviso de Expiração Próxima

Quando faltam menos de 30 dias para expirar:
- ⚠️ Barra amarela aparece no topo do painel
- Mensagem: "Licença expira em X dias"
- Addon continua funcionando normalmente

### Licença Expirada

Quando a data de expiração passa:
- ❌ Painel mostra "Licença Expirada"
- Addon entra em **modo de teste**
- Funcionalidades limitadas (configurável)
- Cliente pode renovar ou remover e reinstalar

### Sem Licença

Se nenhuma licença foi instalada:
- ℹ️ Addon funciona em **modo de teste**
- Todas as funcionalidades estão disponíveis
- Aviso: "Modo de teste - sem licença"

---

## Troubleshooting

### Chave Inválida

**Erro:** "Chave de licença inválida"

**Soluções:**
1. Verifique se copou toda a chave (16 caracteres + 3 hífens)
2. Verifique se não há espaços extras
3. Peça uma nova chave ao seu provedor
4. Certifique-se de que é a chave correta (podem ser múltiplas)

### Licença Expirada

**Erro:** "Licença expirada"

**Soluções:**
1. Contacte o seu provedor
2. Solicite uma nova chave
3. Remova a antiga (se necessário)
4. Instale a nova chave

### Arquivo de Licença Corrompido

**Erro:** "Arquivo de licença corrompido"

**Solução:**
```bash
# Remova o arquivo corrompido
rm /opt/mk-auth/admin/addons/caixas/license.json

# Reinstale a licença via painel
# Ou contacte o suporte
```

### Permissões Insuficientes

**Erro:** "Diretório não tem permissão de escrita"

**Solução:**
```bash
sudo chown -R www-data:www-data /opt/mk-auth/admin/addons/caixas
sudo chmod -R 755 /opt/mk-auth/admin/addons/caixas
```

---

## API para Desenvolvedores

### Verificar Licença Programaticamente

```php
<?php
require_once 'src/LicenseManager.php';

$licenseManager = new LicenseManager();
$status = $licenseManager->getLicenseStatus();

if ($status['instalada']) {
    echo "Licença instalada para: " . $status['cliente'];
    
    if (isset($status['dias_restantes'])) {
        echo "Dias restantes: " . $status['dias_restantes'];
    }
} else {
    echo "Nenhuma licença instalada";
}
?>
```

### Validar uma Chave

```php
<?php
$licenseManager = new LicenseManager();
$resultado = $licenseManager->validateLicense('XXXX-XXXX-XXXX-XXXX');

if ($resultado['valida']) {
    echo "✅ Licença válida!";
    echo "Cliente: " . $resultado['cliente'];
} else {
    echo "❌ Erro: " . $resultado['erro'];
}
?>
```

### Middleware de Proteção

```php
<?php
require_once 'src/LicenseMiddleware.php';

$middleware = new LicenseMiddleware();

if (!$middleware->isValid()) {
    die("Licença inválida ou não instalada");
}

// Avisar se próximo de expirar
if ($middleware->isNearExpiration()) {
    echo "Atenção: Licença expira em breve";
}

// Renderizar aviso na página
$middleware->renderWarning();
?>
```

---

## Perguntas Frequentes

### P: É possível transferir uma licença para outro servidor?

**R:** Sim, a chave é válida em qualquer servidor. Basta:
1. Remover a licença do servidor antigo
2. Instalar no novo servidor
3. A chave continua válida

### P: E se eu perder a chave?

**R:** Contacte o seu provedor. Ele pode regenerar uma nova chave para você com os mesmos dados.

### P: Funciona sem internet?

**R:** Sim! A validação é feita localmente. Não precisa de conexão com internet.

### P: Posso ter múltiplas licenças?

**R:** Atualmente, apenas uma licença por instalação. Contate o suporte para casos especiais.

### P: Quanto tempo leva para a chave começar a funcionar?

**R:** Imediatamente após validar. Apenas refresque o navegador.

### P: O que acontece se a licença expirar?

**R:** O addon volta ao modo de teste. Funcionalidades continuam acessíveis mas com avisos.

---

## Suporte

Para dúvidas sobre licenças:

1. Verifique este documento
2. Consulte o painel de administração (license_admin.php)
3. Contacte o seu provedor
4. Email: suporte@seu-provedor.com

---

**Versão:** 2.0  
**Autor:** Patrick Nascimento  
**Data:** 1º de Janeiro de 2026
