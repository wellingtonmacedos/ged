<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Security;
use App\Models\Departamento;
use App\Models\Pasta;
use App\Models\User;
use App\Services\AuditService;
use App\Services\RateLimiterService;
use App\Services\PermissionService;
use App\Services\RelatorioService;

class RelatorioController extends Controller
{
    private RelatorioService $relatorios;
    private AuditService $audit;
    private PermissionService $permissao;
    private Departamento $departamento;
    private Pasta $pasta;
    private User $userModel;

    public function __construct()
    {
        $this->relatorios = new RelatorioService();
        $this->audit = new AuditService();
        $this->permissao = new PermissionService();
        $this->departamento = new Departamento();
        $this->pasta = new Pasta();
        $this->userModel = new User();
    }

    private function aplicarEscopoUsuario(array $filtros, array $user): array
    {
        $perfil = $user['perfil'] ?? null;
        if ($perfil === 'ADMIN_GERAL' || $perfil === 'SUPER_ADMIN') {
            return $filtros;
        }

        $db = \App\Core\Database::connection();
        $stmt = $db->prepare('SELECT DISTINCT p.departamento_id FROM permissoes perm INNER JOIN pastas p ON p.id = perm.pasta_id WHERE perm.usuario_id = :usuario_id AND perm.pode_ver = 1');
        $stmt->bindValue(':usuario_id', (int) $user['id']);
        $stmt->execute();
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        if (empty($rows)) {
            $filtros['departamento_id'] = 0;
            return $filtros;
        }

        if (empty($filtros['departamento_id'])) {
            $filtros['departamento_id'] = (int) $rows[0]['departamento_id'];
        }

        return $filtros;
    }

    public function index(): void
    {
        if (!Auth::check()) {
            $this->redirect('/login');
        }

        $user = Auth::user();
        $departamentos = $this->departamento->all();
        $usuarios = $this->userModel->all();

        $this->view('relatorios/index', [
            'user' => $user,
            'departamentos' => $departamentos,
            'usuarios' => $usuarios,
        ]);
    }

