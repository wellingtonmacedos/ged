<?php
use App\Core\Security;

$token = Security::csrfToken();
$anos = range((int) date('Y') - 5, (int) date('Y') + 1);
$meses = [
    1 => 'Janeiro',
    2 => 'Fevereiro',
    3 => 'Março',
    4 => 'Abril',
    5 => 'Maio',
    6 => 'Junho',
    7 => 'Julho',
    8 => 'Agosto',
    9 => 'Setembro',
    10 => 'Outubro',
    11 => 'Novembro',
    12 => 'Dezembro',
];

// Lógica para detectar contexto (Ano/Mês)
$isYearFolder = false;
$isMonthFolder = false;
if ($pasta) {
    if (preg_match('/^\d{4}$/', $pasta['nome'])) {
        $isYearFolder = true;
    } elseif (!empty($breadcrumb) && count($breadcrumb) >= 2) {
        // Verifica se o pai é um ano
        $parent = $breadcrumb[count($breadcrumb) - 2];
        if (preg_match('/^\d{4}$/', $parent['nome'])) {
            $isMonthFolder = true;
        }
    }
}
?>
<div class="row">
    <div class="col-md-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="h5 mb-0">Pastas</h2>
            <?php if (isset($user) && in_array($user['perfil'], ['ADMIN_GERAL', 'ADMIN_DEPARTAMENTO'])): ?>
                <div>
                    <a href="/departamentos" class="btn btn-sm btn-outline-secondary" title="Gerenciar Departamentos">⚙️</a>
                    <a href="/pastas/nova" class="btn btn-sm btn-outline-primary" title="Nova Pasta Raiz">+</a>
                </div>
            <?php endif; ?>
        </div>
        <div id="tree-pastas" class="border rounded bg-white" style="min-height: 400px; overflow-y: auto;"></div>
    </div>
    <div class="col-md-8">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <?php if ($pasta): ?>
                    <h2 class="h5 mb-0">
                        <?php echo htmlspecialchars($pasta['nome'], ENT_QUOTES, 'UTF-8'); ?>
                    </h2>
                <?php else: ?>
                    <h2 class="h5 mb-0">Explorador de documentos</h2>
                <?php endif; ?>
                <small class="text-muted">
                    <?php if (!empty($breadcrumb)): ?>
                        <?php foreach ($breadcrumb as $idx => $b): ?>
                            <?php if ($idx > 0): ?> / <?php endif; ?>
                            <?php echo htmlspecialchars($b['nome'], ENT_QUOTES, 'UTF-8'); ?>
                        <?php endforeach; ?>
                    <?php elseif ($pasta): ?>
                        <?php echo htmlspecialchars($pasta['nome'], ENT_QUOTES, 'UTF-8'); ?>
                    <?php else: ?>
                        Selecione uma pasta à esquerda
                    <?php endif; ?>
                </small>
            </div>
            
            <?php if ($pasta): ?>
                <div class="btn-group">
                    <?php if ($isYearFolder): ?>
                        <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalCriarMes">
                            + Criar Mês
                        </button>
                    <?php elseif (!$isMonthFolder): ?>
                         <!-- Se não é ano nem mês (assumindo que mês não cria ano dentro), mostra Criar Ano -->
                         <!-- Na verdade, mostra Criar Ano em qualquer pasta comum -->
                        <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalCriarAno">
                            + Criar Ano
                        </button>
                    <?php endif; ?>
                    
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#modalNovaSubpasta">
                        + Subpasta
                    </button>
                    <button type="button" class="btn btn-sm btn-primary" onclick="document.getElementById('form-upload-container').classList.toggle('d-none')">
                        + Documento
                    </button>
                </div>
            <?php endif; ?>
        </div>

        <?php if (!empty($subpastas)): ?>
            <div class="mb-3">
                <div class="mb-2 fw-semibold small text-uppercase text-muted">Pastas</div>
                <div class="row row-cols-2 row-cols-md-3 row-cols-lg-4 g-3">
                    <?php foreach ($subpastas as $sp): ?>
                        <div class="col">
                            <a href="/documentos?pasta_id=<?php echo (int) $sp['id']; ?>" class="text-decoration-none text-reset">
                                <div class="border rounded bg-white p-3 h-100 folder-card position-relative" style="cursor: pointer;">
                                    <div class="display-6 mb-2">📁</div>
                                    <div class="small fw-semibold">
                                        <?php echo htmlspecialchars($sp['nome'], ENT_QUOTES, 'UTF-8'); ?>
                                    </div>
                                </div>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($pasta): ?>
            <!-- Container do Upload (oculto por padrão) -->
            <div id="form-upload-container" class="card mb-4 d-none">
                <div class="card-body">
                    <h5 class="card-title">Novo Documento</h5>
                    <form method="post" action="/documentos/upload" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($token, ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" name="pasta_id" value="<?php echo (int) $pasta['id']; ?>">
        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-2">
                                    <label class="form-label">Título</label>
                                    <input type="text" name="titulo" class="form-control" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-2">
                                    <label class="form-label">Tipo</label>
                                    <input type="text" name="tipo" class="form-control" required>
                                </div>
                            </div>
                        </div>
        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-2">
                                    <label class="form-label">Subpasta destino</label>
                                    <select name="subpasta_id" class="form-select">
                                        <option value="">(Pasta atual)</option>
                                        <?php if (!empty($subpastas)): ?>
                                            <?php foreach ($subpastas as $sp): ?>
                                                <option value="<?php echo (int) $sp['id']; ?>">
                                                    <?php echo htmlspecialchars($sp['nome'], ENT_QUOTES, 'UTF-8'); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-2">
                                    <label class="form-label">Ano</label>
                                    <select name="ano_doc" class="form-select">
                                        <option value="">(Opcional)</option>
                                        <?php foreach ($anos as $ano): ?>
                                            <option value="<?php echo $ano; ?>"><?php echo $ano; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-2">
                                    <label class="form-label">Mês</label>
                                    <select name="mes_doc" class="form-select">
                                        <option value="">(Opcional)</option>
                                        <?php foreach ($meses as $num => $nomeMes): ?>
                                            <option value="<?php echo $num; ?>"><?php echo $nomeMes; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
        
                        <div class="mb-2">
                            <label class="form-label">Arquivo</label>
                            <input type="file" name="arquivo" class="form-control" required>
                        </div>
        
                        <button type="submit" class="btn btn-primary">Enviar documento</button>
                    </form>
                </div>
            </div>

            <table class="table table-sm table-striped">
                <thead>
                <tr>
                    <th>Título</th>
                    <th>Status</th>
                    <th>Versão</th>
                    <th>Ações</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($documentos as $doc): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($doc['titulo'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars($doc['status'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo (int) $doc['versao_atual']; ?></td>
                        <td>
                            <a href="/documentos/visualizar?id=<?php echo (int) $doc['id']; ?>" class="btn btn-sm btn-outline-primary me-1">Ver</a>
                            <a href="/documentos/download?id=<?php echo (int) $doc['id']; ?>" class="btn btn-sm btn-outline-secondary">Baixar</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="text-muted mt-4">
                <p>Nenhuma pasta selecionada. Use a árvore à esquerda para navegar.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modais -->
<?php if ($pasta): ?>
    <!-- Modal Criar Ano -->
    <div class="modal fade" id="modalCriarAno" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="/pastas/salvar" method="post">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($token, ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="parent_id" value="<?php echo (int) $pasta['id']; ?>">
                    <div class="modal-header">
                        <h5 class="modal-title">Criar Pasta de Ano</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Selecione o Ano</label>
                            <select name="nome" class="form-select">
                                <?php foreach ($anos as $ano): ?>
                                    <option value="<?php echo $ano; ?>" <?php echo $ano == date('Y') ? 'selected' : ''; ?>>
                                        <?php echo $ano; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Criar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Criar Mês -->
    <div class="modal fade" id="modalCriarMes" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="/pastas/salvar" method="post">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($token, ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="parent_id" value="<?php echo (int) $pasta['id']; ?>">
                    <div class="modal-header">
                        <h5 class="modal-title">Criar Pasta de Mês</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Selecione o Mês</label>
                            <select name="nome" class="form-select">
                                <?php foreach ($meses as $nomeMes): ?>
                                    <option value="<?php echo $nomeMes; ?>">
                                        <?php echo $nomeMes; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Criar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Nova Subpasta (Genérica) -->
    <div class="modal fade" id="modalNovaSubpasta" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="/pastas/salvar" method="post">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($token, ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="parent_id" value="<?php echo (int) $pasta['id']; ?>">
                    <div class="modal-header">
                        <h5 class="modal-title">Nova Subpasta</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Nome da Pasta</label>
                            <input type="text" name="nome" class="form-control" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Criar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Função para carregar a árvore de pastas
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
                    
                    const link = document.createElement('button');
                    link.type = 'button';
                    link.classList.add('btn', 'btn-sm', 'btn-link', 'text-start', 'w-100', 'text-decoration-none', 'text-dark');
                    link.textContent = '📁 ' + pasta.nome;
                    
                    // Highlight logic
                    if (<?php echo $pasta ? $pasta['id'] : 'null'; ?> == pasta.id) {
                        link.classList.add('bg-primary', 'text-white');
                    }

                    link.addEventListener('click', function () {
                        window.location.href = '/documentos?pasta_id=' + pasta.id;
                    });
                    
                    li.appendChild(link);
                    ul.appendChild(li);
                });
                container.innerHTML = '';
                container.appendChild(ul);
            });
    }

    const rootContainer = document.getElementById('tree-pastas');
    loadChildren(null, rootContainer);
});
</script>
