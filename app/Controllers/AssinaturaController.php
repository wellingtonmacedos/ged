<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Security;
use App\Models\Assinatura;
use App\Models\Documento;
use App\Models\DocumentoArquivo;
use App\Services\AuditService;
use App\Services\PermissionService;
use App\Services\SignatureService;

class AssinaturaController extends Controller
{
    private Assinatura $assinatura;
    private Documento $documento;
    private DocumentoArquivo $arquivo;
    private PermissionService $permissao;
    private AuditService $audit;
    private SignatureService $signatureService;

    public function __construct()
    {
        $this->assinatura = new Assinatura();
        $this->documento = new Documento();
        $this->arquivo = new DocumentoArquivo();
        $this->permissao = new PermissionService();
        $this->audit = new AuditService();
        $this->signatureService = new SignatureService();
    }

    public function painel(): void
    {
        if (!Auth::check()) {
            $this->redirect('/login');
        }

        $user = Auth::user();

        $sql = 'SELECT a.*, d.titulo FROM assinaturas a INNER JOIN documentos d ON d.id = a.documento_id WHERE a.usuario_id = :usuario_id AND a.status = "PENDENTE" ORDER BY a.ordem ASC';
        $stmt = $this->assinatura->db->prepare($sql);
        $stmt->bindValue(':usuario_id', $user['id']);
        $stmt->execute();
        $pendentes = $stmt->fetchAll();

        $this->view('assinaturas/painel', [
            'user' => $user,
            'pendentes' => $pendentes,
        ]);
    }

    public function assinar(): void
    {
        if (!Auth::check()) {
            $this->redirect('/login');
        }

        Security::requireCsrfToken();

        $user = Auth::user();

        $assinaturaId = isset($_POST['assinatura_id']) ? (int) $_POST['assinatura_id'] : 0;
        if ($assinaturaId <= 0) {
            http_response_code(400);
            echo 'Assinatura inválida';
            return;
        }

        $assinatura = $this->assinatura->find($assinaturaId);
        if (!$assinatura) {
            http_response_code(404);
            echo 'Registro de assinatura não encontrado';
            return;
        }

        if ((int) $assinatura['usuario_id'] !== (int) $user['id']) {
            http_response_code(403);
            echo 'Esta assinatura não pertence ao usuário';
            return;
        }

        $documento = $this->documento->find((int) $assinatura['documento_id']);
        if (!$documento) {
            http_response_code(404);
            echo 'Documento não encontrado';
            return;
        }

        $pastaId = (int) $documento['pasta_id'];
        if (!$this->permissao->canSign((int) $user['id'], $pastaId, $user['perfil'])) {
            http_response_code(403);
            echo 'Sem permissão para assinar documentos nesta pasta';
            return;
        }

        $proxima = $this->assinatura->proximaPendente((int) $assinatura['documento_id']);
        if (!$proxima || (int) $proxima['id'] !== $assinaturaId) {
            http_response_code(400);
            echo 'Ainda não é a vez desta assinatura';
            return;
        }

        $arquivoAtual = $this->arquivo->findAtual((int) $assinatura['documento_id']);
        if (!$arquivoAtual) {
            http_response_code(404);
            echo 'Arquivo do documento não encontrado';
            return;
        }

        $ip = $_SERVER['REMOTE_ADDR'] ?? null;

        // Aplica assinatura visual no PDF (gera nova versão)
        try {
            $this->signatureService->applyVisualSignature(
                (int) $assinatura['documento_id'], 
                $assinaturaId, 
                (int) $user['id']
            );
        } catch (\Throwable $e) {
            http_response_code(500);
            echo 'Erro ao aplicar assinatura visual: ' . $e->getMessage();
            return;
        }

        $integrity = new \App\Services\AuditIntegrityService();
        $userTenantId = $user['tenant_id'] ?? null;
        if ($userTenantId !== null) {
            $resultado = $integrity->verifyTenantChain((int) $userTenantId);
            $acaoLog = $resultado['status'] === 'OK' ? 'CHECK_INTEGRIDADE_OK' : 'CHECK_INTEGRIDADE_FALHA';
            $this->audit->logOperacional(
                $acaoLog,
                'logs_auditoria',
                null,
                json_encode($resultado, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            );
        }

        $this->assinatura->update($assinaturaId, [
            'status' => 'ASSINADO',
            'ip' => $ip,
            'assinado_em' => date('Y-m-d H:i:s'),
        ]);

        $restantes = $this->assinatura->pendentesPorDocumento((int) $assinatura['documento_id']);
        if (count($restantes) === 0) {
            $this->documento->update((int) $assinatura['documento_id'], ['status' => 'ASSINADO']);
        } else {
            $this->documento->update((int) $assinatura['documento_id'], ['status' => 'PENDENTE_ASSINATURA']);
        }

        $this->audit->log('ASSINAR_DOCUMENTO', 'documentos', (int) $assinatura['documento_id']);

        $this->redirect('/assinaturas/painel');
    }
}
