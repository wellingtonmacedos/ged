<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Security;
use App\Models\Documento;
use App\Models\DocumentoArquivo;
use App\Models\Pasta;
use App\Services\AuditService;
use App\Services\RateLimiterService;
use App\Services\DocumentService;
use App\Services\PermissionService;

class DocumentoController extends Controller
{
    private Documento $documento;
    private DocumentoArquivo $arquivo;
    private Pasta $pasta;
    private DocumentService $service;
    private PermissionService $permissao;
    private AuditService $audit;
    private \App\Models\DocumentoOcr $documentoOcr;

    public function __construct()
    {
        $this->documento = new Documento();
        $this->arquivo = new DocumentoArquivo();
        $this->pasta = new Pasta();
        $this->service = new DocumentService();
        $this->permissao = new PermissionService();
        $this->audit = new AuditService();
        $this->documentoOcr = new \App\Models\DocumentoOcr();
    }

    public function index(): void
    {
        if (!Auth::check()) {
            $this->redirect('/login');
        }

        $user = Auth::user();
        $pastaId = isset($_GET['pasta_id']) ? (int) $_GET['pasta_id'] : 0;

        if ($pastaId <= 0) {
            $subpastasRoot = [];
            if (isset($user['departamento_id'])) {
                $subpastasRoot = $this->pasta->findByDepartamentoAndParent((int) $user['departamento_id'], null);
            }
            $this->view('documentos/index', [
                'user' => $user,
                'pasta' => null,
                'breadcrumb' => [],
                'documentos' => [],
                'subpastas' => $subpastasRoot,
            ]);
            return;
        }
 
        if (!$this->permissao->canView((int) $user['id'], $pastaId, $user['perfil'])) {
            http_response_code(403);
            echo 'Sem permissão para visualizar esta pasta';
            return;
        }

        $pasta = $this->pasta->find($pastaId);
        if (!$pasta) {
            http_response_code(404);
            echo 'Pasta não encontrada';
            return;
        }

        $termo = isset($_GET['q']) ? trim($_GET['q']) : '';
        
        if (!empty($termo)) {
            $documentos = $this->documento->searchInFolder($pastaId, $termo);
        } else {
            $documentos = $this->documento->findByPasta($pastaId);
        }
        
        $breadcrumb = $this->pasta->getBreadcrumb($pastaId);

        $subpastas = [];
        if (isset($user['departamento_id'])) {
            $subpastas = $this->pasta->findByDepartamentoAndParent((int) $user['departamento_id'], $pastaId);
        }

        $this->view('documentos/index', [
            'user' => $user,
            'pasta' => $pasta,
            'breadcrumb' => $breadcrumb,
            'documentos' => $documentos,
            'subpastas' => $subpastas,
            'fullScreen' => true,
        ]);
    }

    public function upload(): void
    {
        if (!Auth::check()) {
            $this->redirect('/login');
        }

        Security::requireCsrfToken();

        $user = Auth::user();

        $pastaId = isset($_POST['pasta_id']) ? (int) $_POST['pasta_id'] : 0;
        $subpastaId = isset($_POST['subpasta_id']) ? (int) $_POST['subpasta_id'] : 0;
        $titulo = trim((string) ($_POST['titulo'] ?? ''));
        $tipo = trim((string) ($_POST['tipo'] ?? ''));

        $pastaDestinoId = $pastaId;
        if ($subpastaId > 0) {
            $pastaDestinoId = $subpastaId;
        }

        if ($pastaDestinoId <= 0 || $titulo === '' || $tipo === '') {
            $this->redirect('/documentos?pasta_id=' . $pastaId);
        }

        if (!$this->permissao->canUpload((int) $user['id'], $pastaDestinoId, $user['perfil'])) {
            http_response_code(403);
            echo 'Sem permissão para enviar para esta pasta';
            return;
        }

        if (!isset($_FILES['arquivo']) || $_FILES['arquivo']['error'] !== UPLOAD_ERR_OK) {
            http_response_code(400);
            echo 'Falha no upload do arquivo';
            return;
        }

        $arquivo = $_FILES['arquivo'];

        $tamanhoMaximo = 20 * 1024 * 1024;
        if ($arquivo['size'] > $tamanhoMaximo) {
            http_response_code(400);
            echo 'Arquivo maior que o permitido';
            return;
        }

        $ext = strtolower((string) pathinfo($arquivo['name'], PATHINFO_EXTENSION));
        $tiposPermitidos = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'png', 'jpg', 'jpeg'];
        if (!in_array($ext, $tiposPermitidos, true)) {
            http_response_code(400);
            echo 'Tipo de arquivo não permitido';
            return;
        }

