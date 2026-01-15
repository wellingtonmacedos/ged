<?php
declare(strict_types=1);

namespace App\Core;

require_once __DIR__ . '/Env.php';

class Bootstrap
{
    public static function run(): void
    {
        // Hardening: Timezone padrão
        date_default_timezone_set('America/Sao_Paulo');

        // Carrega env antes de iniciar sessão para poder configurar tempo se necessário (futuro)
        Env::load(self::basePath() . '/.env');

        // Hardening: Logs e Erros
        $debug = getenv('APP_DEBUG') === 'true';
        if (!$debug) {
            ini_set('display_errors', '0');
            ini_set('log_errors', '1');
            $logFile = self::basePath() . '/storage/logs/app-' . date('Y-m-d') . '.log';
            if (!is_dir(dirname($logFile))) {
                mkdir(dirname($logFile), 0755, true);
            }
            ini_set('error_log', $logFile);
            error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
        } else {
            ini_set('display_errors', '1');
            error_reporting(E_ALL);
        }

        if (session_status() !== PHP_SESSION_ACTIVE) {
            // Hardening: Configuração segura de sessão
            if (getenv('APP_ENV') === 'production') {
                ini_set('session.cookie_httponly', '1');
                ini_set('session.use_only_cookies', '1');
                // ini_set('session.cookie_secure', '1'); // Habilitar se usar HTTPS
            }
            session_start();
        }
        
        // Carrega autoloader do Composer se existir
        $composerAutoload = self::basePath() . '/vendor/autoload.php';
        if (file_exists($composerAutoload)) {
            require_once $composerAutoload;
        }

        self::registerAutoloader();
        $router = new Router();
        Routes::register($router);

        $requestPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';
        $scriptDir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
        if ($scriptDir !== '' && $scriptDir !== '/' && str_starts_with($requestPath, $scriptDir)) {
            $requestPath = substr($requestPath, strlen($scriptDir)) ?: '/';
        }

        $router->dispatch($_SERVER['REQUEST_METHOD'], $requestPath);
    }

    private static function registerAutoloader(): void
    {
        spl_autoload_register(function (string $class): void {
            $prefix = 'App\\';
            $baseDir = self::basePath() . '/app/';
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
    }

    public static function basePath(): string
    {
        return dirname(__DIR__, 2);
    }
}
