<h2 class="h4 mb-4">Busca Avançada</h2>

<div class="card mb-4 shadow-sm">
    <div class="card-body">
        <form method="get" action="/documentos/busca" class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Termo</label>
                <input type="text" name="q" class="form-control" value="<?php echo htmlspecialchars($filtros['q'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                <div class="form-check mt-2">
                    <input class="form-check-input" type="checkbox" name="ocr" id="ocr" value="1" <?php echo !empty($filtros['ocr']) && $filtros['ocr'] === '1' ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="ocr">
                        Pesquisar dentro do documento (OCR)
                    </label>
                    <div class="form-text">
                        Inclui conteúdo extraído de PDFs digitalizados.
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <label class="form-label">Departamento</label>
                <select name="departamento_id" class="form-select">
                    <option value="">Todos</option>
                    <?php foreach ($departamentos as $dep): ?>
                        <option value="<?php echo $dep['id']; ?>" <?php echo (!empty($filtros['departamento_id']) && (int) $filtros['departamento_id'] === (int) $dep['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($dep['nome'], ENT_QUOTES, 'UTF-8'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-2">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="">Todos</option>
                    <option value="EM_EDICAO" <?php echo (!empty($filtros['status']) && $filtros['status'] === 'EM_EDICAO') ? 'selected' : ''; ?>>Em Edição</option>
                    <option value="PENDENTE_ASSINATURA" <?php echo (!empty($filtros['status']) && $filtros['status'] === 'PENDENTE_ASSINATURA') ? 'selected' : ''; ?>>Pendente</option>
                    <option value="ASSINADO" <?php echo (!empty($filtros['status']) && $filtros['status'] === 'ASSINADO') ? 'selected' : ''; ?>>Assinado</option>
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

            <div class="col-md-3">
                <label class="form-label">Pasta</label>
                <select name="pasta_id" class="form-select">
                    <option value="">Todas</option>
                    <?php if (!empty($pastas)): ?>
                        <?php foreach ($pastas as $pasta): ?>
                            <option value="<?php echo $pasta['id']; ?>" <?php echo (!empty($filtros['pasta_id']) && (int) $filtros['pasta_id'] === (int) $pasta['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($pasta['nome'], ENT_QUOTES, 'UTF-8'); ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>

            <div class="col-md-2">
                <label class="form-label">Ano</label>
                <input type="number" name="ano" class="form-control" min="1900" max="2100" value="<?php echo isset($filtros['ano']) ? htmlspecialchars($filtros['ano'], ENT_QUOTES, 'UTF-8') : ''; ?>">
            </div>

            <div class="col-md-2">
                <label class="form-label">Mês</label>
                <select name="mes" class="form-select">
                    <option value="">Todos</option>
                    <?php
                    $nomesMeses = [
                        '01' => 'Janeiro',
                        '02' => 'Fevereiro',
                        '03' => 'Março',
                        '04' => 'Abril',
                        '05' => 'Maio',
                        '06' => 'Junho',
                        '07' => 'Julho',
                        '08' => 'Agosto',
                        '09' => 'Setembro',
                        '10' => 'Outubro',
                        '11' => 'Novembro',
                        '12' => 'Dezembro',
                    ];
                    ?>
                    <?php foreach ($nomesMeses as $value => $label): ?>
                        <option value="<?php echo $value; ?>" <?php echo (!empty($filtros['mes']) && $filtros['mes'] === $value) ? 'selected' : ''; ?>>
                            <?php echo $label; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
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
