<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3">Verificação de Integridade da Auditoria</h1>
        <p class="text-muted mb-0">Visão técnica para uso interno e auditoria institucional.</p>
    </div>
    <div>
        <a href="/sistema/auditoria/verificar/exportar" class="btn btn-outline-primary">Exportar Relatório PDF</a>
    </div>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-header bg-light">
        Resultado da Verificação
    </div>
    <div class="card-body">
        <dl class="row mb-0">
            <dt class="col-sm-3">Status da Cadeia</dt>
            <dd class="col-sm-9">
                <?php if ($resultado['status'] === 'OK'): ?>
                    <span class="badge bg-success">ÍNTEGRA</span>
                <?php else: ?>
                    <span class="badge bg-danger">CORROMPIDA</span>
                <?php endif; ?>
            </dd>

            <dt class="col-sm-3">Total de eventos verificados</dt>
            <dd class="col-sm-9"><?php echo (int) $resultado['total_eventos_verificados']; ?></dd>

            <dt class="col-sm-3">Hash inicial</dt>
            <dd class="col-sm-9"><code><?php echo htmlspecialchars($resultado['hash_inicial'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></code></dd>

            <dt class="col-sm-3">Hash final</dt>
            <dd class="col-sm-9"><code><?php echo htmlspecialchars($resultado['hash_final'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></code></dd>

            <dt class="col-sm-3">Data da verificação</dt>
            <dd class="col-sm-9"><?php echo htmlspecialchars($resultado['data_verificacao'], ENT_QUOTES, 'UTF-8'); ?></dd>
        </dl>
    </div>
</div>

<?php if ($resultado['primeiro_evento_invalido']): ?>
    <?php $inv = $resultado['primeiro_evento_invalido']; ?>
    <div class="card shadow-sm">
        <div class="card-header bg-warning">
            Detalhes do primeiro evento inválido
        </div>
        <div class="card-body">
            <dl class="row mb-0">
                <dt class="col-sm-3">ID do log</dt>
                <dd class="col-sm-9"><?php echo (int) $inv['id']; ?></dd>

                <dt class="col-sm-3">Hash anterior esperado</dt>
                <dd class="col-sm-9"><code><?php echo htmlspecialchars($inv['esperado_hash_anterior'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></code></dd>

                <dt class="col-sm-3">Hash anterior encontrado</dt>
                <dd class="col-sm-9"><code><?php echo htmlspecialchars($inv['encontrado_hash_anterior'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></code></dd>

                <dt class="col-sm-3">Hash evento esperado</dt>
                <dd class="col-sm-9"><code><?php echo htmlspecialchars($inv['esperado_hash_evento'], ENT_QUOTES, 'UTF-8'); ?></code></dd>

                <dt class="col-sm-3">Hash evento encontrado</dt>
                <dd class="col-sm-9"><code><?php echo htmlspecialchars($inv['encontrado_hash_evento'], ENT_QUOTES, 'UTF-8'); ?></code></dd>

                <dt class="col-sm-3">Ordem cronológica válida</dt>
                <dd class="col-sm-9">
                    <?php echo $inv['ordem_cronologica_valida'] ? 'Sim' : 'Não'; ?>
                </dd>
            </dl>
        </div>
    </div>
<?php else: ?>
    <div class="alert alert-success">
        Nenhuma inconsistência encontrada na cadeia de auditoria analisada.
    </div>
<?php endif; ?>

