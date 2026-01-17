<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Services\AuditIntegrityService;
use App\Services\AuditService;
use App\Services\BackupService;
use App\Services\RateLimiterService;

class SistemaController extends Controller
{
    private AuditIntegrityService $integrity;
    private AuditService $audit;
    private BackupService $backup;

    public function __construct()
    {
        $this->integrity = new AuditIntegrityService();
        $this->audit = new AuditService();
        $this->backup = new BackupService();
    }

    public function verificarAuditoria(): void
    {
        if (!Auth::check()) {
            $this->redirect('/login');
        }

        (new RateLimiterService(5, 60))->check('sistema_auditoria_verificar');

        $user = Auth::user();
        if (!isset($user['perfil']) || !in_array($user['perfil'], ['ADMIN_GERAL', 'SUPER_ADMIN'], true)) {
            http_response_code(403);
            echo 'Acesso restrito';
            return;
        }

        $tenantId = isset($user['tenant_id']) ? (int) $user['tenant_id'] : 0;

        $resultado = $this->integrity->verifyTenantChain($tenantId);

        $acaoLog = $resultado['status'] === 'OK' ? 'CHECK_INTEGRIDADE_OK' : 'CHECK_INTEGRIDADE_FALHA';
        $this->audit->logOperacional(
            $acaoLog,
            'logs_auditoria',
            null,
            json_encode($resultado, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );

        $this->view('sistema/auditoria_verificar', [
            'resultado' => $resultado,
            'user' => $user,
        ]);
    }

    public function backups(): void
    {
        if (!Auth::check()) {
            $this->redirect('/login');
        }

        (new RateLimiterService(5, 60))->check('sistema_backups');

        $user = Auth::user();
        if (!isset($user['perfil']) || !in_array($user['perfil'], ['ADMIN_GERAL', 'SUPER_ADMIN'], true)) {
            http_response_code(403);
            echo 'Acesso restrito';
            return;
        }

        $db = \App\Core\Database::connection();
        $stmt = $db->query('SELECT * FROM backups ORDER BY iniciado_em DESC LIMIT 100');
        $backups = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $this->view('sistema/backups', [
            'backups' => $backups,
            'user' => $user,
        ]);
    }

    public function backupsExecutar(): void
    {
        if (!Auth::check()) {
            $this->redirect('/login');
        }

        \App\Core\Security::requireCsrfToken();

        (new RateLimiterService(3, 60))->check('sistema_backups_executar');

        $user = Auth::user();
        if (!isset($user['perfil']) || !in_array($user['perfil'], ['ADMIN_GERAL', 'SUPER_ADMIN'], true)) {
            http_response_code(403);
            echo 'Acesso restrito';
            return;
        }

        $escopo = isset($_POST['escopo']) ? (string) $_POST['escopo'] : 'COMPLETO';
        if (!in_array($escopo, ['BANCO', 'ARQUIVOS', 'COMPLETO'], true)) {
            $escopo = 'COMPLETO';
        }

        if ($escopo === 'BANCO') {
            $this->backup->backupBanco('MANUAL');
        } elseif ($escopo === 'ARQUIVOS') {
            $this->backup->backupArquivos('MANUAL');
        } else {
            $this->backup->backupCompleto('MANUAL');
        }

        $this->redirect('/sistema/backups');
    }

    public function backupsDownload(): void
    {
        if (!Auth::check()) {
            $this->redirect('/login');
        }

        \App\Core\Security::requireCsrfToken();

        (new RateLimiterService(5, 60))->check('sistema_backups_download');

        $user = Auth::user();
        if (!isset($user['perfil']) || !in_array($user['perfil'], ['ADMIN_GERAL', 'SUPER_ADMIN'], true)) {
            http_response_code(403);
            echo 'Acesso restrito';
            return;
        }

        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        if ($id <= 0) {
            http_response_code(400);
            echo 'ID inválido';
            return;
        }

        $db = \App\Core\Database::connection();
        $stmt = $db->prepare('SELECT * FROM backups WHERE id = :id LIMIT 1');
        $stmt->bindValue(':id', $id, \PDO::PARAM_INT);
        $stmt->execute();
        $backup = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$backup) {
            http_response_code(404);
            echo 'Backup não encontrado';
            return;
        }

        $path = $backup['caminho_arquivo'];
        if (!is_string($path) || $path === '' || !is_file($path)) {
            http_response_code(404);
            echo 'Arquivo de backup não encontrado';
            return;
        }

        $nome = basename($path);

        header('Content-Type: application/octet-stream');
        header('Content-Length: ' . filesize($path));
        header('Content-Disposition: attachment; filename="' . $nome . '"');

        readfile($path);
        exit;
    }

