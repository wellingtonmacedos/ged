<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Models\Departamento;

class DepartamentoController extends Controller
{
    private Departamento $departamento;

    public function __construct()
    {
        $this->departamento = new Departamento();
    }

    public function listJson(): void
    {
        if (!Auth::check()) {
            http_response_code(401);
            echo json_encode(['error' => 'Não autenticado']);
            return;
        }

        $user = Auth::user();
        
        $db = Database::connection();
        $stmt = $db->query("SELECT id, nome FROM departamentos WHERE status = 'ATIVO' ORDER BY nome ASC");
        $departamentos = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($departamentos);
    }

    public function index(): void
    {
        if (!Auth::check()) {
            $this->redirect('/login');
        }

        $user = Auth::user();
        
        $db = Database::connection();
        $stmt = $db->query("SELECT * FROM departamentos ORDER BY nome ASC");
        $departamentos = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $this->view('departamentos/index', [
            'user' => $user,
            'departamentos' => $departamentos
        ]);
    }

    public function create(): void
    {
        if (!Auth::check()) {
            $this->redirect('/login');
        }

        $user = Auth::user();
        $this->view('departamentos/create', [
            'user' => $user
        ]);
    }

    public function store(): void
    {
        if (!Auth::check()) {
            $this->redirect('/login');
        }

        $user = Auth::user();
        
        $nome = trim($_POST['nome'] ?? '');
        $descricao = trim($_POST['descricao'] ?? '');

        if ($nome === '') {
            // Error handling could be better, but keeping it simple
            echo "Nome é obrigatório.";
            return;
        }

        $db = Database::connection();
        $stmt = $db->prepare("INSERT INTO departamentos (nome, descricao) VALUES (:nome, :descricao)");
        $stmt->execute([':nome' => $nome, ':descricao' => $descricao]);

        $this->redirect('/departamentos');
    }
}
