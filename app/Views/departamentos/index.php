<?php
$title = 'Gerenciar Departamentos';
ob_start();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Departamentos</h1>
    <?php if ($user['perfil'] === 'ADMIN_GERAL'): ?>
        <a href="/departamentos/novo" class="btn btn-primary">Novo Departamento</a>
    <?php endif; ?>
</div>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>ID</th>
                    <th>Nome</th>
                    <th>Descrição</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($departamentos as $dept): ?>
                    <tr>
                        <td><?php echo $dept['id']; ?></td>
                        <td><?php echo htmlspecialchars($dept['nome']); ?></td>
                        <td><?php echo htmlspecialchars($dept['descricao'] ?? ''); ?></td>
                        <td>
                            <span class="badge bg-<?php echo $dept['status'] === 'ATIVO' ? 'success' : 'secondary'; ?>">
                                <?php echo $dept['status']; ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($departamentos)): ?>
                    <tr>
                        <td colspan="4" class="text-center py-4 text-muted">Nenhum departamento cadastrado.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout/main.php';
?>
