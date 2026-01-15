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
    <link href="/css/zorin.css" rel="stylesheet">
</head>
<body class="<?php echo isset($fullScreen) && $fullScreen ? 'zorin-mode theme-light' : 'bg-light theme-light'; ?>">
<?php if (isset($fullScreen) && $fullScreen): ?>
    <!-- Full Screen App Layout (Handled by View) -->
    <?php echo $content; ?>
<?php else: ?>
    <!-- Standard Layout -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
    <div class="container-fluid">
        <a class="navbar-brand" href="/">GED Institucional</a>
        <div class="collapse navbar-collapse">
            <ul class="navbar-nav ms-auto">
                <?php if (Auth::check()): ?>
                    <?php $currentUser = Auth::user(); ?>
                    <li class="nav-item">
                        <a class="nav-link" href="/">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/documentos">Arquivos</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/departamentos">Departamentos</a>
                    </li>
                    <?php if ($currentUser && isset($currentUser['perfil']) && $currentUser['perfil'] === 'ADMIN_GERAL'): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="/usuarios/novo">Usuários</a>
                        </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <a class="nav-link" href="/assinaturas/painel">Assinaturas</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/documentos/busca">Busca Avançada</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#" id="theme-toggle-main">Tema</a>
                    </li>
                    <li class="nav-item ms-3">
                        <a class="nav-link text-danger" href="/logout">Sair</a>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="/login">Entrar</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
<div class="container my-4">
    <?php if (isset($content)): ?>
        <?php echo $content; ?>
    <?php endif; ?>
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
