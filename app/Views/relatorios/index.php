<?php
use App\Core\Security;

$token = Security::csrfToken();
?>
<h1 class="h4 mb-3">Relatórios</h1>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card shadow-sm h-100 border-primary">
            <div class="card-body">
                <h5 class="card-title">Documentos</h5>
                <p class="card-text small text-muted">Visão por departamento, pasta e status.</p>
                <button type="button" class="btn btn-sm btn-primary" data-relatorio="documentos">Selecionar</button>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <h5 class="card-title">Assinaturas</h5>
                <p class="card-text small text-muted">Total por usuário e período.</p>
                <button type="button" class="btn btn-sm btn-outline-primary" data-relatorio="assinaturas">Selecionar</button>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <h5 class="card-title">Auditoria</h5>
                <p class="card-text small text-muted">Eventos do sistema por usuário e ação.</p>
                <button type="button" class="btn btn-sm btn-outline-primary" data-relatorio="auditoria">Selecionar</button>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <h5 class="card-title">OCR</h5>
                <p class="card-text small text-muted">Documentos processados e páginas.</p>
                <button type="button" class="btn btn-sm btn-outline-primary" data-relatorio="ocr">Selecionar</button>
            </div>
        </div>
    </div>
</div>

<form method="post" action="/relatorios/gerar" class="card shadow-sm mb-4" id="form-relatorio">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($token, ENT_QUOTES, 'UTF-8'); ?>">
    <input type="hidden" name="tipo" id="relatorio-tipo" value="<?php echo isset($tipo) ? htmlspecialchars($tipo, ENT_QUOTES, 'UTF-8') : 'documentos'; ?>">
    <div class="card-header">
        <strong>Filtros</strong>
        <span class="text-muted small ms-2" id="relatorio-label"></span>
    </div>
    <div class="card-body">
        <div class="row g-3" data-relatorio-form="documentos">
            <div class="col-md-3">
                <label class="form-label small text-muted">Departamento</label>
                <select name="filtros[departamento_id]" class="form-select form-select-sm">
                    <option value="">Todos</option>
                    <?php foreach ($departamentos as $dept): ?>
                        <option value="<?php echo $dept['id']; ?>" <?php echo !empty($filtros['departamento_id']) && (int) $filtros['departamento_id'] === (int) $dept['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($dept['nome'], ENT_QUOTES, 'UTF-8'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small text-muted">Status</label>
                <select name="filtros[status]" class="form-select form-select-sm">
                    <option value="">Todos</option>
                    <option value="EM_EDICAO" <?php echo (isset($filtros['status']) && $filtros['status'] === 'EM_EDICAO') ? 'selected' : ''; ?>>Em edição</option>
                    <option value="PENDENTE_ASSINATURA" <?php echo (isset($filtros['status']) && $filtros['status'] === 'PENDENTE_ASSINATURA') ? 'selected' : ''; ?>>Pendente assinatura</option>
                    <option value="ASSINADO" <?php echo (isset($filtros['status']) && $filtros['status'] === 'ASSINADO') ? 'selected' : ''; ?>>Assinado</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small text-muted">Início</label>
                <input type="date" name="filtros[inicio]" class="form-control form-control-sm" value="<?php echo htmlspecialchars($filtros['inicio'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label small text-muted">Fim</label>
                <input type="date" name="filtros[fim]" class="form-control form-control-sm" value="<?php echo htmlspecialchars($filtros['fim'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
            </div>
        </div>

        <div class="row g-3 d-none" data-relatorio-form="assinaturas">
            <div class="col-md-3">
                <label class="form-label small text-muted">Usuário</label>
                <select name="filtros[usuario_id]" class="form-select form-select-sm">
                    <option value="">Todos</option>
                    <?php foreach ($usuarios as $u): ?>
                        <option value="<?php echo $u['id']; ?>" <?php echo !empty($filtros['usuario_id']) && (int) $filtros['usuario_id'] === (int) $u['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($u['nome'], ENT_QUOTES, 'UTF-8'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small text-muted">Departamento</label>
                <select name="filtros[departamento_id]" class="form-select form-select-sm">
                    <option value="">Todos</option>
                    <?php foreach ($departamentos as $dept): ?>
                        <option value="<?php echo $dept['id']; ?>" <?php echo !empty($filtros['departamento_id']) && (int) $filtros['departamento_id'] === (int) $dept['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($dept['nome'], ENT_QUOTES, 'UTF-8'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small text-muted">Início</label>
                <input type="date" name="filtros[inicio]" class="form-control form-control-sm" value="<?php echo htmlspecialchars($filtros['inicio'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label small text-muted">Fim</label>
                <input type="date" name="filtros[fim]" class="form-control form-control-sm" value="<?php echo htmlspecialchars($filtros['fim'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
            </div>
        </div>

        <div class="row g-3 d-none" data-relatorio-form="auditoria">
            <div class="col-md-3">
                <label class="form-label small text-muted">Usuário ID</label>
                <input type="number" name="filtros[usuario_id]" class="form-control form-control-sm" value="<?php echo htmlspecialchars($filtros['usuario_id'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label small text-muted">Ação</label>
                <input type="text" name="filtros[acao]" class="form-control form-control-sm" value="<?php echo htmlspecialchars($filtros['acao'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label small text-muted">Entidade</label>
                <input type="text" name="filtros[entidade]" class="form-control form-control-sm" value="<?php echo htmlspecialchars($filtros['entidade'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label small text-muted">Início</label>
                <input type="date" name="filtros[inicio]" class="form-control form-control-sm" value="<?php echo htmlspecialchars($filtros['inicio'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label small text-muted">Fim</label>
                <input type="date" name="filtros[fim]" class="form-control form-control-sm" value="<?php echo htmlspecialchars($filtros['fim'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
            </div>
        </div>

        <div class="row g-3 d-none" data-relatorio-form="ocr">
            <div class="col-md-3">
                <label class="form-label small text-muted">Departamento</label>
                <select name="filtros[departamento_id]" class="form-select form-select-sm">
                    <option value="">Todos</option>
                    <?php foreach ($departamentos as $dept): ?>
                        <option value="<?php echo $dept['id']; ?>" <?php echo !empty($filtros['departamento_id']) && (int) $filtros['departamento_id'] === (int) $dept['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($dept['nome'], ENT_QUOTES, 'UTF-8'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small text-muted">Início</label>
                <input type="date" name="filtros[inicio]" class="form-control form-control-sm" value="<?php echo htmlspecialchars($filtros['inicio'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label small text-muted">Fim</label>
                <input type="date" name="filtros[fim]" class="form-control form-control-sm" value="<?php echo htmlspecialchars($filtros['fim'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
            </div>
        </div>
    </div>
    <div class="card-footer d-flex justify-content-between align-items-center">
        <div class="small text-muted">
            Use filtros para limitar o período e o escopo dos dados.
        </div>
        <button type="submit" class="btn btn-primary btn-sm">Gerar Relatório</button>
    </div>
</form>

<?php if (isset($tipo, $dados)): ?>
    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <strong>Resultado</strong>
            </div>
            <div class="btn-group">
                <a href="/relatorios/exportar?tipo=<?php echo urlencode($tipo); ?>&formato=pdf" class="btn btn-sm btn-outline-secondary">PDF</a>
                <a href="/relatorios/exportar?tipo=<?php echo urlencode($tipo); ?>&formato=csv" class="btn btn-sm btn-outline-secondary">CSV</a>
            </div>
        </div>
        <div class="card-body">
            <?php if ($tipo === 'documentos'): ?>
                <div class="mb-3">
                    <span class="badge bg-secondary me-2">Total: <?php echo (int) $dados['total']; ?></span>
                    <span class="badge bg-light text-dark me-2">Em edição: <?php echo (int) $dados['por_status']['EM_EDICAO']; ?></span>
                    <span class="badge bg-warning text-dark me-2">Pendente: <?php echo (int) $dados['por_status']['PENDENTE_ASSINATURA']; ?></span>
                    <span class="badge bg-success">Assinado: <?php echo (int) $dados['por_status']['ASSINADO']; ?></span>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                        <tr>
                            <th>ID</th>
                            <th>Título</th>
                            <th>Departamento</th>
                            <th>Pasta</th>
                            <th>Status</th>
                            <th>Criado em</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($dados['linhas'] as $row): ?>
                            <tr>
                                <td><?php echo $row['id']; ?></td>
                                <td><?php echo htmlspecialchars($row['titulo'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars($row['departamento_nome'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars($row['pasta_nome'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars($row['status'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars($row['created_at'], ENT_QUOTES, 'UTF-8'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php elseif ($tipo === 'assinaturas'): ?>
                <div class="mb-3">
                    <span class="badge bg-secondary me-2">Total de assinaturas: <?php echo (int) $dados['total']; ?></span>
                </div>
                <div class="table-responsive mb-3">
                    <table class="table table-sm">
                        <thead>
                        <tr>
                            <th>Usuário</th>
                            <th>Total</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($dados['por_usuario'] as $linha): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($linha['usuario_nome'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo (int) $linha['total']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                        <tr>
                            <th>ID</th>
                            <th>Documento</th>
                            <th>Usuário</th>
                            <th>Departamento</th>
                            <th>Status</th>
                            <th>Assinado em</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($dados['linhas'] as $row): ?>
                            <tr>
                                <td><?php echo $row['id']; ?></td>
                                <td><?php echo htmlspecialchars($row['titulo'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars($row['usuario_nome'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars($row['departamento_nome'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars($row['status'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars($row['assinado_em'], ENT_QUOTES, 'UTF-8'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php elseif ($tipo === 'auditoria'): ?>
                <div class="mb-3">
                    <span class="badge bg-secondary me-2">Total de eventos: <?php echo (int) $dados['total']; ?></span>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                        <tr>
                            <th>Data/Hora</th>
                            <th>Usuário ID</th>
                            <th>Ação</th>
                            <th>Entidade</th>
                            <th>Entidade ID</th>
                            <th>IP</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($dados['linhas'] as $row): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['created_at'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars($row['usuario_id'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars($row['acao'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars($row['entidade'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars($row['entidade_id'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars($row['ip'], ENT_QUOTES, 'UTF-8'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php elseif ($tipo === 'ocr'): ?>
                <div class="mb-3">
                    <span class="badge bg-secondary me-2">Total de registros: <?php echo (int) $dados['total']; ?></span>
                    <span class="badge bg-info text-dark">Páginas processadas: <?php echo (int) $dados['paginas']; ?></span>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                        <tr>
                            <th>ID</th>
                            <th>Documento</th>
                            <th>Departamento</th>
                            <th>Páginas</th>
                            <th>Engine</th>
                            <th>Criado em</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($dados['linhas'] as $row): ?>
                            <tr>
                                <td><?php echo $row['id']; ?></td>
                                <td><?php echo htmlspecialchars($row['titulo'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars($row['departamento_nome'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo (int) $row['paginas_processadas']; ?></td>
                                <td><?php echo htmlspecialchars($row['engine'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars($row['created_at'], ENT_QUOTES, 'UTF-8'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<script>
(function () {
    var tipoInput = document.getElementById('relatorio-tipo');
    var label = document.getElementById('relatorio-label');
    var buttons = document.querySelectorAll('[data-relatorio]');

    function setTipo(tipo) {
        tipoInput.value = tipo;
        document.querySelectorAll('[data-relatorio-form]').forEach(function (el) {
            if (el.getAttribute('data-relatorio-form') === tipo) {
                el.classList.remove('d-none');
            } else {
                el.classList.add('d-none');
            }
        });
        buttons.forEach(function (btn) {
            if (btn.getAttribute('data-relatorio') === tipo) {
                btn.classList.remove('btn-outline-primary');
                btn.classList.add('btn-primary');
            } else {
                btn.classList.remove('btn-primary');
                btn.classList.add('btn-outline-primary');
            }
        });
        if (tipo === 'documentos') {
            label.textContent = 'Relatório de documentos';
        } else if (tipo === 'assinaturas') {
            label.textContent = 'Relatório de assinaturas';
        } else if (tipo === 'auditoria') {
            label.textContent = 'Relatório de auditoria';
        } else if (tipo === 'ocr') {
            label.textContent = 'Relatório de OCR';
        }
    }

    buttons.forEach(function (btn) {
        btn.addEventListener('click', function () {
            setTipo(btn.getAttribute('data-relatorio'));
        });
    });

    var initial = tipoInput.value || 'documentos';
    setTipo(initial);
})();
</script>

