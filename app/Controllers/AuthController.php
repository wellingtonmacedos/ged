<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Security;

class AuthController extends Controller
{
    public function showLogin(): void
    {
        if (Auth::check()) {
            $this->redirect('/');
        }

        $this->view('auth/login');
    }

    public function login(): void
    {
        Security::requireCsrfToken();

        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        if ($email === '' || $password === '') {
            $this->view('auth/login', ['error' => 'Informe e-mail e senha']);
            return;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->view('auth/login', ['error' => 'E-mail inválido']);
            return;
        }

        if (!Auth::attempt($email, $password)) {
            $this->view('auth/login', ['error' => 'Credenciais inválidas ou usuário inativo']);
            return;
        }

        $this->redirect('/');
    }

    public function logout(): void
    {
        Auth::logout();
        $this->redirect('/login');
    }
}