    public function verificarAuditoriaExportar(): void
    {
        if (!Auth::check()) {
            $this->redirect('/login');
        }

        (new RateLimiterService(3, 60))->check('sistema_auditoria_verificar_exportar');

        $user = Auth::user();
        if (!isset($user['perfil']) || $user['perfil'] !== 'SUPER_ADMIN') {
            http_response_code(403);
            echo 'Acesso restrito';
            return;
        }

        $tenantId = isset($user['tenant_id']) ? (int) $user['tenant_id'] : 0;

        $resultado = $this->integrity->verifyTenantChain($tenantId);

        $acaoLog = $resultado['status'] === 'OK' ? 'CHECK_INTEGRIDADE_OK' : 'CHECK_INTEGRIDADE_FALHA';
        $this->audit->logOperacional(
            $acaoLog,
            'logs_auditoria',
            null,
            json_encode($resultado, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );

        if (!class_exists(\TCPDF::class)) {
            http_response_code(500);
            echo 'Biblioteca TCPDF não instalada. Execute composer install.';
            return;
        }

        $pdf = new \TCPDF();
        $pdf->SetCreator('GED Institucional');
        $pdf->SetAuthor('Sistema GED');
        $pdf->SetTitle('Relatório de Integridade da Auditoria');
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->AddPage();

        $agora = new \DateTime('now', new \DateTimeZone('America/Sao_Paulo'));

        $html = '<h1>Relatório de Integridade da Auditoria</h1>';
        $html .= '<p><strong>Tenant:</strong> ' . ($tenantId ?: '-') . '</p>';
        $html .= '<p><strong>Data da verificação:</strong> ' . $agora->format('d/m/Y H:i:s T') . '</p>';
        $html .= '<p><strong>Status da cadeia:</strong> ' . $resultado['status'] . '</p>';
        $html .= '<p><strong>Total de eventos verificados:</strong> ' . $resultado['total_eventos_verificados'] . '</p>';
        $html .= '<p><strong>Hash inicial:</strong> ' . ($resultado['hash_inicial'] ?? '-') . '</p>';
        $html .= '<p><strong>Hash final:</strong> ' . ($resultado['hash_final'] ?? '-') . '</p>';

        if ($resultado['primeiro_evento_invalido']) {
            $inv = $resultado['primeiro_evento_invalido'];
            $html .= '<h3>Primeiro Evento Inválido</h3>';
            $html .= '<p><strong>ID do log:</strong> ' . $inv['id'] . '</p>';
            $html .= '<p><strong>Hash anterior esperado:</strong> ' . ($inv['esperado_hash_anterior'] ?? '-') . '</p>';
            $html .= '<p><strong>Hash anterior encontrado:</strong> ' . ($inv['encontrado_hash_anterior'] ?? '-') . '</p>';
            $html .= '<p><strong>Hash evento esperado:</strong> ' . $inv['esperado_hash_evento'] . '</p>';
            $html .= '<p><strong>Hash evento encontrado:</strong> ' . $inv['encontrado_hash_evento'] . '</p>';
            $html .= '<p><strong>Ordem cronológica válida:</strong> ' . ($inv['ordem_cronologica_valida'] ? 'Sim' : 'Não') . '</p>';
        }

        $pdf->writeHTML($html, true, false, true, false, '');
        $pdf->Output('relatorio_integridade_auditoria.pdf', 'D');
    }
}
