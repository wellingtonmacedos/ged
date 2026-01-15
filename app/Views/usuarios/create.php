<?php
use App\Core\Security;

$token = Security::csrfToken();
$title = 'Novo Usuário';
ob_start();
?>

<div class="mb-4">
    <a href="/" class="text-decoration-none">&larr; Voltar</a>
</div>

<h1>Novo Usuário</h1>

<div class="card shadow-sm mt-4" style="max-width: 700px;">
    <div class="card-body">
        <form action="/usuarios/salvar" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($token, ENT_QUOTES, 'UTF-8'); ?>">

            <div class="mb-3">
                <label for="nome" class="form-label">Nome *</label>
                <input type="text" class="form-control" id="nome" name="nome" required autofocus>
            </div>

            <div class="mb-3">
                <label for="email" class="form-label">E-mail *</label>
                <input type="email" class="form-control" id="email" name="email" required>
            </div>

            <div class="mb-3">
                <label for="senha" class="form-label">Senha *</label>
                <input type="password" class="form-control" id="senha" name="senha" required>
            </div>

            <div class="mb-3">
                <label for="perfil" class="form-label">Perfil *</label>
                <select class="form-select" id="perfil" name="perfil" required>
                    <option value="">Selecione</option>
                    <option value="ADMIN_GERAL">Admin Geral</option>
                    <option value="ADMIN_DEPARTAMENTO">Admin de Departamento</option>
                    <option value="SUPER_ADMIN">Super Admin</option>
                </select>
            </div>

            <div class="mb-3">
                <label for="departamento_id" class="form-label">Departamento</label>
                <select class="form-select" id="departamento_id" name="departamento_id">
                    <option value="">Selecione</option>
                    <?php foreach ($departamentos as $dept): ?>
                        <option value="<?php echo (int) $dept['id']; ?>">
                            <?php echo htmlspecialchars($dept['nome'], ENT_QUOTES, 'UTF-8'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-3">
                <label for="status" class="form-label">Status</label>
                <select class="form-select" id="status" name="status">
                    <option value="ATIVO" selected>Ativo</option>
                    <option value="INATIVO">Inativo</option>
                </select>
            </div>

            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary">Salvar Usuário</button>
            </div>
        </form>
    </div>
    </div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout/main.php';
?>