    public function gerar(): void
    {
        if (!Auth::check()) {
            $this->redirect('/login');
        }

        Security::requireCsrfToken();

        (new RateLimiterService(10, 60))->check('relatorios_gerar');

        $user = Auth::user();

        $tipo = $_POST['tipo'] ?? '';
        $filtros = $_POST['filtros'] ?? [];
        $filtros = $this->aplicarEscopoUsuario($filtros, $user);

        $dados = [];

        if ($tipo === 'documentos') {
            $dados = $this->relatorios->documentos($filtros);
        } elseif ($tipo === 'assinaturas') {
            $dados = $this->relatorios->assinaturas($filtros);
        } elseif ($tipo === 'auditoria') {
            $dados = $this->relatorios->auditoria($filtros);
        } elseif ($tipo === 'ocr') {
            $dados = $this->relatorios->ocr($filtros);
        }

        $this->audit->logOperacional(
            'GERAR_RELATORIO',
            'relatorios',
            null,
            json_encode([
                'tipo' => $tipo,
                'filtros' => $filtros,
                'usuario_id' => $user['id'] ?? null,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );

        $departamentos = $this->departamento->all();
        $usuarios = $this->userModel->all();

        $this->view('relatorios/index', [
            'user' => $user,
            'departamentos' => $departamentos,
            'usuarios' => $usuarios,
            'tipo' => $tipo,
            'filtros' => $filtros,
            'dados' => $dados,
        ]);
    }

    public function exportar(): void
    {
        if (!Auth::check()) {
            $this->redirect('/login');
        }

        (new RateLimiterService(5, 60))->check('relatorios_exportar');

        $user = Auth::user();

        $tipo = $_GET['tipo'] ?? '';
        $formato = $_GET['formato'] ?? 'pdf';

        $filtros = [
            'departamento_id' => isset($_GET['departamento_id']) ? (int) $_GET['departamento_id'] : null,
            'pasta_id' => isset($_GET['pasta_id']) ? (int) $_GET['pasta_id'] : null,
            'status' => $_GET['status'] ?? null,
            'usuario_id' => isset($_GET['usuario_id']) ? (int) $_GET['usuario_id'] : null,
            'acao' => $_GET['acao'] ?? null,
            'entidade' => $_GET['entidade'] ?? null,
            'inicio' => $_GET['inicio'] ?? null,
            'fim' => $_GET['fim'] ?? null,
        ];

        $filtros = $this->aplicarEscopoUsuario($filtros, $user);

        if ($tipo === 'documentos') {
            $dados = $this->relatorios->documentos($filtros);
        } elseif ($tipo === 'assinaturas') {
            $dados = $this->relatorios->assinaturas($filtros);
        } elseif ($tipo === 'auditoria') {
            $dados = $this->relatorios->auditoria($filtros);
        } elseif ($tipo === 'ocr') {
            $dados = $this->relatorios->ocr($filtros);
        } else {
            http_response_code(400);
            echo 'Tipo inválido';
            return;
        }

        $this->audit->logOperacional(
            'EXPORTAR_RELATORIO',
            'relatorios',
            null,
            json_encode([
                'tipo' => $tipo,
                'filtros' => $filtros,
                'usuario_id' => $user['id'] ?? null,
                'formato' => $formato,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );

        if ($formato === 'csv') {
            $this->exportarCsv($tipo, $dados);
        } else {
            $this->exportarPdf($tipo, $dados);
        }
    }

    private function exportarCsv(string $tipo, array $dados): void
    {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="relatorio_' . $tipo . '_' . date('Ymd_His') . '.csv"');
        $out = fopen('php://output', 'w');

        if ($tipo === 'documentos') {
            fputcsv($out, ['ID', 'Título', 'Departamento', 'Pasta', 'Status', 'Criado em']);
            foreach ($dados['linhas'] as $row) {
                fputcsv($out, [
                    $row['id'],
                    $row['titulo'],
                    $row['departamento_nome'],
                    $row['pasta_nome'],
                    $row['status'],
                    $row['created_at'],
                ]);
            }
        } elseif ($tipo === 'assinaturas') {
            fputcsv($out, ['ID', 'Documento', 'Usuário', 'Departamento', 'Status', 'Assinado em']);
            foreach ($dados['linhas'] as $row) {
                fputcsv($out, [
                    $row['id'],
                    $row['titulo'],
                    $row['usuario_nome'],
                    $row['departamento_nome'],
                    $row['status'],
                    $row['assinado_em'],
                ]);
            }
        } elseif ($tipo === 'auditoria') {
            fputcsv($out, ['Data/Hora', 'Usuário ID', 'Ação', 'Entidade', 'Entidade ID', 'IP']);
            foreach ($dados['linhas'] as $row) {
                fputcsv($out, [
                    $row['created_at'],
                    $row['usuario_id'],
                    $row['acao'],
                    $row['entidade'],
                    $row['entidade_id'],
                    $row['ip'],
                ]);
            }
        } elseif ($tipo === 'ocr') {
            fputcsv($out, ['ID', 'Documento', 'Departamento', 'Páginas', 'Engine', 'Criado em']);
            foreach ($dados['linhas'] as $row) {
                fputcsv($out, [
                    $row['id'],
                    $row['titulo'],
                    $row['departamento_nome'],
                    $row['paginas_processadas'],
                    $row['engine'],
                    $row['created_at'],
                ]);
            }
        }

        fclose($out);
        exit;
    }

    private function exportarPdf(string $tipo, array $dados): void
    {
        if (!class_exists(\TCPDF::class)) {
            http_response_code(500);
            echo 'Biblioteca TCPDF não instalada. Execute composer install.';
            return;
        }

        $pdf = new \TCPDF();
        $pdf->SetCreator('GED Institucional');
        $pdf->SetAuthor('Sistema GED');
        $pdf->SetTitle('Relatório ' . $tipo);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->AddPage();

        $html = '<h1>Relatório ' . htmlspecialchars(strtoupper($tipo), ENT_QUOTES, 'UTF-8') . '</h1>';
        $html .= '<p>Gerado em: ' . date('d/m/Y H:i:s') . '</p>';
        $html .= '<hr>';

        if ($tipo === 'documentos') {
            $html .= '<p>Total de documentos: ' . (int) $dados['total'] . '</p>';
            $html .= '<table border="1" cellpadding="4"><tr><th>ID</th><th>Título</th><th>Departamento</th><th>Pasta</th><th>Status</th><th>Criado em</th></tr>';
            foreach ($dados['linhas'] as $row) {
                $html .= '<tr><td>' . $row['id'] . '</td><td>' . htmlspecialchars($row['titulo']) . '</td><td>' . htmlspecialchars($row['departamento_nome']) . '</td><td>' . htmlspecialchars($row['pasta_nome']) . '</td><td>' . htmlspecialchars($row['status']) . '</td><td>' . $row['created_at'] . '</td></tr>';
            }
            $html .= '</table>';
        } elseif ($tipo === 'assinaturas') {
            $html .= '<p>Total de assinaturas: ' . (int) $dados['total'] . '</p>';
            $html .= '<table border="1" cellpadding="4"><tr><th>ID</th><th>Documento</th><th>Usuário</th><th>Departamento</th><th>Status</th><th>Assinado em</th></tr>';
            foreach ($dados['linhas'] as $row) {
                $html .= '<tr><td>' . $row['id'] . '</td><td>' . htmlspecialchars($row['titulo']) . '</td><td>' . htmlspecialchars($row['usuario_nome']) . '</td><td>' . htmlspecialchars($row['departamento_nome']) . '</td><td>' . htmlspecialchars($row['status']) . '</td><td>' . $row['assinado_em'] . '</td></tr>';
            }
            $html .= '</table>';
        } elseif ($tipo === 'auditoria') {
            $html .= '<p>Total de eventos: ' . (int) $dados['total'] . '</p>';
            $html .= '<table border="1" cellpadding="4"><tr><th>Data/Hora</th><th>Usuário ID</th><th>Ação</th><th>Entidade</th><th>Entidade ID</th><th>IP</th></tr>';
            foreach ($dados['linhas'] as $row) {
                $html .= '<tr><td>' . $row['created_at'] . '</td><td>' . $row['usuario_id'] . '</td><td>' . htmlspecialchars($row['acao']) . '</td><td>' . htmlspecialchars($row['entidade']) . '</td><td>' . $row['entidade_id'] . '</td><td>' . htmlspecialchars($row['ip']) . '</td></tr>';
            }
            $html .= '</table>';
        } elseif ($tipo === 'ocr') {
            $html .= '<p>Total de registros OCR: ' . (int) $dados['total'] . '</p>';
            $html .= '<p>Total de páginas processadas: ' . (int) $dados['paginas'] . '</p>';
            $html .= '<table border="1" cellpadding="4"><tr><th>ID</th><th>Documento</th><th>Departamento</th><th>Páginas</th><th>Engine</th><th>Criado em</th></tr>';
            foreach ($dados['linhas'] as $row) {
                $html .= '<tr><td>' . $row['id'] . '</td><td>' . htmlspecialchars($row['titulo']) . '</td><td>' . htmlspecialchars($row['departamento_nome']) . '</td><td>' . (int) $row['paginas_processadas'] . '</td><td>' . htmlspecialchars($row['engine']) . '</td><td>' . $row['created_at'] . '</td></tr>';
            }
            $html .= '</table>';
        }

        $pdf->writeHTML($html);
        $pdf->Output('relatorio_' . $tipo . '_' . date('Ymd_His') . '.pdf', 'I');
        exit;
    }
}
