<?php
declare(strict_types=1);

namespace App\Core;

use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Controllers\PastaController;
use App\Controllers\DocumentoController;
use App\Controllers\AssinaturaController;
use App\Controllers\SistemaController;
use App\Controllers\DepartamentoController;
use App\Controllers\UsuarioController;

class Routes
{
    public static function register(Router $router): void
    {
        $router->get('/', [DashboardController::class, 'index']);

        $router->get('/login', [AuthController::class, 'showLogin']);
        $router->post('/login', [AuthController::class, 'login']);
        $router->get('/logout', [AuthController::class, 'logout']);

        $router->get('/pastas', [PastaController::class, 'index']);
        $router->get('/pastas/children', [PastaController::class, 'children']);
        $router->get('/pastas/nova', [PastaController::class, 'create']);
        $router->post('/pastas/salvar', [PastaController::class, 'store']);

        $router->get('/departamentos', [DepartamentoController::class, 'index']);
        $router->get('/departamentos/list-json', [DepartamentoController::class, 'listJson']);
        $router->get('/departamentos/novo', [DepartamentoController::class, 'create']);
        $router->post('/departamentos/salvar', [DepartamentoController::class, 'store']);

        $router->get('/usuarios/novo', [UsuarioController::class, 'create']);
        $router->post('/usuarios/salvar', [UsuarioController::class, 'store']);

        $router->get('/documentos', [DocumentoController::class, 'index']);
        $router->post('/documentos/upload', [DocumentoController::class, 'upload']);
        $router->get('/documentos/download', [DocumentoController::class, 'download']);
        $router->get('/documentos/stream', [DocumentoController::class, 'stream']);
        $router->get('/documentos/visualizar', [DocumentoController::class, 'visualizar']);
        $router->get('/documentos/busca', [DocumentoController::class, 'busca']);
        $router->get('/documentos/auditoria', [DocumentoController::class, 'auditoria']);
        $router->get('/documentos/auditoria/exportar', [DocumentoController::class, 'auditoriaExportar']);

        $router->get('/assinaturas/painel', [AssinaturaController::class, 'painel']);
        $router->post('/assinaturas/assinar', [AssinaturaController::class, 'assinar']);

        $router->get('/sistema/auditoria/verificar', [SistemaController::class, 'verificarAuditoria']);
$router->get('/sistema/auditoria/verificar/exportar', [SistemaController::class, 'verificarAuditoriaExportar']);

// Rotas de Teste (Remover em produção)
$router->get('/test/audit/setup', [\App\Controllers\TestController::class, 'setup']);
$router->get('/test/audit/corrupt', [\App\Controllers\TestController::class, 'corrupt']);
$router->get('/test/audit/verify', [\App\Controllers\TestController::class, 'verify']);
$router->get('/test/user/setup', [\App\Controllers\TestController::class, 'setupCommonUser']);
    }
}
