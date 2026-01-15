<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Models\Pasta;
use App\Services\PermissionService;

class PastaController extends Controller
{
    private Pasta $pasta;
    private PermissionService $permissao;

    public function __construct()
    {
        $this->pasta = new Pasta();
        $this->permissao = new PermissionService();
    }

    public function index(): void
    {
        if (!Auth::check()) {
            $this->redirect('/login');
        }

        $user = Auth::user();

        $this->view('pastas/index', ['user' => $user]);
    }

    public function children(): void
    {
        if (!Auth::check()) {
            http_response_code(401);
            echo json_encode(['error' => 'não autenticado']);
            return;
        }

        $user = Auth::user();
        $departamentoId = (int) $user['departamento_id'];
        $parentId = isset($_GET['parent_id']) && $_GET['parent_id'] !== '' ? (int) $_GET['parent_id'] : null;

        if ($parentId !== null) {
            if (!$this->permissao->canView((int) $user['id'], $parentId, $user['perfil'])) {
                http_response_code(403);
                echo json_encode(['error' => 'sem permissão']);
                return;
            }
        }

        $pastas = $this->pasta->findByDepartamentoAndParent($departamentoId, $parentId);

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($pastas);
    }

    public function create(): void
    {
        if (!Auth::check()) {
            $this->redirect('/login');
        }

        $user = Auth::user();
        
        // Check permission (basic check, could be more granular)
        if (!in_array($user['perfil'], ['ADMIN_GERAL', 'ADMIN_DEPARTAMENTO', 'USUARIO'])) {
            http_response_code(403);
            echo "Acesso negado.";
            return;
        }

        $parentId = isset($_GET['parent_id']) ? (int) $_GET['parent_id'] : null;
        $parentFolder = null;

        if ($parentId) {
             $parentFolder = $this->pasta->find($parentId);
             // Verify permission to create inside this folder (usually same as upload/write)
             if (!$this->permissao->canUpload((int) $user['id'], $parentId, $user['perfil'])) {
                 http_response_code(403);
                 echo "Sem permissão para criar pasta neste local.";
                 return;
             }
        }

        $this->view('pastas/create', [
            'user' => $user,
            'parentId' => $parentId,
            'parentFolder' => $parentFolder
        ]);
    }

    public function store(): void
    {
        if (!Auth::check()) {
            $this->redirect('/login');
        }

        $user = Auth::user();
        
        $nome = trim($_POST['nome'] ?? '');
        $parentId = isset($_POST['parent_id']) && $_POST['parent_id'] !== '' ? (int) $_POST['parent_id'] : null;
        
        // Determine Departamento ID
        $departamentoId = (int) ($user['departamento_id'] ?? 0);
        
        // If Admin Geral, maybe allow selecting department? (Simplification: stick to user's dept or parent's dept)
        if ($parentId) {
            $parent = $this->pasta->find($parentId);
            if ($parent) {
                $departamentoId = (int) $parent['departamento_id'];
            }
        }

        if ($nome === '') {
            echo "Nome é obrigatório.";
            return;
        }

        // Permission check
        if ($parentId) {
            if (!$this->permissao->canUpload((int) $user['id'], $parentId, $user['perfil'])) {
                 http_response_code(403);
                 echo "Sem permissão para criar pasta neste local.";
                 return;
            }
        } else {
             // Creating root folder - usually only Admins
             if (!in_array($user['perfil'], ['ADMIN_GERAL', 'ADMIN_DEPARTAMENTO'])) {
                 http_response_code(403);
                 echo "Apenas Administradores podem criar pastas raiz.";
                 return;
             }
        }

        $this->pasta->insert([
            'nome' => $nome,
            'parent_id' => $parentId,
            'departamento_id' => $departamentoId,
            'nivel' => 0 // TODO: calculate level if needed
        ]);

        $redirectUrl = '/pastas';
        if ($parentId) {
            // Usually we redirect to document view of that folder or similar
            // But since we don't have a specific folder view in PastaController (it uses API),
            // maybe redirect to Documentos index with pasta_id?
            $redirectUrl = '/documentos?pasta_id=' . $parentId;
        }

        $this->redirect($redirectUrl);
    }
}

