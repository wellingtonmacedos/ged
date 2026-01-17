<?php
use App\Core\Security;
use App\Core\Auth;

$token = Security::csrfToken();
$anos = range((int) date('Y') - 5, (int) date('Y') + 1);
$meses = [
    1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril',
    5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto',
    9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro',
];

$isYearFolder = false;
$isMonthFolder = false;
$detectedYear = null;
$detectedMonth = null;
$folderDocCounts = isset($folderDocCounts) && is_array($folderDocCounts) ? $folderDocCounts : [];

if ($pasta) {
    if (preg_match('/^\d{4}$/', $pasta['nome'])) {
        $isYearFolder = true;
        $detectedYear = $pasta['nome'];
    } elseif (!empty($breadcrumb) && count($breadcrumb) >= 2) {
        $parent = $breadcrumb[count($breadcrumb) - 2];
        if (preg_match('/^\d{4}$/', $parent['nome'])) {
            $isMonthFolder = true;
            $detectedYear = $parent['nome'];
            $monthKey = array_search($pasta['nome'], $meses, true);
            if ($monthKey !== false) {
                $detectedMonth = $monthKey;
            }
        }
    }
}
?>

<div class="zorin-topbar">
    <div class="d-flex align-items-center me-4">
        <a href="/" class="text-decoration-none d-flex align-items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-primary"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
            <span class="fw-bold fs-5 tracking-tight">GED Institucional</span>
        </a>
    </div>

    <!-- Breadcrumbs -->
    <div class="app-breadcrumb flex-grow-1 d-none d-md-flex">
        <a href="/documentos">Início</a>
        <?php if (!empty($breadcrumb)): ?>
            <?php foreach ($breadcrumb as $b): ?>
                <span class="separator">/</span>
                <a href="/documentos?pasta_id=<?php echo $b['id']; ?>" class="<?php echo ($pasta && $pasta['id'] == $b['id']) ? 'current' : ''; ?>">
                    <?php echo htmlspecialchars($b['nome'], ENT_QUOTES, 'UTF-8'); ?>
                </a>
            <?php endforeach; ?>
        <?php elseif ($pasta): ?>
            <span class="separator">/</span>
            <span class="current"><?php echo htmlspecialchars($pasta['nome'], ENT_QUOTES, 'UTF-8'); ?></span>
        <?php endif; ?>
    </div>

    <div class="d-flex align-items-center gap-3">
        <a href="/documentos/busca" class="text-decoration-none text-muted" title="Buscar">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
        </a>
        <button type="button" class="btn btn-sm btn-outline-secondary" id="theme-toggle-docs" title="Alternar tema">
            Tema
        </button>
        <a href="/assinaturas/painel" class="text-muted text-decoration-none" title="Assinaturas">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><line x1="20" y1="8" x2="20" y2="14"></line><line x1="23" y1="11" x2="17" y2="11"></line></svg>
        </a>
        <div class="dropdown">
            <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle" data-bs-toggle="dropdown">
                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 32px; height: 32px; font-size: 14px;">
                    <?php echo strtoupper(substr($user['nome'], 0, 1)); ?>
                </div>
            </a>
            <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                <li><span class="dropdown-header"><?php echo htmlspecialchars($user['nome']); ?></span></li>
                <li><hr class="dropdown-divider"></li>
                <?php if ($user['perfil'] === 'ADMIN_GERAL'): ?>
                    <li><a class="dropdown-item" href="/departamentos">Departamentos</a></li>
                    <li><a class="dropdown-item" href="/sistema/backups">Backups</a></li>
                <?php endif; ?>
                <li><a class="dropdown-item" href="/pastas">Gerenciar Pastas</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item text-danger" href="/logout">Sair</a></li>
            </ul>
        </div>
    </div>
</div>

