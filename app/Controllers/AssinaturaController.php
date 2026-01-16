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

        $pendentes = $this->assinatura->pendentesPorUsuario((int) $user['id']);

        $missingLib = !class_exists(\setasign\Fpdi\Tcpdf\Fpdi::class);

        $this->view('assinaturas/painel', [
            'user' => $user,
            'pendentes' => $pendentes,
            'missingLib' => $missingLib,
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

    public function configurar(): void
    {
        if (!Auth::check()) {
            $this->redirect('/login');
        }

        $user = Auth::user();

        $documentoId = isset($_GET['documento_id']) ? (int) $_GET['documento_id'] : 0;
        if ($documentoId <= 0) {
            http_response_code(400);
            echo 'Documento inválido';
            return;
        }

        $documento = $this->documento->find($documentoId);
        if (!$documento) {
            http_response_code(404);
            echo 'Documento não encontrado';
            return;
        }

        if ($documento['status'] === 'ASSINADO') {
            http_response_code(400);
            echo 'Documento já assinado não permite alterar fluxo de assinaturas';
            return;
        }

        $pastaId = (int) $documento['pasta_id'];
        if (!$this->permissao->canSign((int) $user['id'], $pastaId, $user['perfil'])) {
            http_response_code(403);
            echo 'Sem permissão para configurar assinaturas nesta pasta';
            return;
        }

        $assinaturas = $this->assinatura->findByDocumento($documentoId);
        $userModel = new \App\Models\User();
        $usuarios = $userModel->all();

        $this->view('assinaturas/configurar', [
            'user' => $user,
            'documento' => $documento,
            'assinaturas' => $assinaturas,
            'usuarios' => $usuarios,
        ]);
    }

    public function salvarConfiguracao(): void
    {
        if (!Auth::check()) {
            $this->redirect('/login');
        }

        Security::requireCsrfToken();

        $user = Auth::user();

        $documentoId = isset($_POST['documento_id']) ? (int) $_POST['documento_id'] : 0;
        if ($documentoId <= 0) {
            http_response_code(400);
            echo 'Documento inválido';
            return;
        }

        $documento = $this->documento->find($documentoId);
        if (!$documento) {
            http_response_code(404);
            echo 'Documento não encontrado';
            return;
        }

        if ($documento['status'] === 'ASSINADO') {
            http_response_code(400);
            echo 'Documento já assinado não permite alterar fluxo de assinaturas';
            return;
        }

        $pastaId = (int) $documento['pasta_id'];
        if (!$this->permissao->canSign((int) $user['id'], $pastaId, $user['perfil'])) {
            http_response_code(403);
            echo 'Sem permissão para configurar assinaturas nesta pasta';
            return;
        }

        $usuarios = isset($_POST['usuario_id']) && is_array($_POST['usuario_id']) ? $_POST['usuario_id'] : [];
        $ordens = isset($_POST['ordem']) && is_array($_POST['ordem']) ? $_POST['ordem'] : [];

        $itens = [];
        foreach ($usuarios as $index => $usuarioId) {
            $usuarioId = (int) $usuarioId;
            $ordem = isset($ordens[$index]) ? (int) $ordens[$index] : 0;
            if ($usuarioId > 0 && $ordem > 0) {
                $itens[] = [
                    'usuario_id' => $usuarioId,
                    'ordem' => $ordem,
                ];
            }
        }

        $pdo = \App\Core\Database::connection();
        $pdo->beginTransaction();

        try {
            $stmtDelete = $pdo->prepare('DELETE FROM assinaturas WHERE documento_id = :documento_id');
            $stmtDelete->execute([':documento_id' => $documentoId]);

            foreach ($itens as $item) {
                $this->assinatura->insert([
                    'documento_id' => $documentoId,
                    'usuario_id' => $item['usuario_id'],
                    'ordem' => $item['ordem'],
                    'status' => 'PENDENTE',
                ]);
            }

            if (count($itens) === 0) {
                $this->documento->update($documentoId, ['status' => 'EM_EDICAO']);
            } else {
                $this->documento->update($documentoId, ['status' => 'PENDENTE_ASSINATURA']);
            }

            $this->audit->log('CONFIGURAR_ASSINATURAS', 'documentos', $documentoId);

            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            http_response_code(500);
            echo 'Erro ao salvar configuração de assinaturas';
            return;
        }

        $this->redirect('/assinaturas/configurar?documento_id=' . $documentoId);
    }
}
