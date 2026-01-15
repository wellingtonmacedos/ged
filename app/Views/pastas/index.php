<?php
use App\Core\Security;
use App\Core\Auth;

$token = Security::csrfToken();
$user = Auth::user();
?>
<div class="row">
    <div class="col-md-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="h5 mb-0">Pastas</h2>
            <?php if (in_array($user['perfil'], ['ADMIN_GERAL', 'ADMIN_DEPARTAMENTO'], true)): ?>
                <a href="/pastas/nova" class="btn btn-sm btn-outline-primary">Nova pasta</a>
            <?php endif; ?>
        </div>
        <div id="tree-pastas" class="border rounded bg-white" style="min-height: 400px; overflow-y: auto;"></div>
    </div>
    <div class="col-md-8">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h2 class="h5 mb-0">Explorador de documentos</h2>
                <small class="text-muted" id="current-path">Selecione um departamento/pasta</small>
            </div>
            <div class="btn-group btn-group-sm" role="group">
                <button type="button" class="btn btn-outline-secondary" id="btn-refresh">Atualizar</button>
            </div>
        </div>
        <div id="folder-grid" class="row row-cols-2 row-cols-md-3 row-cols-lg-4 g-3 mb-4"></div>
        <div id="hint-empty" class="text-muted">
            Nenhuma pasta selecionada. Use a árvore à esquerda como no gerenciador de arquivos do Zorin OS.
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    function loadChildren(parentId, container) {
        const url = '/pastas/children' + (parentId ? ('?parent_id=' + parentId) : '');
        fetch(url)
            .then(response => response.json())
            .then(data => {
                const ul = document.createElement('ul');
                ul.classList.add('list-unstyled', 'ms-2');
                data.forEach(function (pasta) {
                    const li = document.createElement('li');
                    li.classList.add('folder-node');
                    li.dataset.id = pasta.id;
                    li.dataset.nome = pasta.nome;

                    const link = document.createElement('button');
                    link.type = 'button';
                    link.classList.add('btn', 'btn-sm', 'btn-link', 'text-start', 'w-100');
                    link.textContent = '📁 ' + pasta.nome;
                    link.addEventListener('click', function () {
                        highlightNode(li);
                        loadFolderGrid(pasta.id, pasta.nome);
                    });

                    li.appendChild(link);
                    ul.appendChild(li);
                });
                container.innerHTML = '';
                container.appendChild(ul);
            });
    }

    function loadFolderGrid(parentId, nomePasta) {
        const pathLabel = document.getElementById('current-path');
        const grid = document.getElementById('folder-grid');
        const hint = document.getElementById('hint-empty');
        pathLabel.textContent = nomePasta || 'Raiz';
        hint.style.display = 'none';
        grid.innerHTML = '';

        const url = '/pastas/children' + (parentId ? ('?parent_id=' + parentId) : '');
        fetch(url)
            .then(response => response.json())
            .then(data => {
                if (!Array.isArray(data) || data.length === 0) {
                    grid.innerHTML = '<div class="col text-muted">Nenhuma subpasta aqui.</div>';
                    return;
                }

                data.forEach(function (pasta) {
                    const col = document.createElement('div');
                    col.classList.add('col');

                    const card = document.createElement('div');
                    card.classList.add('border', 'rounded', 'bg-white', 'p-3', 'h-100', 'folder-card');
                    card.style.cursor = 'pointer';

                    const icon = document.createElement('div');
                    icon.classList.add('display-6', 'mb-2');
                    icon.textContent = '📁';

                    const name = document.createElement('div');
                    name.classList.add('small', 'fw-semibold');
                    name.textContent = pasta.nome;

                    card.appendChild(icon);
                    card.appendChild(name);

                    card.addEventListener('click', function () {
                        window.location.href = '/documentos?pasta_id=' + pasta.id;
                    });

                    col.appendChild(card);
                    grid.appendChild(col);
                });
            });
    }

    function highlightNode(node) {
        document.querySelectorAll('.folder-node > button').forEach(function (btn) {
            btn.classList.remove('bg-primary', 'text-white');
        });
        const btn = node.querySelector('button');
        if (btn) {
            btn.classList.add('bg-primary', 'text-white');
        }
    }

    const rootContainer = document.getElementById('tree-pastas');
    loadChildren(null, rootContainer);

    const btnRefresh = document.getElementById('btn-refresh');
    if (btnRefresh) {
        btnRefresh.addEventListener('click', function () {
            const currentSelected = document.querySelector('.folder-node > button.bg-primary');
            let parentId = null;
            let nome = '';
            if (currentSelected) {
                const li = currentSelected.closest('.folder-node');
                parentId = li ? li.dataset.id : null;
                nome = li ? li.dataset.nome : '';
            }
            loadChildren(parentId, rootContainer);
            if (parentId) {
                loadFolderGrid(parentId, nome);
            }
        });
    }
});
</script>