        $metadados = [];
        if (isset($_POST['meta_chave'], $_POST['meta_valor']) && is_array($_POST['meta_chave']) && is_array($_POST['meta_valor'])) {
            foreach ($_POST['meta_chave'] as $i => $chave) {
                $chave = trim((string) $chave);
                $valor = trim((string) ($_POST['meta_valor'][$i] ?? ''));
                if ($chave !== '' && $valor !== '') {
                    $metadados[$chave] = $valor;
                }
            }
        }

        $ano = isset($_POST['ano_doc']) ? trim((string) $_POST['ano_doc']) : '';
        $mes = isset($_POST['mes_doc']) ? trim((string) $_POST['mes_doc']) : '';

        if ($ano !== '') {
            $metadados['ano'] = $ano;
        }
        if ($mes !== '') {
            $metadados['mes'] = $mes;
        }

        $this->service->createDocument($pastaDestinoId, $titulo, $tipo, (int) $user['id'], $metadados, $arquivo);

        $this->redirect('/documentos?pasta_id=' . $pastaDestinoId);
    }

    public function download(): void
    {
        $this->audit->log('DOWNLOAD_DOCUMENTO', 'documentos', isset($_GET['id']) ? (int)$_GET['id'] : null);
        $this->serveFile(true);
    }

    public function stream(): void
    {
        (new RateLimiterService(30, 60))->check('documentos_stream');
        $this->serveFile(false);
    }

    private function serveFile(bool $forceDownload): void
    {
        if (!Auth::check()) {
            $this->redirect('/login');
        }

        $user = Auth::user();

        $documentoId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
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

        $pastaId = (int) $documento['pasta_id'];
        if (!$this->permissao->canView((int) $user['id'], $pastaId, $user['perfil'])) {
            http_response_code(403);
            echo 'Sem permissão para visualizar este documento';
            return;
        }

        $arquivo = $this->arquivo->findAtual($documentoId);
        if (!$arquivo) {
            http_response_code(404);
            echo 'Arquivo não encontrado';
            return;
        }

        $caminho = $arquivo['caminho_arquivo'];
        if (!is_file($caminho)) {
            http_response_code(404);
            echo 'Arquivo físico não encontrado';
            return;
        }

        $nome = $documento['titulo'];
        $ext = pathinfo($caminho, PATHINFO_EXTENSION);
        $mime = mime_content_type($caminho);
        
        if ($ext) {
            $nome .= '.' . $ext;
        }

        header('Content-Type: ' . $mime);
        header('Content-Length: ' . filesize($caminho));
        
        if ($forceDownload) {
            header('Content-Disposition: attachment; filename="' . basename($nome) . '"');
        } else {
            header('Content-Disposition: inline; filename="' . basename($nome) . '"');
        }
        
        readfile($caminho);
    }

    public function visualizar(): void
    {
        if (!Auth::check()) {
            $this->redirect('/login');
        }

        (new RateLimiterService(30, 60))->check('documentos_visualizar');

        $documentoId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        if ($documentoId <= 0) {
            http_response_code(400);
            echo 'ID inválido';
            return;
        }

        $user = Auth::user();
        $documento = $this->documento->find($documentoId);
        if (!$documento) {
            http_response_code(404);
            echo 'Documento não encontrado';
            return;
        }
        
        if (!$this->permissao->canView((int) $user['id'], (int) $documento['pasta_id'], $user['perfil'])) {
            http_response_code(403);
            echo 'Sem permissão';
            return;
        }
        
        $this->audit->log('VISUALIZAR_DOCUMENTO', 'documentos', $documentoId);

        $arquivoAtual = $this->arquivo->findAtual($documentoId);
        $ocr = null;
        if ($arquivoAtual) {
            $ocr = $this->documentoOcr->findByDocumentoArquivo($documentoId, (int) $arquivoAtual['id']);
        }

        $this->view('documentos/visualizar', [
            'documento' => $documento,
            'ocr' => $ocr,
        ]);
    }

    public function busca(): void
    {
        if (!Auth::check()) {
            $this->redirect('/login');
        }
        $user = Auth::user();

        $termo = isset($_GET['q']) ? trim($_GET['q']) : null;
        $status = isset($_GET['status']) ? trim($_GET['status']) : null;
        $departamentoId = isset($_GET['departamento_id']) && $_GET['departamento_id'] !== '' ? (int) $_GET['departamento_id'] : null;
        $pastaId = isset($_GET['pasta_id']) && $_GET['pasta_id'] !== '' ? (int) $_GET['pasta_id'] : null;
        $inicio = isset($_GET['data_inicio']) && $_GET['data_inicio'] !== '' ? $_GET['data_inicio'] : null;
        $fim = isset($_GET['data_fim']) && $_GET['data_fim'] !== '' ? $_GET['data_fim'] : null;
        $ano = isset($_GET['ano']) && $_GET['ano'] !== '' ? (int) $_GET['ano'] : null;
        $mes = isset($_GET['mes']) && $_GET['mes'] !== '' ? $_GET['mes'] : null;
        $usarOcr = isset($_GET['ocr']) && $_GET['ocr'] === '1';
        
        // Paginação
        $page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
        $limit = 20;
        $offset = ($page - 1) * $limit;

        // Metadados
        $metadados = [];
        if (isset($_GET['meta_chave'], $_GET['meta_valor'])) {
            $metadados[$_GET['meta_chave']] = $_GET['meta_valor'];
        }

        $resultados = null;
        if ($termo || $status || $departamentoId || $pastaId || $inicio || $fim || $ano || $mes || !empty($metadados)) {
            $resultados = $this->documento->search($termo, $status, $departamentoId, $pastaId, $inicio, $fim, $ano, $mes, $metadados, $limit, $offset, $usarOcr);
            $this->audit->log('BUSCA_DOCUMENTOS', 'sistema', null);
        }

        $depModel = new \App\Models\Departamento();
        $departamentos = $depModel->all();

        $pastaModel = new \App\Models\Pasta();
        $departamentoPastas = $departamentoId;
        if ($departamentoPastas === null && isset($user['departamento_id'])) {
            $departamentoPastas = (int) $user['departamento_id'];
        }
        $pastas = [];
        if ($departamentoPastas !== null) {
            $pastas = $pastaModel->findByDepartamentoAndParent((int) $departamentoPastas, null);
        }

        $this->view('documentos/busca', [
            'resultados' => $resultados,
            'filtros' => $_GET,
            'departamentos' => $departamentos,
            'page' => $page,
            'hasMore' => $resultados && count($resultados) === $limit,
            'pastas' => $pastas
        ]);
    }

    public function auditoria(): void
    {
        if (!Auth::check()) {
            $this->redirect('/login');
        }

        $integrity = new \App\Services\AuditIntegrityService();
        $userTenantId = Auth::user()['tenant_id'] ?? null;
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

        (new RateLimiterService(20, 60))->check('documentos_auditoria');

        $documentoId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        if ($documentoId <= 0) {
            http_response_code(400);
            echo 'ID inválido';
            return;
        }

        $user = Auth::user();
        $documento = $this->documento->find($documentoId);
        if (!$documento) {
            http_response_code(404);
            echo 'Documento não encontrado';
            return;
        }
        
        if (!$this->permissao->canView((int) $user['id'], (int) $documento['pasta_id'], $user['perfil'])) {
            http_response_code(403);
            echo 'Sem permissão';
            return;
        }

        $historico = $this->audit->getDocumentHistory($documentoId);

        $this->view('documentos/auditoria', [
            'documento' => $documento,
            'historico' => $historico
        ]);
    }

    public function auditoriaExportar(): void
    {
        if (!Auth::check()) {
            $this->redirect('/login');
        }

        $integrity = new \App\Services\AuditIntegrityService();
        $userTenantId = Auth::user()['tenant_id'] ?? null;
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

        (new RateLimiterService(10, 60))->check('documentos_auditoria_exportar');

        $documentoId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        $formato = $_GET['formato'] ?? 'pdf';
        
        if ($documentoId <= 0) die('ID inválido');

        $user = Auth::user();
        $documento = $this->documento->find($documentoId);
        
        if (!$documento || !$this->permissao->canView((int) $user['id'], (int) $documento['pasta_id'], $user['perfil'])) {
            die('Sem permissão ou não encontrado');
        }

        $historico = $this->audit->getDocumentHistory($documentoId);

        if ($formato === 'csv') {
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="auditoria_' . $documentoId . '.csv"');
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Data/Hora', 'Usuario', 'Acao', 'IP', 'Detalhes']);
            foreach ($historico['logs'] as $log) {
                fputcsv($out, [
                    $log['created_at'],
                    $log['usuario_nome'] ?? 'Sistema',
                    $log['acao'],
                    $log['ip'],
                    $log['entidade'] . ':' . $log['entidade_id']
                ]);
            }
            fclose($out);
            exit;
        } else {
            // PDF Implementation using TCPDF
            if (!class_exists(\TCPDF::class)) {
                die('Biblioteca TCPDF não instalada. Execute composer install.');
            }
            
            $pdf = new \TCPDF();
            $pdf->SetCreator('GED Institucional');
            $pdf->SetAuthor('Sistema GED');
            $pdf->SetTitle('Relatório de Auditoria - Doc #' . $documentoId);
            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);
            $pdf->AddPage();
            
            $html = '<h1>Relatório de Auditoria</h1>';
            $html .= '<p><strong>Documento:</strong> ' . htmlspecialchars($documento['titulo']) . '</p>';
            $html .= '<p><strong>ID:</strong> ' . $documento['id'] . '</p>';
            $html .= '<p><strong>Gerado em:</strong> ' . date('d/m/Y H:i:s') . '</p>';
            $html .= '<hr>';
            $html .= '<h3>Histórico de Eventos</h3>';
            $html .= '<table border="1" cellpadding="5">';
            $html .= '<tr style="background-color:#eee;"><th>Data</th><th>Usuário</th><th>Ação</th><th>IP</th></tr>';
            
            foreach ($historico['logs'] as $log) {
                $html .= '<tr>';
                $html .= '<td>' . date('d/m/Y H:i', strtotime($log['created_at'])) . '</td>';
                $html .= '<td>' . htmlspecialchars($log['usuario_nome'] ?? 'Sistema') . '</td>';
                $html .= '<td>' . htmlspecialchars($log['acao']) . '</td>';
                $html .= '<td>' . htmlspecialchars($log['ip'] ?? '-') . '</td>';
                $html .= '</tr>';
            }
            $html .= '</table>';
            
            $pdf->writeHTML($html, true, false, true, false, '');
            $pdf->Output('auditoria_' . $documentoId . '.pdf', 'D');
            exit;
        }
    }
}
