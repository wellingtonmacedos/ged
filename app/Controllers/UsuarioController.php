<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Security;
use App\Models\User;
use App\Models\Departamento;

class UsuarioController extends Controller
{
    public function index(): void
    {
        if (!Auth::check()) {
            $this->redirect('/login');
        }

        $user = Auth::user();
        if (!isset($user['perfil']) || $user['perfil'] !== 'ADMIN_GERAL') {
            http_response_code(403);
            echo 'Acesso restrito';
            return;
        }

        $userModel = new User();
        $usuarios = $userModel->all();

        $departamentoModel = new Departamento();
        $departamentos = $departamentoModel->all();
        $deptMap = [];
        foreach ($departamentos as $d) {
            $deptMap[$d['id']] = $d['nome'];
        }

        $this->view('usuarios/index', [
            'user' => $user,
            'usuarios' => $usuarios,
            'deptMap' => $deptMap
        ]);
    }

    public function create(): void
    {
        if (!Auth::check()) {
            $this->redirect('/login');
        }

        $user = Auth::user();
        if (!isset($user['perfil']) || $user['perfil'] !== 'ADMIN_GERAL') {
            http_response_code(403);
            echo 'Acesso restrito';
            return;
        }

        $departamentoModel = new Departamento();
        $departamentos = $departamentoModel->all();

        $this->view('usuarios/create', [
            'user' => $user,
            'departamentos' => $departamentos,
        ]);
    }

    public function store(): void
    {
        if (!Auth::check()) {
            $this->redirect('/login');
        }

        Security::requireCsrfToken();

        $currentUser = Auth::user();
        if (!isset($currentUser['perfil']) || $currentUser['perfil'] !== 'ADMIN_GERAL') {
            http_response_code(403);
            echo 'Acesso restrito';
            return;
        }

        $nome = trim((string) ($_POST['nome'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $senha = (string) ($_POST['senha'] ?? '');
        $perfil = trim((string) ($_POST['perfil'] ?? ''));
        $status = trim((string) ($_POST['status'] ?? 'ATIVO'));
        $departamentoId = isset($_POST['departamento_id']) && $_POST['departamento_id'] !== ''
            ? (int) $_POST['departamento_id']
            : null;

        if ($nome === '') {
            echo 'Nome é obrigatório.';
            return;
        }

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo 'E-mail inválido.';
            return;
        }

        if ($senha === '' || strlen($senha) < 6) {
            echo 'Senha deve ter pelo menos 6 caracteres.';
            return;
        }

        if ($perfil === '') {
            echo 'Perfil é obrigatório.';
            return;
        }

        $userModel = new User();
        if ($userModel->findByEmail($email) !== null) {
            echo 'E-mail já cadastrado.';
            return;
        }

        $db = Database::connection();
        $colsDept = $db->query("SHOW COLUMNS FROM users LIKE 'departamento_id'")->fetchAll();
        $hasDepartamento = count($colsDept) > 0;

        $colsTenant = $db->query("SHOW COLUMNS FROM users LIKE 'tenant_id'")->fetchAll();
        $hasTenant = count($colsTenant) > 0;

        $senhaHash = password_hash($senha, PASSWORD_BCRYPT);

        $data = [
            'nome' => $nome,
            'email' => $email,
            'senha_hash' => $senhaHash,
            'perfil' => $perfil,
            'status' => $status !== '' ? $status : 'ATIVO',
        ];

        if ($hasTenant && isset($currentUser['tenant_id'])) {
            $data['tenant_id'] = (int) $currentUser['tenant_id'];
        }

        if ($hasDepartamento && $departamentoId !== null) {
            $data['departamento_id'] = $departamentoId;
        }

        $userModel->insert($data);

        $this->redirect('/usuarios');
    }
}

