<h2 class="h4 mb-4">Busca Avançada</h2>

<div class="card mb-4 shadow-sm">
    <div class="card-body">
        <form method="get" action="/documentos/busca" class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Termo (Título)</label>
                <input type="text" name="q" class="form-control" value="<?php echo htmlspecialchars($filtros['q'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            
            <div class="col-md-3">
                <label class="form-label">Departamento</label>
                <select name="departamento_id" class="form-select">
                    <option value="">Todos</option>
                    <?php foreach ($departamentos as $dep): ?>
                        <option value="<?php echo $dep['id']; ?>" <?php echo ($filtros['departamento_id'] == $dep['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($dep['nome'], ENT_QUOTES, 'UTF-8'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-2">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="">Todos</option>
                    <option value="EM_EDICAO" <?php echo ($filtros['status'] === 'EM_EDICAO') ? 'selected' : ''; ?>>Em Edição</option>
                    <option value="PENDENTE_ASSINATURA" <?php echo ($filtros['status'] === 'PENDENTE_ASSINATURA') ? 'selected' : ''; ?>>Pendente</option>
                    <option value="ASSINADO" <?php echo ($filtros['status'] === 'ASSINADO') ? 'selected' : ''; ?>>Assinado</option>
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label">Período (Criação)</label>
                <div class="input-group">
                    <input type="date" name="data_inicio" class="form-control" value="<?php echo $filtros['data_inicio'] ?? ''; ?>">
                    <span class="input-group-text">até</span>
                    <input type="date" name="data_fim" class="form-control" value="<?php echo $filtros['data_fim'] ?? ''; ?>">
                </div>
            </div>

            <div class="col-md-12">
                <label class="form-label">Metadados (Opcional)</label>
                <div class="input-group">
                    <input type="text" name="meta_chave" class="form-control" placeholder="Chave (ex: contrato_id)" value="<?php echo htmlspecialchars($filtros['meta_chave'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="text" name="meta_valor" class="form-control" placeholder="Valor" value="<?php echo htmlspecialchars($filtros['meta_valor'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                </div>
            </div>

            <div class="col-12 text-end">
                <a href="/documentos/busca" class="btn btn-outline-secondary me-2">Limpar</a>
                <button type="submit" class="btn btn-primary">Buscar</button>
            </div>
        </form>
    </div>
</div>

<?php if (isset($resultados) && count($resultados) > 0): ?>
    <div class="card shadow-sm">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
            <span>Resultados Encontrados</span>
            <small class="text-muted">Página <?php echo $page; ?></small>
        </div>
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Título</th>
                    <th>Status</th>
                    <th>Criado em</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($resultados as $doc): ?>
                    <tr>
                        <td>
                            <a href="/documentos?pasta_id=<?php echo $doc['pasta_id']; ?>" class="text-decoration-none">
                                <?php echo htmlspecialchars($doc['titulo'], ENT_QUOTES, 'UTF-8'); ?>
                            </a>
                        </td>
                        <td>
                            <span class="badge bg-<?php echo match($doc['status']) { 'ASSINADO' => 'success', 'PENDENTE_ASSINATURA' => 'warning', default => 'secondary' }; ?>">
                                <?php echo htmlspecialchars($doc['status'], ENT_QUOTES, 'UTF-8'); ?>
                            </span>
                        </td>
                        <td><?php echo date('d/m/Y H:i', strtotime($doc['created_at'])); ?></td>
                        <td>
                            <a href="/documentos/visualizar?id=<?php echo $doc['id']; ?>" class="btn btn-sm btn-outline-primary">Ver</a>
                            <a href="/documentos/auditoria?id=<?php echo $doc['id']; ?>" class="btn btn-sm btn-outline-info">Auditoria</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <?php if ($page > 1 || $hasMore): ?>
            <div class="card-footer d-flex justify-content-between">
                <?php if ($page > 1): ?>
                    <a href="?<?php echo http_build_query(array_merge($filtros, ['page' => $page - 1])); ?>" class="btn btn-sm btn-outline-secondary">&laquo; Anterior</a>
                <?php else: ?>
                    <span></span>
                <?php endif; ?>

                <?php if ($hasMore): ?>
                    <a href="?<?php echo http_build_query(array_merge($filtros, ['page' => $page + 1])); ?>" class="btn btn-sm btn-outline-secondary">Próximo &raquo;</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
<?php elseif (isset($resultados)): ?>
    <div class="alert alert-info">Nenhum documento encontrado com os filtros selecionados.</div>
<?php endif; ?>
