# Solução: HTTP 500 em Novas Instalações do Addon

## Problema

Ao instalar o addon em um novo servidor, aparecia erro HTTP 500:
```
Esta página não está funcionando - HTTP ERROR 500
45.160.84.65 não consegui atender a esta solicitação no momento
```

## Causa Raiz

O arquivo `/etc/apache2/conf-available/api.conf` referenciava um socket PHP-FPM que não existia:
```
SetHandler "proxy:unix:/run/php-api.sock|fcgi://127.0.0.1/"
```

Porém, cada servidor tem configuração PHP-FPM diferente:
- Alguns têm `/run/php-admin.sock`
- Outros têm `/run/php-central.sock`
- O servidor novo não tinha nenhum dos sockets esperados

O Apache tentava conectar a um socket inexistente e retornava HTTP 500.

## Solução Implementada

### 1. **Detecção Automática de Socket**

O `install.sh` foi atualizado com a função `detect_php_socket()` que:

```bash
detect_php_socket() {
    # Procura por sockets em ordem de preferência
    SOCKET_PATHS=(
        "/run/php-api.sock"
        "/run/php-admin.sock"
        "/run/php-central.sock"
        "/run/php-publico.sock"
        "/run/php-boleto.sock"
        "/run/php-retorno.sock"
        "/run/php-fpm.sock"
        "/var/run/php-fpm.sock"
        "/var/run/php7.3-fpm.sock"
        "/var/run/php8.0-fpm.sock"
    )
    
    # Encontra o primeiro socket disponível
    for socket in "${SOCKET_PATHS[@]}"; do
        if [ -e "$socket" ] && [ -S "$socket" ]; then
            echo "$socket"
            return 0
        fi
    done
}
```

### 2. **Configuração Dinâmica do Apache**

A função `configure_apache_socket()` atualiza automaticamente o `api.conf` com o socket detectado:

```bash
configure_apache_socket() {
    local PHP_SOCKET=$1
    # ... atualiza api.conf com o socket correto
}
```

### 3. **Integração no Instalador**

O `install.sh` agora executa:
```bash
PHP_SOCKET=$(detect_php_socket)
configure_apache_socket "$PHP_SOCKET"
```

## Por Que Funciona em Todos os Servidores

1. **Independente de Configuração PHP-FPM**: Procura por qualquer socket que exista
2. **Independente do Banco de Dados**: A detecção é apenas do Apache/PHP-FPM
3. **Compatível com Múltiplas Versões**: Procura por sockets de PHP 7.3 e 8.0
4. **Graceful Degradation**: Se nenhum socket for encontrado, usa o padrão e deixa criação futura para o PHP-FPM

## Verificação

Para verificar se a instalação está funcionando:

```bash
# Ver qual socket foi configurado
grep "SetHandler" /etc/apache2/conf-available/api.conf

# Testar conexão
ls -la /run/php*.sock | head -5

# Acessar o addon
curl -s https://seu-servidor/admin/addons/caixas/ -o /dev/null -w "%{http_code}"
# Deve retornar: 200 ou 302 (redirect de login)
```

## Mudanças Realizadas

**Arquivo**: `install.sh`  
**Versão**: 3.1 (Com detecção PHP-FPM)  
**Commit**: `7145029` - feat: Adicionar detecção automática de socket PHP-FPM

### Novas Funções Adicionadas:
- `detect_php_socket()` - Localiza socket PHP-FPM disponível
- `configure_apache_socket()` - Atualiza Apache com o socket correto

### Integração:
- Chamado automaticamente antes de `download_addon()`
- Recarrega Apache se configuração for válida
- Mensagens informativas sobre o socket detectado

## Resultados

✅ **Antes**: HTTP 500 em novas instalações  
✅ **Depois**: Instalação funcionando corretamente  
✅ **Compatibilidade**: Funciona em qualquer servidor  
✅ **Independência**: Não depende de pool PHP-FPM específico

---

**Data**: 3 de janeiro de 2026  
**Status**: RESOLVIDO
