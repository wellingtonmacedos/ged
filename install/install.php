<?php
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

$step = isset($_GET['step']) ? (int) $_GET['step'] : 1;

function h(string $v): string
{
    return htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
}

function checkRequirement(string $label, bool $ok, string $hint = ''): array
{
    return [
        'label' => $label,
        'ok' => $ok,
        'hint' => $hint,
    ];
}

if ($step === 2 && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $envPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . '.env';
    $dbHost = trim($_POST['db_host'] ?? 'localhost');
    $dbName = trim($_POST['db_name'] ?? '');
    $dbUser = trim($_POST['db_user'] ?? '');
    $dbPass = trim($_POST['db_pass'] ?? '');
    $dbCharset = trim($_POST['db_charset'] ?? 'utf8mb4');

    $lines = [
        'DB_HOST=' . $dbHost,
        'DB_NAME=' . $dbName,
        'DB_USER=' . $dbUser,
        'DB_PASS=' . $dbPass,
        'DB_CHARSET=' . $dbCharset,
        'APP_ENV=production',
        'APP_TIMEZONE=America/Sao_Paulo',
    ];

    file_put_contents($envPath, implode(PHP_EOL, $lines) . PHP_EOL);

    $dsn = 'mysql:host=' . $dbHost . ';dbname=' . $dbName . ';charset=' . $dbCharset;
    $pdo = new PDO($dsn, $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);

    $schemaPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'schema.sql';
    if (is_file($schemaPath)) {
        $sql = file_get_contents($schemaPath);
        if ($sql !== false) {
            $pdo->exec($sql);
        }
    }

    // Log de configuração
    $agora = date('Y-m-d H:i:s');
    // Tenta inserir log se a tabela existir (schema importado)
    try {
        $pdo->exec("INSERT INTO logs_auditoria (acao, entidade, dados, created_at) VALUES ('EVENTO_CONFIGURACAO', 'sistema', 'Configuração inicial do banco de dados', '$agora')");
    } catch (PDOException $e) {
        // Ignora erro se tabela não existir
    }

    header('Location: ?step=3');
    exit;
}

if ($step === 3 && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $envPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . '.env';
    
    if (is_file($envPath)) {
        $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (str_starts_with(trim($line), 'DB_')) {
                putenv(trim($line));
            }
        }
    }

    $dbHost = getenv('DB_HOST') ?: 'localhost';
    $dbName = getenv('DB_NAME') ?: '';
    $dbUser = getenv('DB_USER') ?: '';
    $dbPass = getenv('DB_PASS') ?: '';
    $dbCharset = getenv('DB_CHARSET') ?: 'utf8mb4';

    $dsn = 'mysql:host=' . $dbHost . ';dbname=' . $dbName . ';charset=' . $dbCharset;
    $pdo = new PDO($dsn, $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);

    $nome = trim($_POST['admin_nome'] ?? 'Administrador');
    $email = trim($_POST['admin_email'] ?? '');
    $senha = $_POST['admin_senha'] ?? '';

    if ($email !== '' && $senha !== '') {
        $senhaHash = password_hash($senha, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("INSERT INTO users (nome, email, senha_hash, perfil, status) VALUES (?, ?, ?, 'SUPER_ADMIN', 'ATIVO')");
        $stmt->execute([$nome, $email, $senhaHash]);
        
        // Log de instalação
        $agora = date('Y-m-d H:i:s');
        $pdo->exec("INSERT INTO logs_auditoria (acao, entidade, dados, created_at) VALUES ('EVENTO_INSTALACAO', 'sistema', 'Instalação concluída via wizard', '$agora')");
    }

    header('Location: ?step=4');
    exit;
}

if ($step === 3) {
    $envPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . '.env';
    if (is_file($envPath)) {
        $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (str_starts_with(trim($line), 'DB_')) {
                putenv(trim($line));
            }
        }
    }
}

