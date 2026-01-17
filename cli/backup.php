<?php
declare(strict_types=1);

use App\Core\Env;
use App\Services\BackupService;

require __DIR__ . '/../app/Core/Env.php';

Env::load(__DIR__ . '/../.env');

$composerAutoload = __DIR__ . '/../vendor/autoload.php';
if (file_exists($composerAutoload)) {
    require $composerAutoload;
}

spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/../app/';
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

$tipo = 'AUTOMATICO';
$escopo = 'COMPLETO';

foreach ($argv as $arg) {
    if (str_starts_with($arg, '--escopo=')) {
        $value = strtoupper(substr($arg, strlen('--escopo=')));
        if (in_array($value, ['BANCO', 'ARQUIVOS', 'COMPLETO'], true)) {
            $escopo = $value;
        }
    }
}

$service = new BackupService();

if ($escopo === 'BANCO') {
    $id = $service->backupBanco($tipo);
} elseif ($escopo === 'ARQUIVOS') {
    $id = $service->backupArquivos($tipo);
} else {
    $id = $service->backupCompleto($tipo);
}

echo 'Backup criado com ID ' . $id . PHP_EOL;

