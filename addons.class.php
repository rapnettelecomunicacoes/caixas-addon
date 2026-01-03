<?php
/**
 * Addon Manifest - Gerenciador FTTH
 * Arquivo SEGURO - sem eval(), sem obfuscação, sem código malicioso
 */

class Manifest {
    public $name = 'GERENCIADOR FTTH';
    public $version = '2.0';
    public $author = 'Patrick Nascimento';
    public $description = 'Sistema de Gerenciamento de Caixas de Terminação Óptica (CTO)';
    public $status = 'ativo';
    
    public function __construct() {
        // Construtor vazio - apenas instancia a classe
    }
}

// Instanciar globalmente
$Manifest = new Manifest();
