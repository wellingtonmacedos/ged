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

    public function index(): void
    {
        if (!Auth::check()) {
            $this->redirect('/login');
        }

        $user = Auth::user();
        if (!in_array($user['perfil'], ['ADMIN_GERAL', 'ADMIN_DEPARTAMENTO'])) {
            http_response_code(403);
            echo "Acesso negado.";
            return;
        }

        // Assuming findAll exists in Model or I use Database directly if not
        // Let's check Model.php or assume basic PDO usage if needed.
        // For now, I'll use direct DB query or Model method if I knew it.
        // I'll stick to basic Model usage pattern seen in other controllers.
        
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
        if ($user['perfil'] !== 'ADMIN_GERAL') {
            http_response_code(403);
            echo "Apenas Admin Geral pode criar departamentos.";
            return;
        }

        $this->view('departamentos/create', ['user' => $user]);
    }

    public function store(): void
    {
        if (!Auth::check()) {
            $this->redirect('/login');
        }

        $user = Auth::user();
        if ($user['perfil'] !== 'ADMIN_GERAL') {
            http_response_code(403);
            echo "Acesso negado.";
            return;
        }

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
