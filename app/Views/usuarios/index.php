<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Gerenciar Usuários</h1>
    <a href="/usuarios/novo" class="btn btn-primary">
        <i class="bi bi-person-plus"></i> Novo Usuário
    </a>
</div>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Nome</th>
                        <th>E-mail</th>
                        <th>Perfil</th>
                        <th>Departamento</th>
                        <th>Status</th>
                        <th class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($usuarios as $u): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="avatar-circle me-2 bg-secondary text-white small d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; border-radius: 50%;">
                                        <?php echo strtoupper(substr($u['nome'], 0, 1)); ?>
                                    </div>
                                    <?php echo htmlspecialchars($u['nome']); ?>
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars($u['email']); ?></td>
                            <td>
                                <span class="badge bg-info text-dark">
                                    <?php echo htmlspecialchars(str_replace('_', ' ', $u['perfil'] ?? '')); ?>
                                </span>
                            </td>
                            <td>
                                <?php 
                                    if (!empty($u['departamento_id']) && isset($deptMap[$u['departamento_id']])) {
                                        echo htmlspecialchars($deptMap[$u['departamento_id']]);
                                    } else {
                                        echo '<span class="text-muted">-</span>';
                                    }
                                ?>
                            </td>
                            <td>
                                <?php if (($u['status'] ?? 'ATIVO') === 'ATIVO'): ?>
                                    <span class="badge bg-success">Ativo</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Inativo</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-outline-secondary" title="Editar (Em breve)">
                                    Editar
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($usuarios)): ?>
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                Nenhum usuário encontrado.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