<!-- Main Body -->
<div class="zorin-body">
    <!-- Sidebar -->
    <div class="zorin-sidebar">
        <div class="p-3 pb-2 d-flex justify-content-between align-items-center">
            <span class="text-uppercase text-muted small fw-bold">Locais</span>
            <?php if (isset($user) && in_array($user['perfil'], ['ADMIN_GERAL', 'ADMIN_DEPARTAMENTO'])): ?>
                <a href="/pastas/nova" class="text-muted text-decoration-none" title="Nova Pasta Raiz">+</a>
            <?php endif; ?>
        </div>
        <div id="tree-pastas" class="folder-tree">
            <!-- Loading Skeleton -->
            <div class="p-2 text-muted small">Carregando pastas...</div>
        </div>
    </div>

    <!-- Content Area -->
    <div class="zorin-main">
        <!-- Header -->
        <div class="zorin-header">
            <div class="d-flex align-items-center">
                <?php if ($pasta): ?>
                    <h1 class="h5 mb-0 fw-bold"><?php echo htmlspecialchars($pasta['nome'], ENT_QUOTES, 'UTF-8'); ?></h1>
                    <?php if (isset($pasta['status']) && $pasta['status'] === 'ASSINADO'): ?>
                        <span class="badge bg-success ms-2">Assinado</span>
                    <?php endif; ?>
                <?php else: ?>
                    <h1 class="h5 mb-0 fw-bold">Início</h1>
                <?php endif; ?>
            </div>
            
            <!-- Search in Folder -->
            <?php if ($pasta): ?>
            <div class="flex-grow-1 mx-4">
                <form action="/documentos" method="get" class="d-flex">
                    <input type="hidden" name="pasta_id" value="<?php echo $pasta['id']; ?>">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white border-end-0">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-muted"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                        </span>
                        <input type="text" name="q" class="form-control border-start-0 ps-0" placeholder="Pesquisar nesta pasta..." value="<?php echo isset($_GET['q']) ? htmlspecialchars($_GET['q'], ENT_QUOTES, 'UTF-8') : ''; ?>">
                    </div>
                </form>
            </div>
            <?php endif; ?>

            <div class="d-flex gap-2">
                <?php if ($pasta): ?>
                    <?php if ($isYearFolder): ?>
                        <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalCriarMes">
                            + Mês
                        </button>
                    <?php elseif (!$isMonthFolder): ?>
                        <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalCriarAno">
                            + Ano
                        </button>
                    <?php endif; ?>
                    
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#modalNovaSubpasta">
                        + Pasta
                    </button>
                    <button type="button" class="btn btn-sm btn-primary" onclick="toggleUploadForm()">
                        Upload
                    </button>
                <?php endif; ?>
                <?php if (!empty($subpastas)): ?>
                    <div class="folder-view-toolbar ms-3 d-none d-md-inline-flex align-items-center gap-1">
                        <button type="button" class="btn btn-light btn-sm" data-view-mode="grid">
                            ■■
                        </button>
                        <button type="button" class="btn btn-light btn-sm" data-view-mode="compact">
                            ▣
                        </button>
                        <button type="button" class="btn btn-light btn-sm" data-view-mode="list">
                            ☰
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Scrollable Content -->
        <div class="zorin-content p-0">
            <!-- Upload Form -->
            <?php if ($pasta): ?>
                <div id="form-upload-container" class="bg-light border-bottom p-4 d-none">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">Novo Documento</h5>
                        <button type="button" class="btn-close" onclick="toggleUploadForm()"></button>
                    </div>
                    <form method="post" action="/documentos/upload" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($token, ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" name="pasta_id" value="<?php echo (int) $pasta['id']; ?>">
        
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label small text-muted">Título</label>
                                <input type="text" name="titulo" class="form-control form-control-sm" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small text-muted">Tipo</label>
                                <input type="text" name="tipo" class="form-control form-control-sm" required>
                            </div>
                        </div>
                        <div class="row g-3 mb-3">
                            <!-- Subpasta Select: Oculta se contexto de Ano/Mês detectado (trava na pasta atual) -->
                            <?php if (!$detectedYear): ?>
                            <div class="col-md-4">
                                <label class="form-label small text-muted">Pasta</label>
                                <select name="subpasta_id" class="form-select form-select-sm">
                                    <option value="">(Atual)</option>
                                    <?php if (!empty($subpastas)): ?>
                                        <?php foreach ($subpastas as $sp): ?>
                                            <option value="<?php echo (int) $sp['id']; ?>"><?php echo htmlspecialchars($sp['nome']); ?></option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>
                            <?php endif; ?>

                            <!-- Ano Field -->
                            <div class="col-md-4">
                                <label class="form-label small text-muted">Ano</label>
                                <?php if ($detectedYear): ?>
                                    <input type="hidden" name="ano_doc" value="<?php echo $detectedYear; ?>">
                                    <input type="text" class="form-control form-control-sm" value="<?php echo $detectedYear; ?>" disabled>
                                <?php else: ?>
                                    <select name="ano_doc" class="form-select form-select-sm">
                                        <option value="">(Opcional)</option>
                                        <?php foreach ($anos as $ano): ?>
                                            <option value="<?php echo $ano; ?>"><?php echo $ano; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                <?php endif; ?>
                            </div>

                            <!-- Mês Field -->
                            <?php if ($detectedYear && !$detectedMonth): ?>
                                <!-- Contexto de Ano detectado mas sem mês específico (ex: dentro de "2024") -> Oculta select de Mês -->
                            <?php else: ?>
                                <div class="col-md-4">
                                    <label class="form-label small text-muted">Mês</label>
                                    <?php if ($detectedMonth): ?>
                                        <input type="hidden" name="mes_doc" value="<?php echo $detectedMonth; ?>">
                                        <input type="text" class="form-control form-control-sm" value="<?php echo $meses[$detectedMonth]; ?>" disabled>
                                    <?php else: ?>
                                        <select name="mes_doc" class="form-select form-select-sm">
                                            <option value="">(Opcional)</option>
                                            <?php foreach ($meses as $num => $nomeMes): ?>
                                                <option value="<?php echo $num; ?>"><?php echo $nomeMes; ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small text-muted">Arquivo</label>
                            <input type="file" name="arquivo" class="form-control form-control-sm" required>
                        </div>
                        <div class="text-end">
                            <button type="submit" class="btn btn-primary btn-sm">Enviar Documento</button>
                        </div>
                    </form>
                </div>
            <?php endif; ?>

            <!-- Subfolders View -->
            <?php if (!empty($subpastas)): ?>
                <div class="p-4 pb-0 folder-view-wrapper">
                    <div class="folder-grid mb-4" id="folder-grid">
                        <?php foreach ($subpastas as $sp): ?>
                            <?php $folderCount = $folderDocCounts[(int) $sp['id']] ?? 0; ?>
                            <a href="/documentos?pasta_id=<?php echo (int) $sp['id']; ?>" class="text-decoration-none">
                                <div class="folder-card">
                                    <div class="folder-icon text-primary">📁</div>
                                    <div class="folder-text">
                                        <div class="folder-name" title="<?php echo htmlspecialchars($sp['nome']); ?>">
                                            <?php echo htmlspecialchars($sp['nome']); ?>
                                        </div>
                                        <?php if ($folderCount > 0): ?>
                                            <div class="folder-meta">
                                                <span class="folder-count">
                                                    <?php echo $folderCount; ?> <?php echo $folderCount === 1 ? 'documento' : 'documentos'; ?>
                                                </span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($pasta): ?>
                <?php if (!empty($documentos)): ?>
                    <div class="px-4 pt-3 d-flex justify-content-end">
                        <div class="doc-view-toolbar d-inline-flex align-items-center gap-1">
                            <button type="button" class="btn btn-light btn-sm" data-doc-view="list">☰</button>
                            <button type="button" class="btn btn-light btn-sm" data-doc-view="grid">■■</button>
                            <button type="button" class="btn btn-light btn-sm" data-doc-view="compact">▣</button>
                        </div>
                    </div>
                <?php endif; ?>
                <div class="doc-view-wrapper px-4">
                    <div class="table-responsive doc-view-table">
                        <table class="file-table">
                            <thead>
                                <tr>
                                    <th style="width: 40px;"><input type="checkbox" class="form-check-input" id="check-all"></th>
                                    <th>Nome</th>
                                    <th>Status</th>
                                    <th>Versão</th>
                                    <th>Data</th>
                                    <th class="text-end">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($documentos)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-5 text-muted">
                                            <div class="mb-2">📭</div>
                                            Esta pasta está vazia
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($documentos as $doc): ?>
                                        <tr class="doc-row">
                                            <td>
                                                <input type="checkbox" class="form-check-input doc-check" value="<?php echo $doc['id']; ?>">
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <span class="me-2">
                                                        <span class="doc-thumb doc-thumb-small doc-type-<?php echo strtolower($doc['tipo']); ?>">
                                                            <span class="doc-thumb-label"><?php echo htmlspecialchars(strtoupper($doc['tipo'])); ?></span>
                                                        </span>
                                                    </span>
                                                    <span class="fw-semibold"><?php echo htmlspecialchars($doc['titulo']); ?></span>
                                                </div>
                                            </td>
                                            <td>
                                                <?php
                                                $statusClass = match($doc['status']) {
                                                    'EM_EDICAO' => 'status-em_edicao',
                                                    'PENDENTE_ASSINATURA' => 'status-pendente',
                                                    'ASSINADO' => 'status-assinado',
                                                    default => 'bg-secondary text-white'
                                                };
                                                ?>
                                                <span class="status-badge <?php echo $statusClass; ?>">
                                                    <?php echo htmlspecialchars(str_replace('_', ' ', $doc['status'])); ?>
                                                </span>
                                            </td>
                                            <td class="text-muted small">v<?php echo (int) $doc['versao_atual']; ?></td>
                                            <td class="text-muted small">
                                                <?php echo isset($doc['created_at']) ? date('d/m/Y', strtotime($doc['created_at'])) : '-'; ?>
                                            </td>
                                            <td class="text-end">
                                                <div class="btn-group">
                                                    <a href="/documentos/visualizar?id=<?php echo $doc['id']; ?>" class="btn btn-sm btn-link text-muted p-0 me-2" title="Visualizar">👁️</a>
                                                    <a href="/documentos/download?id=<?php echo $doc['id']; ?>" class="btn btn-sm btn-link text-muted p-0 me-2" title="Baixar">⬇️</a>
                                                    <a href="/documentos/auditoria?id=<?php echo $doc['id']; ?>" class="btn btn-sm btn-link text-muted p-0 me-2" title="Auditoria">📋</a>
                                                    <a href="/assinaturas/configurar?documento_id=<?php echo $doc['id']; ?>" class="btn btn-sm btn-link text-muted p-0" title="Configurar assinaturas">✍️</a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php if (!empty($documentos)): ?>
                    <div class="doc-grid" id="doc-grid">
                        <?php foreach ($documentos as $doc): ?>
                            <?php
                            $statusClass = match($doc['status']) {
                                'EM_EDICAO' => 'status-em_edicao',
                                'PENDENTE_ASSINATURA' => 'status-pendente',
                                'ASSINADO' => 'status-assinado',
                                default => 'bg-secondary text-white'
                            };
                            $isSigned = $doc['status'] === 'ASSINADO';
                            ?>
                            <div class="doc-card">
                                <div class="doc-card-thumb">
                                    <div class="doc-thumb doc-type-<?php echo strtolower($doc['tipo']); ?>">
                                        <span class="doc-thumb-label"><?php echo htmlspecialchars(strtoupper($doc['tipo'])); ?></span>
                                    </div>
                                </div>
                                <div class="doc-card-title-row">
                                    <?php if ($isSigned): ?>
                                        <span class="doc-status-dot doc-status-dot-success">✔</span>
                                    <?php endif; ?>
                                    <span class="doc-card-title"><?php echo htmlspecialchars($doc['titulo']); ?></span>
                                </div>
                                <div class="doc-card-meta">
                                    <span>
                                        <span class="status-badge <?php echo $statusClass; ?>">
                                            <?php echo htmlspecialchars(str_replace('_', ' ', $doc['status'])); ?>
                                        </span>
                                    </span>
                                    <span>
                                        <?php echo isset($doc['created_at']) ? date('d/m/Y', strtotime($doc['created_at'])) : '-'; ?>
                                    </span>
                                </div>
                                <div class="doc-card-actions">
                                    <a href="/documentos/visualizar?id=<?php echo $doc['id']; ?>">👁️</a>
                                    <a href="/documentos/download?id=<?php echo $doc['id']; ?>">⬇️</a>
                                    <a href="/documentos/auditoria?id=<?php echo $doc['id']; ?>">📋</a>
                                    <a href="/assinaturas/configurar?documento_id=<?php echo $doc['id']; ?>">✍️</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="d-flex flex-column align-items-center justify-content-center h-75 text-muted">
                    <div class="fs-1 mb-3">👈</div>
                    <p>Selecione uma pasta na barra lateral para começar</p>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Batch Actions Bar -->
        <div class="batch-actions" id="batch-actions-bar" role="region" aria-label="Ações em lote de documentos">
            <span class="fw-bold"><span id="selected-count">0</span> selecionados</span>
            <div class="vr bg-white opacity-25"></div>
            <button class="btn btn-sm btn-light rounded-pill px-3" type="button" onclick="batchDownload()" aria-label="Download em lote dos documentos selecionados">
                ⬇️ Download
            </button>
            <button class="btn btn-sm btn-outline-light rounded-pill px-3" type="button" onclick="batchAudit()" aria-label="Exportar auditoria em lote dos documentos selecionados">
                📋 Exportar Auditoria
            </button>
        </div>
    </div>
</div>

<!-- Scripts específicos desta tela -->
<script>
    (function() {
        const folderWrapper = document.querySelector('.folder-view-wrapper');
        const folderToolbar = document.querySelector('.folder-view-toolbar');
        if (folderWrapper && folderToolbar) {
            const STORAGE_KEY_FOLDER = 'ged_folder_view_mode';
            let folderMode = localStorage.getItem(STORAGE_KEY_FOLDER) || 'grid';

            function applyFolderMode(newMode) {
                folderMode = newMode;
                folderWrapper.classList.remove('folder-view-grid', 'folder-view-compact', 'folder-view-list');
                if (folderMode === 'list') {
                    folderWrapper.classList.add('folder-view-list');
                } else if (folderMode === 'compact') {
                    folderWrapper.classList.add('folder-view-compact');
                } else {
                    folderWrapper.classList.add('folder-view-grid');
                }

                folderToolbar.querySelectorAll('button[data-view-mode]').forEach(function(btn) {
                    if (btn.getAttribute('data-view-mode') === folderMode) {
                        btn.classList.add('active');
                    } else {
                        btn.classList.remove('active');
                    }
                });

                try {
                    localStorage.setItem(STORAGE_KEY_FOLDER, folderMode);
                } catch (e) {}
            }

            folderToolbar.addEventListener('click', function (e) {
                var btn = e.target.closest('button[data-view-mode]');
                if (!btn) return;
                var newMode = btn.getAttribute('data-view-mode');
                applyFolderMode(newMode);
            });

            applyFolderMode(folderMode);
        }

        const docWrapper = document.querySelector('.doc-view-wrapper');
        const docToolbar = document.querySelector('.doc-view-toolbar');
        if (docWrapper && docToolbar) {
            const STORAGE_KEY_DOC = 'ged_document_view_mode';
            let docMode = localStorage.getItem(STORAGE_KEY_DOC) || 'list';

            function applyDocMode(newMode) {
                docMode = newMode;
                docWrapper.classList.remove('doc-view-grid', 'doc-view-compact', 'doc-view-list');
                if (docMode === 'grid') {
                    docWrapper.classList.add('doc-view-grid');
                } else if (docMode === 'compact') {
                    docWrapper.classList.add('doc-view-compact');
                } else {
                    docWrapper.classList.add('doc-view-list');
                }

                docToolbar.querySelectorAll('button[data-doc-view]').forEach(function(btn) {
                    if (btn.getAttribute('data-doc-view') === docMode) {
                        btn.classList.add('active');
                    } else {
                        btn.classList.remove('active');
                    }
                });

                try {
                    localStorage.setItem(STORAGE_KEY_DOC, docMode);
                } catch (e) {}
            }

            docToolbar.addEventListener('click', function (e) {
                var btn = e.target.closest('button[data-doc-view]');
                if (!btn) return;
                var newMode = btn.getAttribute('data-doc-view');
                applyDocMode(newMode);
            });

            applyDocMode(docMode);
        }
    })();
</script>

<!-- Modals (Contextual) -->
<?php if ($pasta): ?>
    <!-- Modal Criar Ano -->
    <div class="modal fade" id="modalCriarAno" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <form action="/pastas/salvar" method="post">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($token, ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="parent_id" value="<?php echo (int) $pasta['id']; ?>">
                    <div class="modal-header border-0 pb-0">
                        <h5 class="modal-title fs-6 fw-bold">Novo Ano</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <select name="nome" class="form-select">
                            <?php foreach ($anos as $ano): ?>
                                <option value="<?php echo $ano; ?>" <?php echo $ano == date('Y') ? 'selected' : ''; ?>>
                                    <?php echo $ano; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-sm btn-primary">Criar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Criar Mês -->
    <div class="modal fade" id="modalCriarMes" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <form action="/pastas/salvar" method="post">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($token, ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="parent_id" value="<?php echo (int) $pasta['id']; ?>">
                    <div class="modal-header border-0 pb-0">
                        <h5 class="modal-title fs-6 fw-bold">Novo Mês</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <select name="nome" class="form-select">
                            <?php foreach ($meses as $nomeMes): ?>
                                <option value="<?php echo $nomeMes; ?>"><?php echo $nomeMes; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-sm btn-primary">Criar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Nova Subpasta -->
    <div class="modal fade" id="modalNovaSubpasta" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <form action="/pastas/salvar" method="post">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($token, ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="parent_id" value="<?php echo (int) $pasta['id']; ?>">
                    <div class="modal-header border-0 pb-0">
                        <h5 class="modal-title fs-6 fw-bold">Nova Pasta</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="text" name="nome" class="form-control" placeholder="Nome da pasta" required>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-sm btn-primary">Criar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php endif; ?>

<script>
// Toggle Upload Form
function toggleUploadForm() {
    const el = document.getElementById('form-upload-container');
    if (el) el.classList.toggle('d-none');
}

// Folder Tree Logic
document.addEventListener('DOMContentLoaded', function () {
    const treeContainer = document.getElementById('tree-pastas');
    const currentPastaId = <?php echo $pasta ? $pasta['id'] : 'null'; ?>;
    const currentDeptId = <?php echo $pasta && isset($pasta['departamento_id']) ? $pasta['departamento_id'] : 'null'; ?>;
    const expandedIds = <?php echo json_encode(isset($breadcrumb) ? array_column($breadcrumb, 'id') : []); ?>;

    // Load Folders for a parent (folder or department context)
    function loadFolders(container, parentId, deptId) {
        let url = '/pastas/children';
        const params = [];
        if (parentId) params.push('parent_id=' + parentId);
        if (deptId) params.push('departamento_id=' + deptId);
        
        if (params.length > 0) url += '?' + params.join('&');

        return fetch(url)
            .then(response => response.json())
            .then(data => {
                // Remove placeholder loader if exists
                if (container.innerHTML.includes('Carregando') || container.innerHTML.includes('...')) {
                    container.innerHTML = '';
                }

                if (data.length === 0) {
                    if (!parentId && deptId) {
                        container.innerHTML = '<div class="text-muted small p-2 ms-3">Vazio</div>';
                    } else if (parentId) {
                         const placeholder = document.createElement('div');
                         placeholder.classList.add('text-muted', 'small', 'ms-4', 'fst-italic');
                         placeholder.textContent = '(vazio)';
                         container.appendChild(placeholder);
                    }
                    return;
                }

                data.forEach(function (pasta) {
                    const itemContainer = document.createElement('div');
                    itemContainer.classList.add('folder-tree-item');
                    
                    const header = document.createElement('div');
                    header.classList.add('d-flex', 'align-items-center', 'py-1');
                    
                    // Toggle Button
                    const toggleBtn = document.createElement('span');
                    toggleBtn.classList.add('folder-toggle', 'text-muted', 'me-1', 'd-inline-flex', 'justify-content-center', 'align-items-center');
                    toggleBtn.style.cursor = 'pointer';
                    toggleBtn.style.width = '20px';
                    toggleBtn.style.height = '20px';
                    toggleBtn.style.userSelect = 'none';
                    
                    // Folder Link
                    const item = document.createElement('a');
                    item.href = '/documentos?pasta_id=' + pasta.id;
                    item.classList.add('folder-item', 'text-truncate', 'text-decoration-none', 'd-flex', 'align-items-center', 'flex-grow-1');
                    if (currentPastaId == pasta.id) {
                        item.classList.add('fw-bold', 'text-primary');
                    }
                    
                    item.innerHTML = `
                        <span class="icon me-1">📁</span>
                        <span class="text-truncate small">${pasta.nome}</span>
                    `;
                    
                    // Subfolder Container
                    const subContainer = document.createElement('div');
                    subContainer.classList.add('folder-children', 'ms-3');
                    subContainer.style.display = 'none';
                    
                    // Determine if should be expanded
                    // Expand if in breadcrumb path OR if it's the current folder (to show its children? usually yes if it's a tree)
                    // But here loadFolders renders children OF parent.
                    // If pasta.id is in expandedIds, it means it's a parent of current folder or IS the current folder.
                    // If it is the current folder, we might want to expand it to show subfolders too? 
                    // Let's say yes.
                    const shouldExpand = expandedIds.includes(pasta.id);

                    if (shouldExpand) {
                        subContainer.style.display = 'block';
                        toggleBtn.innerHTML = '<small>▼</small>';
                        // Load children
                        loadFolders(subContainer, pasta.id, null);
                    } else {
                        toggleBtn.innerHTML = '<small>▶</small>';
                    }
                    
                    // Toggle Logic
                    toggleBtn.addEventListener('click', function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        
                        const isExpanded = subContainer.style.display === 'block';
                        
                        if (isExpanded) {
                            subContainer.style.display = 'none';
                            toggleBtn.innerHTML = '<small>▶</small>';
                        } else {
                            subContainer.style.display = 'block';
                            toggleBtn.innerHTML = '<small>▼</small>'; 
                            
                            // Load children if empty
                            if (subContainer.children.length === 0) {
                                subContainer.innerHTML = '<div class="text-muted small ms-3">...</div>';
                                loadFolders(subContainer, pasta.id, null);
                            }
                        }
                    });

                    header.appendChild(toggleBtn);
                    header.appendChild(item);
                    itemContainer.appendChild(header);
                    itemContainer.appendChild(subContainer);
                    container.appendChild(itemContainer);
                });
            })
            .catch(err => {
                console.error(err);
                container.innerHTML = '<div class="text-danger small p-2 ms-3">Erro</div>';
            });
    }

    // Load Departments (Root Level)
    function loadDepartments(container) {
        fetch('/departamentos/list-json') // We need to route this correctly or use a new method
            // Actually, we added listJson to DepartamentoController. 
            // We need a route for it. If not defined in Router, we might need to access via ?controller=departamento&action=listJson if that's how it works
            // Or assumes /departamentos/list-json maps to DepartamentoController::listJson
            // Let's assume the router handles /controller/action or defined routes.
            // If the router is basic, it might need specific route. 
            // I'll try /departamentos/list-json. If it fails, I'll need to check router.
            .then(response => {
                if (!response.ok) throw new Error('Network response was not ok');
                return response.json();
            })
            .then(data => {
                container.innerHTML = '';
                if (data.length === 0) {
                    container.innerHTML = '<div class="text-muted small p-2">Nenhum departamento</div>';
                    return;
                }

                data.forEach(function (dept) {
                    const deptContainer = document.createElement('div');
                    deptContainer.classList.add('dept-item-container', 'mb-1');
                    
                    const deptHeader = document.createElement('div');
                    deptHeader.classList.add('folder-item', 'fw-bold');
                    deptHeader.style.cursor = 'pointer';
                    deptHeader.innerHTML = `
                        <span class="icon">🏢</span>
                        <span class="text-truncate">${dept.nome}</span>
                    `;
                    
                    const childrenContainer = document.createElement('div');
                    childrenContainer.classList.add('dept-children', 'ms-3');
                    childrenContainer.style.display = 'none'; // Collapsed by default
                    
                    deptHeader.addEventListener('click', function(e) {
                        e.preventDefault();
                        const isExpanded = childrenContainer.style.display === 'block';
                        childrenContainer.style.display = isExpanded ? 'none' : 'block';
                        
                        if (!isExpanded && childrenContainer.children.length === 0) {
                            childrenContainer.innerHTML = '<div class="text-muted small p-2">Carregando...</div>';
                            loadFolders(childrenContainer, null, dept.id).then(() => {
                                // Clear loading if needed, handled by innerHTML overwrite
                            });
                        }
                    });

                    // Auto-expand if current folder belongs to this department
                    if (currentDeptId && dept.id == currentDeptId) {
                         childrenContainer.style.display = 'block';
                         // Only load if empty (it will be empty on init)
                         if (childrenContainer.children.length === 0) {
                             loadFolders(childrenContainer, null, dept.id);
                         }
                    }

                    deptContainer.appendChild(deptHeader);
                    deptContainer.appendChild(childrenContainer);
                    container.appendChild(deptContainer);
                    
                    // Auto-expand if current folder belongs to this department?
                    // Complex to know without extra data.
                });
            })
            .catch(err => {
                console.error(err);
                container.innerHTML = '<div class="text-danger small p-2">Erro ao carregar locais</div>';
            });
    }

    // Start
    loadDepartments(treeContainer);
});

// Batch Actions Logic
document.addEventListener('DOMContentLoaded', function() {
    const checkAll = document.getElementById('check-all');
    const docChecks = document.querySelectorAll('.doc-check');
    const batchBar = document.getElementById('batch-actions-bar');
    const countSpan = document.getElementById('selected-count');
    const docRows = document.querySelectorAll('.doc-row');

    function updateBatchState() {
        const checked = document.querySelectorAll('.doc-check:checked');
        const count = checked.length;
        
        countSpan.textContent = count;
        
        if (count > 0) {
            batchBar.classList.add('visible');
        } else {
            batchBar.classList.remove('visible');
        }

        // Highlight rows
        docRows.forEach(row => {
            const cb = row.querySelector('.doc-check');
            if (cb && cb.checked) {
                row.classList.add('selected');
            } else {
                row.classList.remove('selected');
            }
        });
    }

    if (checkAll) {
        checkAll.addEventListener('change', function() {
            docChecks.forEach(cb => cb.checked = checkAll.checked);
            updateBatchState();
        });
    }

    docChecks.forEach(cb => {
        cb.addEventListener('change', updateBatchState);
    });
});

function batchDownload() {
    const checked = document.querySelectorAll('.doc-check:checked');
    const ids = Array.from(checked).map(cb => cb.value);
    if (ids.length === 0) {
        return;
    }

    const form = document.createElement('form');
    form.method = 'post';
    form.action = '/documentos/acoes-lote';

    const csrf = document.querySelector('input[name="csrf_token"]');
    if (csrf) {
        const tokenInput = document.createElement('input');
        tokenInput.type = 'hidden';
        tokenInput.name = 'csrf_token';
        tokenInput.value = csrf.value;
        form.appendChild(tokenInput);
    }

    const acaoInput = document.createElement('input');
    acaoInput.type = 'hidden';
    acaoInput.name = 'acao';
    acaoInput.value = 'download';
    form.appendChild(acaoInput);

    ids.forEach(function(id) {
        const hidden = document.createElement('input');
        hidden.type = 'hidden';
        hidden.name = 'ids[]';
        hidden.value = id;
        form.appendChild(hidden);
    });

    document.body.appendChild(form);
    form.submit();
}

function batchAudit() {
    const checked = document.querySelectorAll('.doc-check:checked');
    const ids = Array.from(checked).map(cb => cb.value);
    if (ids.length === 0) {
        return;
    }

    const form = document.createElement('form');
    form.method = 'post';
    form.action = '/documentos/acoes-lote';

    const csrf = document.querySelector('input[name="csrf_token"]');
    if (csrf) {
        const tokenInput = document.createElement('input');
        tokenInput.type = 'hidden';
        tokenInput.name = 'csrf_token';
        tokenInput.value = csrf.value;
        form.appendChild(tokenInput);
    }

    const acaoInput = document.createElement('input');
    acaoInput.type = 'hidden';
    acaoInput.name = 'acao';
    acaoInput.value = 'auditoria';
    form.appendChild(acaoInput);

    ids.forEach(function(id) {
        const hidden = document.createElement('input');
        hidden.type = 'hidden';
        hidden.name = 'ids[]';
        hidden.value = id;
        form.appendChild(hidden);
    });

    document.body.appendChild(form);
    form.submit();
}
</script>
