<?php
/**
 * Script de Verificação Manual via CLI
 * 
 * Como usar:
 * php tests/manual_verification.php [tenant_id]
 */

declare(strict_types=1);

// Configuração básica de diretórios
define('BASE_PATH', dirname(__DIR__));

// Autoloader simplificado para o namespace App
spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    $baseDir = BASE_PATH . '/app/';
    $len = strlen($prefix);
    
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    
    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    
    if (is_file($file)) {
        require $file;
    }
});

// Carregar variáveis de ambiente
if (file_exists(BASE_PATH . '/app/Core/Env.php')) {
    require_once BASE_PATH . '/app/Core/Env.php';
    if (class_exists('App\Core\Env')) {
        \App\Core\Env::load(BASE_PATH . '/.env');
    }
}

// Verificar se classes necessárias existem
if (!class_exists('App\Services\AuditIntegrityService')) {
    die("Erro: Classe AuditIntegrityService não encontrada.\n");
}

use App\Services\AuditIntegrityService;
use App\Core\Database;

echo "\n=== Verificação de Integridade da Auditoria (CLI) ===\n\n";

try {
    // Tenta conectar ao banco para garantir que as configurações estão OK
    $db = Database::connection();
    echo "Conexão com banco de dados: OK\n";
} catch (\Throwable $e) {
    die("Erro ao conectar ao banco de dados: " . $e->getMessage() . "\nVerifique o arquivo .env\n");
}

// Obter Tenant ID do argumento ou usar padrão
$tenantId = isset($argv[1]) ? (int)$argv[1] : 0;

// Se não foi passado argumento, tenta descobrir um tenant com logs
if ($tenantId === 0) {
    echo "Nenhum Tenant ID fornecido. Buscando tenant com logs...\n";
    $stmt = $db->query("SELECT DISTINCT tenant_id FROM logs_auditoria LIMIT 1");
    $tenantId = $stmt->fetchColumn();
    
    if (!$tenantId) {
        die("Nenhum log de auditoria encontrado no banco. Execute o setup primeiro via navegador.\n");
    }
    echo "Tenant encontrado: $tenantId\n";
}

echo "Iniciando verificação para Tenant ID: $tenantId...\n";

try {
    $service = new AuditIntegrityService();
    $resultado = $service->verifyTenantChain((int)$tenantId);
    
    echo "\n--------------------------------------------------\n";
    echo "RESULTADO DA VERIFICAÇÃO\n";
    echo "--------------------------------------------------\n";
    echo "Status: " . ($resultado['status'] === 'OK' ? "\033[32mOK (ÍNTEGRO)\033[0m" : "\033[31m" . $resultado['status'] . "\033[0m") . "\n";
    echo "Total de Eventos: " . $resultado['total_eventos_verificados'] . "\n";
    echo "Data da Verificação: " . $resultado['data_verificacao'] . "\n";
    echo "Hash Inicial: " . ($resultado['hash_inicial'] ?? 'N/A') . "\n";
    echo "Hash Final: " . ($resultado['hash_final'] ?? 'N/A') . "\n";
    
    if ($resultado['status'] !== 'OK' && isset($resultado['primeiro_evento_invalido'])) {
        echo "\nDETALHES DO ERRO:\n";
        print_r($resultado['primeiro_evento_invalido']);
    }
    
    echo "\n";
    
} catch (\Throwable $e) {
    echo "Erro durante a verificação: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