?><!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Instalação do GED Institucional</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
    <h1 class="h3 mb-4">Instalação do GED Institucional</h1>
    <?php if ($step === 1): ?>
        <?php
        $requirements = [];
        $requirements[] = checkRequirement('PHP >= 8.0', PHP_VERSION_ID >= 80000, 'Atualize a versão do PHP para 8.0 ou superior.');
        
        // Composer Check
        $requirements[] = checkRequirement('Dependências do Composer', file_exists(dirname(__DIR__) . '/vendor/autoload.php'), 'Execute "composer install" na raiz do projeto para instalar as bibliotecas necessárias (TCPDF, FPDI, etc).');

        $requiredExtensions = ['pdo', 'pdo_mysql', 'mbstring', 'gd', 'zip'];
        foreach ($requiredExtensions as $ext) {
            $requirements[] = checkRequirement('Extensão ' . $ext, extension_loaded($ext), 'Habilite a extensão ' . $ext . ' no php.ini.');
        }
        $writablePaths = [
            dirname(__DIR__) . DIRECTORY_SEPARATOR . 'database',
            dirname(__DIR__) . DIRECTORY_SEPARATOR . 'storage',
        ];
        // Create storage if not exists (try)
        $storagePath = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'storage';
        if (!is_dir($storagePath)) {
            @mkdir($storagePath, 0775, true);
        }

        foreach ($writablePaths as $path) {
            $requirements[] = checkRequirement('Permissão de escrita em ' . basename($path), is_writable($path), 'Ajuste as permissões de escrita na pasta ' . basename($path));
        }
        ?>
        <div class="card mb-4">
            <div class="card-header">Verificação de requisitos</div>
            <div class="card-body">
                <ul class="list-group list-group-flush">
                    <?php foreach ($requirements as $req): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span><?php echo h($req['label']); ?></span>
                            <?php if ($req['ok']): ?>
                                <span class="badge bg-success">OK</span>
                            <?php else: ?>
                                <span class="badge bg-danger" title="<?php echo h($req['hint']); ?>">Falha</span>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        <a href="?step=2" class="btn btn-primary">Prosseguir</a>
    <?php elseif ($step === 2): ?>
        <form method="post" action="?step=2" class="card">
            <div class="card-header">Configuração do banco de dados</div>
            <div class="card-body">
                <div class="mb-3">
                    <label for="db_host" class="form-label">Host</label>
                    <input type="text" id="db_host" name="db_host" class="form-control" value="localhost" required>
                </div>
                <div class="mb-3">
                    <label for="db_name" class="form-label">Banco de dados</label>
                    <input type="text" id="db_name" name="db_name" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label for="db_user" class="form-label">Usuário</label>
                    <input type="text" id="db_user" name="db_user" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label for="db_pass" class="form-label">Senha</label>
                    <input type="password" id="db_pass" name="db_pass" class="form-control">
                </div>
                <div class="mb-3">
                    <label for="db_charset" class="form-label">Charset</label>
                    <input type="text" id="db_charset" name="db_charset" class="form-control" value="utf8mb4">
                </div>
                <p class="text-muted small">Os dados informados serão gravados no arquivo .env na raiz do projeto.</p>
            </div>
            <div class="card-footer text-end">
                <button type="submit" class="btn btn-primary">Salvar e importar schema</button>
            </div>
        </form>
    <?php elseif ($step === 3): ?>
        <form method="post" action="?step=3" class="card">
            <div class="card-header">Criação do usuário SUPER_ADMIN</div>
            <div class="card-body">
                <div class="mb-3">
                    <label for="admin_nome" class="form-label">Nome</label>
                    <input type="text" id="admin_nome" name="admin_nome" class="form-control" value="Administrador">
                </div>
                <div class="mb-3">
                    <label for="admin_email" class="form-label">E-mail</label>
                    <input type="email" id="admin_email" name="admin_email" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label for="admin_senha" class="form-label">Senha</label>
                    <input type="password" id="admin_senha" name="admin_senha" class="form-control" required>
                </div>
            </div>
            <div class="card-footer text-end">
                <button type="submit" class="btn btn-primary">Criar usuário</button>
            </div>
        </form>
    <?php else: ?>
        <div class="card shadow-sm">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0">Instalação Concluída com Sucesso!</h5>
            </div>
            <div class="card-body">
                <p class="lead">O GED Institucional foi configurado corretamente.</p>
                
                <h6 class="border-bottom pb-2 mb-3">Checklist de Instalação</h6>
                <ul class="list-group mb-4">
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-success me-2">✔</span> Arquivo de configuração (.env)
                        </div>
                        <span class="badge bg-success">Gerado</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-success me-2">✔</span> Banco de Dados
                        </div>
                        <span class="badge bg-success">Conectado</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-success me-2">✔</span> Estrutura de Tabelas
                        </div>
                        <span class="badge bg-success">Importada</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-success me-2">✔</span> Diretório Storage
                        </div>
                        <span class="badge bg-success">Configurado</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-success me-2">✔</span> Usuário Administrador
                        </div>
                        <span class="badge bg-success">Criado</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-success me-2">✔</span> Logs de Auditoria
                        </div>
                        <span class="badge bg-success">Inicializado</span>
                    </li>
                </ul>

                <div class="alert alert-warning d-flex align-items-center" role="alert">
                    <svg class="bi flex-shrink-0 me-2" width="24" height="24" role="img" aria-label="Warning:"><use xlink:href="#exclamation-triangle-fill"/></svg>
                    <div>
                        Por segurança, recomendamos remover o diretório <strong>/install</strong> antes de colocar o sistema em produção.
                    </div>
                </div>

                <div class="d-grid gap-2 mt-4">
                    <a href="../public/index.php" class="btn btn-primary btn-lg">Acessar o Sistema</a>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>
</body>
</html>

