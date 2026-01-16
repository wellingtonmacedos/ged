<?php
use App\Core\Security;

$token = Security::csrfToken();
?>
<h1 class="h4 mb-3">Configurar assinaturas</h1>

<div class="mb-3">
    <p class="mb-1"><strong>Documento:</strong> <?php echo htmlspecialchars($documento['titulo'], ENT_QUOTES, 'UTF-8'); ?></p>
    <p class="mb-1"><strong>Status atual:</strong> <?php echo htmlspecialchars($documento['status'], ENT_QUOTES, 'UTF-8'); ?></p>
    <p class="mb-0">
        <a href="/documentos/visualizar?id=<?php echo $documento['id']; ?>" class="btn btn-sm btn-outline-secondary me-2">Ver documento</a>
        <a href="/documentos?pasta_id=<?php echo $documento['pasta_id']; ?>" class="btn btn-sm btn-secondary">Voltar para pasta</a>
    </p>
</div>

<form method="post" action="/assinaturas/configurar">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($token, ENT_QUOTES, 'UTF-8'); ?>">
    <input type="hidden" name="documento_id" value="<?php echo (int) $documento['id']; ?>">

    <div class="card shadow-sm mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span class="fw-bold">Fluxo de assinaturas</span>
            <button type="button" class="btn btn-sm btn-outline-primary" id="btn-add-linha">Adicionar linha</button>
        </div>
        <div class="card-body p-0">
            <table class="table mb-0" id="tabela-assinaturas">
                <thead>
                    <tr>
                        <th style="width: 55%;">Usuário</th>
                        <th style="width: 20%;">Ordem</th>
                        <th style="width: 15%;">Status</th>
                        <th style="width: 10%;"></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($assinaturas)): ?>
                        <?php foreach ($assinaturas as $ass): ?>
                            <tr>
                                <td>
                                    <select name="usuario_id[]" class="form-select form-select-sm">
                                        <option value="">Selecione</option>
                                        <?php foreach ($usuarios as $u): ?>
                                            <option value="<?php echo $u['id']; ?>" <?php echo (int) $u['id'] === (int) $ass['usuario_id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($u['nome'], ENT_QUOTES, 'UTF-8'); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td>
                                    <input type="number" name="ordem[]" class="form-control form-control-sm" min="1" value="<?php echo (int) $ass['ordem']; ?>">
                                </td>
                                <td class="align-middle">
                                    <?php echo htmlspecialchars($ass['status'], ENT_QUOTES, 'UTF-8'); ?>
                                </td>
                                <td class="text-end align-middle">
                                    <button type="button" class="btn btn-sm btn-outline-danger btn-remover-linha">&times;</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    <tr class="linha-template">
                        <td>
                            <select name="usuario_id[]" class="form-select form-select-sm">
                                <option value="">Selecione</option>
                                <?php foreach ($usuarios as $u): ?>
                                    <option value="<?php echo $u['id']; ?>">
                                        <?php echo htmlspecialchars($u['nome'], ENT_QUOTES, 'UTF-8'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td>
                            <input type="number" name="ordem[]" class="form-control form-control-sm" min="1" value="">
                        </td>
                        <td class="align-middle text-muted">
                            PENDENTE
                        </td>
                        <td class="text-end align-middle">
                            <button type="button" class="btn btn-sm btn-outline-danger btn-remover-linha">&times;</button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="card-footer text-muted small">
            As assinaturas serão executadas na ordem crescente indicada na coluna Ordem.
        </div>
    </div>

    <div class="d-flex justify-content-between align-items-center">
        <div class="text-muted small">
            Deixe todas as linhas vazias para remover o fluxo de assinaturas e retornar o documento para edição.
        </div>
        <button type="submit" class="btn btn-primary">Salvar fluxo</button>
    </div>
</form>

<script>
    (function () {
        var tabela = document.getElementById('tabela-assinaturas');
        if (!tabela) {
            return;
        }

        var btnAdd = document.getElementById('btn-add-linha');
        var tbody = tabela.querySelector('tbody');
        var template = tbody.querySelector('.linha-template');

        if (btnAdd && template) {
            btnAdd.addEventListener('click', function () {
                var clone = template.cloneNode(true);
                clone.classList.remove('linha-template');
                var selects = clone.querySelectorAll('select');
                selects.forEach(function (el) {
                    el.value = '';
                });
                var inputs = clone.querySelectorAll('input[type="number"]');
                inputs.forEach(function (el) {
                    el.value = '';
                });
                tbody.appendChild(clone);
            });
        }

        tbody.addEventListener('click', function (e) {
            var btn = e.target.closest('.btn-remover-linha');
            if (!btn) {
                return;
            }
            var tr = btn.closest('tr');
            if (!tr) {
                return;
            }
            if (tr.classList.contains('linha-template')) {
                return;
            }
            tbody.removeChild(tr);
        });
    })();
</script>

