<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3">Auditoria do Documento</h1>
        <p class="text-muted">Documento: <?php echo htmlspecialchars($documento['titulo'], ENT_QUOTES, 'UTF-8'); ?> (ID: <?php echo $documento['id']; ?>)</p>
    </div>
    <div>
        <div class="btn-group">
            <button type="button" class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                Exportar
            </button>
            <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="/documentos/auditoria/exportar?id=<?php echo $documento['id']; ?>&formato=pdf">PDF</a></li>
                <li><a class="dropdown-item" href="/documentos/auditoria/exportar?id=<?php echo $documento['id']; ?>&formato=csv">CSV</a></li>
            </ul>
        </div>
        <a href="/documentos?pasta_id=<?php echo $documento['pasta_id']; ?>" class="btn btn-secondary ms-2">Voltar</a>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-light">
                <h5 class="card-title mb-0">Linha do Tempo de Eventos</h5>
            </div>
            <div class="card-body">
                <div class="timeline">
                    <?php foreach ($historico['logs'] as $log): ?>
                        <div class="timeline-item pb-4 border-start ps-4 position-relative">
                            <div class="position-absolute top-0 start-0 translate-middle rounded-circle bg-primary" style="width: 12px; height: 12px;"></div>
                            <div class="small text-muted mb-1">
                                <?php echo date('d/m/Y H:i:s', strtotime($log['created_at'])); ?>
                            </div>
                            <h6 class="mb-1"><?php echo htmlspecialchars($log['acao'], ENT_QUOTES, 'UTF-8'); ?></h6>
                            <p class="mb-1 small">
                                <strong>Usuário:</strong> <?php echo htmlspecialchars($log['usuario_nome'] ?? 'Sistema', ENT_QUOTES, 'UTF-8'); ?>
                            </p>
                            <p class="mb-0 small text-muted">
                                IP: <?php echo htmlspecialchars($log['ip'] ?? '-', ENT_QUOTES, 'UTF-8'); ?>
                            </p>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-light">
                <h5 class="card-title mb-0">Versões do Arquivo</h5>
            </div>
            <ul class="list-group list-group-flush">
                <?php foreach ($historico['versoes'] as $versao): ?>
                    <li class="list-group-item">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <strong>Versão <?php echo $versao['versao']; ?></strong>
                                <div class="small text-muted text-truncate" style="max-width: 200px;" title="<?php echo $versao['hash_sha256']; ?>">
                                    Hash: <?php echo substr($versao['hash_sha256'], 0, 10); ?>...
                                </div>
                            </div>
                            <span class="badge bg-secondary"><?php echo date('d/m/Y', strtotime($versao['created_at'])); ?></span>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <div class="card shadow-sm">
            <div class="card-header bg-light">
                <h5 class="card-title mb-0">Assinaturas</h5>
            </div>
            <ul class="list-group list-group-flush">
                <?php if (empty($historico['assinaturas'])): ?>
                    <li class="list-group-item text-muted small">Nenhuma assinatura registrada.</li>
                <?php else: ?>
                    <?php foreach ($historico['assinaturas'] as $assinatura): ?>
                        <li class="list-group-item">
                            <div class="mb-1">
                                <strong><?php echo htmlspecialchars($assinatura['usuario_nome'], ENT_QUOTES, 'UTF-8'); ?></strong>
                            </div>
                            <div class="small text-muted">
                                <?php echo date('d/m/Y H:i', strtotime($assinatura['created_at'])); ?>
                            </div>
                            <div class="small text-break mt-1 font-monospace bg-light p-1 rounded">
                                <?php echo substr($assinatura['hash_assinatura'], 0, 20); ?>...
                            </div>
                        </li>
                    <?php endforeach; ?>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</div>
