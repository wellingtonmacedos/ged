<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Services\AuditService;
use App\Services\AuditIntegrityService;

class TestController extends Controller
{
    public function verify(): void
    {
        if (!Auth::check()) {
            echo "Faça login primeiro.";
            return;
        }

        $user = Auth::user();
        $tenantId = isset($user['tenant_id']) ? (int) $user['tenant_id'] : 0;
        
        $integrity = new AuditIntegrityService();
        $resultado = $integrity->verifyTenantChain($tenantId);

        echo "<h1>Verificação de Integridade (Modo Teste)</h1>";
        echo "<p>Tenant ID: $tenantId</p>";
        
        $cor = $resultado['status'] === 'OK' ? 'green' : 'red';
        echo "<h2 style='color: $cor'>Status: " . $resultado['status'] . "</h2>";
        
        echo "<pre>" . json_encode($resultado, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";
        
        echo "<hr>";
        echo "<ul>";
        echo "<li><a href='/test/audit/setup'>Gerar Mais Logs (Setup)</a></li>";
        echo "<li><a href='/test/audit/corrupt'>Simular Corrupção</a></li>";
        echo "</ul>";
    }

    public function setup(): void
    {
        if (!Auth::check()) {
            echo "<h1>Teste de Auditoria</h1>";
            echo "<p>Por favor, faça login primeiro para definir o contexto do Tenant.</p>";
            echo "<p><a href='/login'>Ir para Login</a></p>";
            return;
        }

        $audit = new AuditService();
        
        // Generate some events
        $eventos = [
            ['acao' => 'TEST_SETUP_START', 'entidade' => 'system', 'dados' => 'Iniciando setup de teste'],
            ['acao' => 'TEST_CREATE_DOC', 'entidade' => 'documentos', 'entidade_id' => 999, 'dados' => 'Documento de teste criado'],
            ['acao' => 'TEST_SIGN_DOC', 'entidade' => 'documentos', 'entidade_id' => 999, 'dados' => 'Assinatura simulada'],
            ['acao' => 'TEST_SETUP_END', 'entidade' => 'system', 'dados' => 'Fim do setup de teste'],
        ];

        foreach ($eventos as $ev) {
            $audit->logAuditoria($ev['acao'], $ev['entidade'], $ev['entidade_id'] ?? null, $ev['dados']);
            // Small delay to ensure distinct timestamps if needed, 
            // though the hash chain order relies on ID/insertion order primarily.
            usleep(100000); 
        }

        echo "<h1>Setup de Teste Concluído</h1>";
        echo "<p>4 eventos de auditoria foram gerados com sucesso.</p>";
        echo "<p>A cadeia de hashes deve estar ÍNTEGRA.</p>";
        echo "<ul>";
        echo "<li><a href='/test/audit/verify'>Verificar Integridade (Modo Teste)</a></li>";
        echo "<li><a href='/sistema/auditoria/verificar'>Verificar Integridade (Oficial - Requer SUPER_ADMIN)</a></li>";
        echo "<li><a href='/test/audit/corrupt'>Simular Corrupção de Dados (Ataque)</a></li>";
        echo "</ul>";
    }

    public function corrupt(): void
    {
        if (!Auth::check()) {
            echo "Faça login primeiro.";
            return;
        }

        $user = Auth::user();
        $tenantId = $user['tenant_id'] ?? null;
        
        if ($tenantId === null) {
             echo "Usuário sem Tenant ID definido.";
             return;
        }

        $db = Database::connection();
        
        // Find the last log entry for this tenant
        $stmt = $db->prepare("SELECT id, dados FROM logs_auditoria WHERE tenant_id = :tenant_id ORDER BY id DESC LIMIT 1");
        $stmt->execute([':tenant_id' => $tenantId]);
        $log = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$log) {
            echo "Nenhum log encontrado para corromper. Execute o setup primeiro.";
            echo "<br><a href='/test/audit/setup'>Executar Setup</a>";
            return;
        }

        // Corrupt the data without updating the hash
        $dadosCorrompidos = $log['dados'] . " [DADO ALTERADO MALICIOSAMENTE]";
        
        $update = $db->prepare("UPDATE logs_auditoria SET dados = :dados WHERE id = :id");
        $update->execute([
            ':dados' => $dadosCorrompidos,
            ':id' => $log['id']
        ]);

        echo "<h1>Dados Corrompidos com Sucesso!</h1>";
        echo "<p>O log ID #" . $log['id'] . " foi alterado diretamente no banco de dados.</p>";
        echo "<p>O hash do evento NÃO foi recalculado, o que invalida a assinatura criptográfica deste evento.</p>";
        echo "<p>Ao verificar a auditoria, o sistema deve detectar 'CORROMPIDO'.</p>";
        echo "<ul>";
        echo "<li><a href='/test/audit/verify'>Verificar Integridade (Modo Teste)</a></li>";
        echo "<li><a href='/sistema/auditoria/verificar'>Verificar Integridade (Oficial)</a></li>";
        echo "<li><a href='/test/audit/setup'>Gerar Novos Logs</a> (Gerar mais logs sobre a base corrompida)</li>";
        echo "</ul>";
    }

