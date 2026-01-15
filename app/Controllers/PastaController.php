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

        $this->redirect('/documentos');
    }

    public function children(): void
    {
        if (!Auth::check()) {
            http_response_code(401);
            echo json_encode(['error' => 'não autenticado']);
            return;
        }

        $user = Auth::user();
        
        // Allow overriding department_id if provided (e.g. for sidebar navigation)
        // Ideally we should check permissions here (if user can see that department)
        // For now, if it's public folders, it's fine.
        $departamentoId = isset($_GET['departamento_id']) && $_GET['departamento_id'] !== '' 
            ? (int) $_GET['departamento_id'] 
            : (int) $user['departamento_id'];

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
        
        $parentId = isset($_GET['parent_id']) ? (int) $_GET['parent_id'] : null;
        $parentFolder = null;

        if ($parentId) {
             $parentFolder = $this->pasta->find($parentId);
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

