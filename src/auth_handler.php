<?php
/**
 * GESTOR DE AUTENTICAÇÃO FLEXÍVEL - ADDON CAIXAS
 * Compatível com múltiplas configurações de mk-auth
 * 
 * Tenta detectar a forma de autenticação usada em qualquer servidor
 */

class AuthHandler {
    private static $authenticated = null;
    private static $session_var = null;
    
    /**
     * Verificar se o usuário está autenticado
     * Funciona com qualquer variável de sessão do mk-auth
     */
    public static function isAuthenticated() {
        // Se já foi verificado, retorna em cache
        if (self::$authenticated !== null) {
            return self::$authenticated;
        }
        
        // Iniciar sessão se não estiver iniciada
        if (session_status() === PHP_SESSION_NONE) {
            session_name('mka');
            session_start();
        }
        
        // Lista de possíveis variáveis de autenticação do mk-auth
        $auth_variables = [
            'mka_logado',           // Padrão do mk-auth
            'MKA_Logado',          // Variante maiúscula
            'logado',              // Alternativa simples
            'authenticated',       // Padrão internacional
            'is_authenticated',    // Padrão alternativo
            'user_id',             // Verificar por ID do usuário
            'usuario_id',          // Variante portuguesa
            'id_usuario',          // Variante portuguesa alternativa
            'login_status',        // Status de login
            'is_logged_in',        // Alternativa
            'auth',                // Simples
            'user_logado',         // Combinação
            'admin_logado'         // Admin específico
        ];
        
        // Verificar cada variável possível
        foreach ($auth_variables as $var) {
            if (isset($_SESSION[$var])) {
                $value = $_SESSION[$var];
                
                // Considerar verdadeiro se:
                // - Boolean true
                // - String não vazia
                // - Número > 0
                if ($value === true || 
                    ($value !== false && !empty($value)) ||
                    (is_numeric($value) && $value > 0)) {
                    
                    self::$authenticated = true;
                    self::$session_var = $var;
                    
                    // Log para debug
                    error_log("AuthHandler: Autenticação detectada via \$_SESSION['$var']");
                    return true;
                }
            }
        }
        
        // Se chegou aqui, não está autenticado
        self::$authenticated = false;
        
        // Log para debug
        error_log("AuthHandler: Nenhuma variável de autenticação encontrada");
        error_log("AuthHandler: SESSION: " . json_encode($_SESSION));
        
        return false;
    }
    
    /**
     * Obter qual variável de sessão é usada
     */
    public static function getAuthVariable() {
        if (self::$session_var === null) {
            self::isAuthenticated(); // Forçar detecção
        }
        return self::$session_var;
    }
    
    /**
     * Redirecionar para login se não autenticado
     */
    public static function requireAuth($redirect_url = '../../') {
        if (!self::isAuthenticated()) {
            header("Location: $redirect_url");
            exit();
        }
    }
    
    /**
     * Obter informações do usuário autenticado
     */
    public static function getUserInfo() {
        if (!self::isAuthenticated()) {
            return null;
        }
        
        // Tentar obter informações do usuário em variáveis possíveis
        $user_info = [
            'authenticated' => true,
            'auth_variable' => self::$session_var,
            'full_session' => $_SESSION
        ];
        
        // Procurar por variáveis de usuário comuns
        $user_vars = ['usuario', 'user', 'username', 'nome', 'name', 'email', 'user_email'];
        foreach ($user_vars as $var) {
            if (isset($_SESSION[$var])) {
                $user_info[$var] = $_SESSION[$var];
            }
        }
        
        return $user_info;
    }
}

?>
