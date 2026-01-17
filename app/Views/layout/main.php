<?php
use App\Core\Auth;

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>GED Institucional</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="/css/zorin.css" rel="stylesheet">
</head>
<body class="<?php echo isset($fullScreen) && $fullScreen ? 'zorin-mode theme-light' : (($_COOKIE['gedTheme'] ?? '') === 'light' ? 'theme-light' : 'theme-dark'); ?>">
<script>
(function() {
    var stored = localStorage.getItem('gedTheme');
    var body = document.body;
    if (stored === 'light' && body.classList.contains('theme-dark')) {
        body.classList.remove('theme-dark');
        body.classList.add('theme-light');
    } else if (stored === 'dark' && !body.classList.contains('theme-dark')) {
        body.classList.add('theme-dark');
        body.classList.remove('theme-light');
    }
})();
</script>
<?php if (isset($fullScreen) && $fullScreen): ?>
    <?php echo $content; ?>
<?php else: ?>
    <?php $currentUser = Auth::check() ? Auth::user() : null; ?>
    <div class="dashboard-shell">
        <aside class="dashboard-sidebar" role="navigation" aria-label="Navegação principal">
            <div class="sidebar-header">
                <a href="/" class="sidebar-logo text-decoration-none">
                    <span class="logo-mark">G</span>
                    <span class="logo-text">GED Institucional</span>
                </a>
            </div>
            <?php if ($currentUser): ?>
                <div class="sidebar-user">
                    <div class="sidebar-avatar">
                        <span><?php echo strtoupper(substr($currentUser['nome'], 0, 1)); ?></span>
                    </div>
                    <div class="sidebar-user-info">
                        <div class="sidebar-user-name"><?php echo htmlspecialchars($currentUser['nome'], ENT_QUOTES, 'UTF-8'); ?></div>
                        <div class="sidebar-user-role text-muted small"><?php echo htmlspecialchars($currentUser['perfil'] ?? '', ENT_QUOTES, 'UTF-8'); ?></div>
                    </div>
                </div>
                <ul class="sidebar-menu list-unstyled mt-3" role="menubar">
                    <li>
                        <a href="/" class="sidebar-link" role="menuitem">Dashboard</a>
                    </li>
                    <li>
                        <a href="/documentos" class="sidebar-link" role="menuitem">Arquivos</a>
                    </li>
                    <li>
                        <a href="/assinaturas/painel" class="sidebar-link" role="menuitem">Assinaturas</a>
                    </li>
                    <li>
                        <a href="/relatorios" class="sidebar-link" role="menuitem">Relatórios</a>
                    </li>
                    <li>
                        <a href="/documentos/busca" class="sidebar-link" role="menuitem">Busca</a>
                    </li>
                    <li>
                        <a href="/departamentos" class="sidebar-link" role="menuitem">Departamentos</a>
                    </li>
                    <?php if (isset($currentUser['perfil']) && $currentUser['perfil'] === 'ADMIN_GERAL'): ?>
                        <li class="mt-2 sidebar-section-label">Administração</li>
                        <li>
                            <a href="/usuarios" class="sidebar-link" role="menuitem">Usuários</a>
                        </li>
                        <li>
                            <a href="/sistema/backups" class="sidebar-link" role="menuitem">Backups</a>
                        </li>
                    <?php endif; ?>
                </ul>
                <div class="sidebar-footer">
                    <button class="btn btn-sm btn-outline-secondary me-2" id="theme-toggle-main" type="button">Tema</button>
                    <a href="/logout" class="btn btn-sm btn-outline-danger">Sair</a>
                </div>
            <?php else: ?>
                <ul class="sidebar-menu list-unstyled mt-3" role="menubar">
                    <li>
                        <a href="/login" class="sidebar-link" role="menuitem">Entrar</a>
                    </li>
                </ul>
            <?php endif; ?>
        </aside>
        <main class="dashboard-main <?php echo (isset($layoutMode) && $layoutMode === 'full') ? 'p-0 overflow-hidden d-flex flex-column' : ''; ?>" role="main">
            <?php if (!isset($hideHeader) || !$hideHeader): ?>
            <header class="dashboard-topbar d-flex justify-content-between align-items-center mb-4">
                <div class="text-muted small">
                    Painel
                </div>
                <div class="dashboard-topbar-search">
                    <input type="text" class="form-control form-control-sm" placeholder="Buscar no sistema">
                </div>
            </header>
            <?php endif; ?>
            <div class="dashboard-content <?php echo (isset($layoutMode) && $layoutMode === 'full') ? 'flex-grow-1 d-flex flex-column' : ''; ?>">
                <?php if (isset($content)): ?>
                    <?php echo $content; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>
<?php endif; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
(function () {
    function setTheme(theme) {
        var body = document.body;
        if (!body) return;
        body.classList.remove('theme-light', 'theme-dark');
        if (theme === 'dark') {
            body.classList.add('theme-dark');
        } else {
            body.classList.add('theme-light');
        }
        try {
            localStorage.setItem('gedTheme', theme === 'dark' ? 'dark' : 'light');
            document.cookie = "gedTheme=" + (theme === 'dark' ? 'dark' : 'light') + "; path=/; max-age=31536000"; // 1 year
        } catch (e) {
        }
    }

    function detectInitialTheme() {
        var stored = null;
        try {
            stored = localStorage.getItem('gedTheme');
        } catch (e) {
        }
        if (stored === 'dark' || stored === 'light') {
            return stored;
        }
        if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
            return 'dark';
        }
        return 'light';
    }

    function toggleTheme() {
        var body = document.body;
        if (!body) return;
        var isDark = body.classList.contains('theme-dark');
        setTheme(isDark ? 'light' : 'dark');
    }

    window.gedTheme = {
        set: setTheme,
        toggle: toggleTheme
    };

    document.addEventListener('DOMContentLoaded', function () {
        setTheme(detectInitialTheme());
        var mainToggle = document.getElementById('theme-toggle-main');
        if (mainToggle) {
            mainToggle.addEventListener('click', function (e) {
                e.preventDefault();
                toggleTheme();
            });
        }
        var docsToggle = document.getElementById('theme-toggle-docs');
        if (docsToggle) {
            docsToggle.addEventListener('click', function (e) {
                e.preventDefault();
                toggleTheme();
            });
        }
    });
})();
</script>
</body>
</html>
