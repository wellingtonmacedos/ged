<?php
use App\Core\Security;

$ultimoBackup = null;
$totalSucesso = 0;
$totalFalha = 0;

if (!empty($backups)) {
    $ultimoBackup = $backups[0];
    foreach ($backups as $b) {
        if ($b['status'] === 'SUCESSO') {
            $totalSucesso++;
        } elseif ($b['status'] === 'FALHA') {
            $totalFalha++;
        }
    }
}
?>
<div class="container-fluid py-4 backup-dashboard">
    <div class="row align-items-center mb-4">
        <div class="col-md-8">
            <p class="text-white-50 mb-1">Olá, administrador</p>
            <h1 class="h4 mb-1 text-white">Painel de Backups</h1>
            <p class="text-white-50 mb-0">Gerencie os backups institucionais do GED com segurança.</p>
        </div>
        <div class="col-md-4 text-md-end mt-3 mt-md-0">
            <?php if ($ultimoBackup): ?>
                <div class="small text-white-50">Último backup</div>
                <div class="fw-semibold text-white">
                    <?php echo date('d/m/Y H:i', strtotime($ultimoBackup['iniciado_em'])); ?>
                </div>
                <div>
                    <?php if ($ultimoBackup['status'] === 'SUCESSO'): ?>
                        <span class="badge rounded-pill bg-success-subtle text-success-emphasis">Sucesso</span>
                    <?php else: ?>
                        <span class="badge rounded-pill bg-danger-subtle text-danger-emphasis">Falha</span>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="small text-white-50">Nenhum backup registrado ainda.</div>
            <?php endif; ?>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm backup-card-primary h-100">
                <div class="card-body d-flex flex-column">
                    <div class="d-flex align-items-center mb-3">
                        <div class="backup-avatar me-3">
                            <span class="backup-avatar-initial">B</span>
                        </div>
                        <div>
                            <div class="small text-white-50">Ação rápida</div>
                            <div class="fw-semibold text-white">Executar backup</div>
                        </div>
                    </div>

                    <form method="post" action="/sistema/backups/executar" class="mt-2">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(Security::csrfToken(), ENT_QUOTES, 'UTF-8'); ?>">

                        <div class="mb-3">
                            <label for="escopo" class="form-label text-white-50">Escopo do Backup</label>
                            <select id="escopo" name="escopo" class="form-select form-select-sm backup-select">
                                <option value="COMPLETO">Completo (Banco + Arquivos)</option>
                                <option value="BANCO">Apenas Banco de Dados</option>
                                <option value="ARQUIVOS">Apenas Arquivos</option>
                            </select>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 backup-run-btn">
                            Executar backup agora
                        </button>
                    </form>

                    <div class="mt-3 small text-white-50">
                        Os arquivos são salvos no diretório configurado em <code>BACKUP_PATH</code> e seguem a política de retenção definida.
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm backup-metric-card">
                        <div class="card-body">
                            <div class="small text-white-50 mb-1">Backups bem-sucedidos</div>
                            <div class="h4 mb-0 text-white"><?php echo $totalSucesso; ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm backup-metric-card">
                        <div class="card-body">
                            <div class="small text-white-50 mb-1">Backups com falha</div>
                            <div class="h4 mb-0 text-white"><?php echo $totalFalha; ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm backup-metric-card">
                        <div class="card-body">
                            <div class="small text-white-50 mb-1">Total registrado</div>
                            <div class="h4 mb-0 text-white"><?php echo count($backups); ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm backup-card-table">
                <div class="card-header border-0 bg-transparent d-flex justify-content-between align-items-center">
                    <div>
                        <h2 class="h6 mb-0 text-white">Histórico de Backups</h2>
                        <div class="small text-white-50">Últimos 100 registros armazenados na base de dados.</div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-dark table-hover table-sm mb-0 align-middle backup-table">
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
                                        <td colspan="6" class="text-center py-4 text-white-50">
                                            Nenhum backup registrado até o momento.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($backups as $b): ?>
                                        <tr>
                                            <td>
                                                <?php echo date('d/m/Y H:i', strtotime($b['iniciado_em'])); ?>
                                            </td>
                                            <td>
                                                <span class="badge rounded-pill bg-secondary-subtle text-secondary-emphasis">
                                                    <?php echo htmlspecialchars($b['tipo'], ENT_QUOTES, 'UTF-8'); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge rounded-pill bg-primary-subtle text-primary-emphasis">
                                                    <?php echo htmlspecialchars($b['escopo'], ENT_QUOTES, 'UTF-8'); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($b['status'] === 'SUCESSO'): ?>
                                                    <span class="badge rounded-pill bg-success-subtle text-success-emphasis">Sucesso</span>
                                                <?php else: ?>
                                                    <span class="badge rounded-pill bg-danger-subtle text-danger-emphasis">Falha</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php echo $b['tamanho_mb'] !== null ? htmlspecialchars(number_format((float) $b['tamanho_mb'], 2, ',', '.'), ENT_QUOTES, 'UTF-8') . ' MB' : '-'; ?>
                                            </td>
                                            <td>
                                                <?php if ($b['status'] === 'SUCESSO' && !empty($b['caminho_arquivo'])): ?>
                                                    <form method="post" action="/sistema/backups/download" class="d-inline">
                                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(Security::csrfToken(), ENT_QUOTES, 'UTF-8'); ?>">
                                                        <input type="hidden" name="id" value="<?php echo (int) $b['id']; ?>">
                                                        <button type="submit" class="btn btn-sm btn-outline-light">
                                                            Download
                                                        </button>
                                                    </form>
                                                <?php else: ?>
                                                    <span class="text-white-50">-</span>
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