    public function setupCommonUser(): void
    {
        $db = Database::connection();
        
        echo "<h1>Setup de Usuário Comum</h1>";

        // 0. Ensure Schema Exists
        try {
            // Departamentos
            $db->exec("CREATE TABLE IF NOT EXISTS departamentos (
                id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
                nome VARCHAR(255) NOT NULL,
                descricao TEXT NULL,
                status VARCHAR(50) NOT NULL DEFAULT 'ATIVO'
            )");

            // Pastas
            $db->exec("CREATE TABLE IF NOT EXISTS pastas (
                id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
                nome VARCHAR(255) NOT NULL,
                parent_id BIGINT UNSIGNED NULL,
                departamento_id BIGINT UNSIGNED NOT NULL,
                nivel INT UNSIGNED NOT NULL DEFAULT 0,
                created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                CONSTRAINT fk_pastas_parent FOREIGN KEY (parent_id) REFERENCES pastas(id),
                CONSTRAINT fk_pastas_departamento FOREIGN KEY (departamento_id) REFERENCES departamentos(id)
            )");

            // Permissoes
            $db->exec("CREATE TABLE IF NOT EXISTS permissoes (
                id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
                usuario_id BIGINT UNSIGNED NOT NULL,
                pasta_id BIGINT UNSIGNED NOT NULL,
                pode_ver TINYINT(1) NOT NULL DEFAULT 0,
                pode_enviar TINYINT(1) NOT NULL DEFAULT 0,
                pode_editar TINYINT(1) NOT NULL DEFAULT 0,
                pode_assinar TINYINT(1) NOT NULL DEFAULT 0,
                pode_excluir TINYINT(1) NOT NULL DEFAULT 0
            )");

            // Documentos
            $db->exec("CREATE TABLE IF NOT EXISTS documentos (
                id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
                pasta_id BIGINT UNSIGNED NOT NULL,
                titulo VARCHAR(255) NOT NULL,
                tipo VARCHAR(100) NOT NULL,
                status ENUM('EM_EDICAO','PENDENTE_ASSINATURA','ASSINADO') NOT NULL DEFAULT 'EM_EDICAO',
                versao_atual INT UNSIGNED NOT NULL DEFAULT 1,
                criado_por BIGINT UNSIGNED NOT NULL,
                created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP
            )");

            // Documentos Arquivos
            $db->exec("CREATE TABLE IF NOT EXISTS documentos_arquivos (
                id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
                documento_id BIGINT UNSIGNED NOT NULL,
                caminho_arquivo VARCHAR(500) NOT NULL,
                versao INT UNSIGNED NOT NULL,
                hash_sha256 CHAR(64) NULL,
                criado_em TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP
            )");

            // Documentos Metadados
            $db->exec("CREATE TABLE IF NOT EXISTS documentos_metadados (
                id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
                documento_id BIGINT UNSIGNED NOT NULL,
                chave VARCHAR(100) NOT NULL,
                valor TEXT NOT NULL
            )");

            // Assinaturas
            $db->exec("CREATE TABLE IF NOT EXISTS assinaturas (
                id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
                documento_id BIGINT UNSIGNED NOT NULL,
                usuario_id BIGINT UNSIGNED NOT NULL,
                ordem INT UNSIGNED NOT NULL,
                status ENUM('PENDENTE','ASSINADO') NOT NULL DEFAULT 'PENDENTE',
                assinatura_imagem VARCHAR(500) NULL,
                ip VARCHAR(50) NULL,
                assinado_em TIMESTAMP NULL
            )");

            // Logs Auditoria
            $db->exec("CREATE TABLE IF NOT EXISTS logs_auditoria (
                id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
                usuario_id BIGINT UNSIGNED NULL,
                acao VARCHAR(255) NOT NULL,
                entidade VARCHAR(100) NOT NULL,
                entidade_id BIGINT UNSIGNED NULL,
                ip VARCHAR(50) NULL,
                created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP
            )");
            
            echo "<p style='color:green'>Tabelas verificadas/criadas com sucesso.</p>";
        } catch (\PDOException $e) {
            echo "<p style='color:orange'>Aviso ao criar tabelas: " . $e->getMessage() . "</p>";
        }

        // Create Default Departments
        $depts = ['Geral', 'Financeiro', 'Administrativo'];
        $deptIds = [];

        foreach ($depts as $deptName) {
            $stmt = $db->query("SELECT id FROM departamentos WHERE nome = '$deptName' LIMIT 1");
            $dId = $stmt->fetchColumn();
            if (!$dId) {
                $stmt = $db->prepare("INSERT INTO departamentos (nome) VALUES (:nome)");
                $stmt->execute([':nome' => $deptName]);
                $dId = $db->lastInsertId();
                echo "<p>Departamento criado: $deptName (ID: $dId)</p>";
            }
            $deptIds[$deptName] = $dId;
        }
        
        $deptId = $deptIds['Geral']; // Default for user

        // Ensure users table has departamento_id
        try {
            $cols = $db->query("SHOW COLUMNS FROM users LIKE 'departamento_id'")->fetchAll();
            if (count($cols) === 0) {
                $db->exec("ALTER TABLE users ADD COLUMN departamento_id BIGINT UNSIGNED NULL AFTER perfil");
                echo "<p style='color:green'>Coluna departamento_id adicionada à tabela users.</p>";
            }
        } catch (\Exception $e) {
            // ignore
        }

        // 1. Create User
        $email = 'comum@local.com';
        $stmt = $db->prepare("SELECT id FROM users WHERE email = :email");
        $stmt->execute([':email' => $email]);
        $userId = $stmt->fetchColumn();

        if (!$userId) {
            $passHash = password_hash('password', PASSWORD_BCRYPT);
            // Check if tenant_id column exists
            $cols = $db->query("SHOW COLUMNS FROM users LIKE 'tenant_id'")->fetchAll();
            $hasTenant = count($cols) > 0;
            
            if ($hasTenant) {
                $stmt = $db->prepare("INSERT INTO users (nome, email, senha_hash, perfil, status, tenant_id, departamento_id) VALUES ('Usuário Comum', :email, :hash, 'ADMIN_GERAL', 'ATIVO', 1, :deptId)");
            } else {
                $stmt = $db->prepare("INSERT INTO users (nome, email, senha_hash, perfil, status, departamento_id) VALUES ('Usuário Comum', :email, :hash, 'ADMIN_GERAL', 'ATIVO', :deptId)");
            }
            
            $stmt->execute([':email' => $email, ':hash' => $passHash, ':deptId' => $deptId]);
            $userId = $db->lastInsertId();
            echo "<p style='color:green'>Usuário criado: $email / password (Depto ID: $deptId)</p>";
        } else {
            // Update existing user to ensure correct department and ADMIN_GERAL to see everything
            $db->prepare("UPDATE users SET departamento_id = ?, perfil = 'ADMIN_GERAL' WHERE id = ?")->execute([$deptId, $userId]);
            echo "<p style='color:blue'>Usuário já existe: $email. Atualizado para ADMIN_GERAL e Depto ID: $deptId</p>";
        }

        // 2. Create Folders for EACH Department
        $foldersTemplate = [
            'Pasta Pública' => ['view' => 1, 'upload' => 1],
            'Pasta Restrita' => ['view' => 0, 'upload' => 0]
        ];

        foreach ($deptIds as $dName => $dId) {
            foreach ($foldersTemplate as $name => $perms) {
                // Check if exists for this department
                $stmt = $db->prepare("SELECT id FROM pastas WHERE nome = :nome AND departamento_id = :deptId AND parent_id IS NULL LIMIT 1");
                $stmt->execute([':nome' => $name, ':deptId' => $dId]);
                $pastaId = $stmt->fetchColumn();

                if (!$pastaId) {
                    $stmt = $db->prepare("INSERT INTO pastas (nome, departamento_id, parent_id) VALUES (:nome, :deptId, NULL)");
                    $stmt->execute([':nome' => $name, ':deptId' => $dId]);
                    $pastaId = $db->lastInsertId();
                    echo "<p>Pasta criada: $name em $dName (ID: $pastaId)</p>";
                } else {
                    echo "<p>Pasta já existe: $name em $dName (ID: $pastaId)</p>";
                }

                // 3. Set Permissions (Only for the user created above)
                // Delete existing to be sure
                // Note: This overrides permissions, but for dev/test it's fine.
                // We only set permissions if it's the user's department OR if we want them to see it.
                // Since we made the user ADMIN_GERAL, they should see everything anyway if the check respects it.
                // But let's add explicit permissions for safety.
                
                $db->prepare("DELETE FROM permissoes WHERE usuario_id = ? AND pasta_id = ?")->execute([$userId, $pastaId]);
                
                if ($perms['view']) {
                    $stmt = $db->prepare("INSERT INTO permissoes (usuario_id, pasta_id, pode_ver, pode_enviar, pode_editar, pode_assinar, pode_excluir) VALUES (?, ?, ?, ?, 0, 0, 0)");
                    $stmt->execute([$userId, $pastaId, 1, $perms['upload']]);
                }
            }
        }
        
        echo "<hr>";
        echo "<h3>Próximos Passos:</h3>";
        echo "<ol>";
        echo "<li><a href='/logout'>Fazer Logout</a> (Saia do admin)</li>";
        echo "<li>Fazer Login com: <b>comum@local.com</b> / <b>password</b></li>";
        echo "<li>Acessar <a href='/documentos'>Documentos</a> e testar o acesso às pastas.</li>";
        echo "<li>Tentar acessar <a href='/sistema/auditoria/verificar'>Verificação de Auditoria</a> (Deve ser bloqueado).</li>";
        echo "</ol>";
    }
}
