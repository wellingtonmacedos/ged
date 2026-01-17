<?php
use App\Core\Security;
?>
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h4 mb-0">Backups Institucionais</h1>
    </div>

    <div class="row">
        <div class="col-md-4 mb-4">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h2 class="h6 mb-0">Executar Backup Manual</h2>
                </div>
                <div class="card-body">
                    <form method="post" action="/sistema/backups/executar">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(Security::csrfToken(), ENT_QUOTES, 'UTF-8'); ?>">

                        <div class="mb-3">
                            <label for="escopo" class="form-label">Escopo do Backup</label>
                            <select id="escopo" name="escopo" class="form-select">
                                <option value="COMPLETO">Completo (Banco + Arquivos)</option>
                                <option value="BANCO">Apenas Banco de Dados</option>
                                <option value="ARQUIVOS">Apenas Arquivos</option>
                            </select>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">
                            Executar Backup
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-8 mb-4">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h2 class="h6 mb-0">Histórico de Backups</h2>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover mb-0 align-middle">
                            <thead>
                                <tr>
                                    <th>Data</th>
                                    <th>Tipo</th>
                                    <th>Escopo</th>
                                    <th>Status</th>
                                    <th>Tamanho</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($backups)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-4 text-muted">
                                            Nenhum backup registrado até o momento.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($backups as $b): ?>
                                        <tr>
                                            <td>
                                                <?php echo htmlspecialchars($b['iniciado_em'], ENT_QUOTES, 'UTF-8'); ?>
                                            </td>
                                            <td>
                                                <?php echo htmlspecialchars($b['tipo'], ENT_QUOTES, 'UTF-8'); ?>
                                            </td>
                                            <td>
                                                <?php echo htmlspecialchars($b['escopo'], ENT_QUOTES, 'UTF-8'); ?>
                                            </td>
                                            <td>
                                                <?php if ($b['status'] === 'SUCESSO'): ?>
                                                    <span class="badge bg-success">Sucesso</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger">Falha</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php echo $b['tamanho_mb'] !== null ? htmlspecialchars((string) $b['tamanho_mb'], ENT_QUOTES, 'UTF-8') . ' MB' : '-'; ?>
                                            </td>
                                            <td>
                                                <?php if ($b['status'] === 'SUCESSO' && !empty($b['caminho_arquivo'])): ?>
                                                    <form method="post" action="/sistema/backups/download" class="d-inline">
                                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(Security::csrfToken(), ENT_QUOTES, 'UTF-8'); ?>">
                                                        <input type="hidden" name="id" value="<?php echo (int) $b['id']; ?>">
                                                        <button type="submit" class="btn btn-sm btn-outline-secondary">
                                                            Download
                                                        </button>
                                                    </form>
                                                <?php else: ?>
                                                    -
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

