<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Config;
use App\Core\Database;
use App\Models\Backup;
use PDO;

class BackupService
{
    private Backup $backup;
    private AuditService $audit;

    public function __construct()
    {
        $this->backup = new Backup();
        $this->audit = new AuditService();
    }

    public function backupBanco(string $tipo): int
    {
        return $this->backupGeneric($tipo, 'BANCO');
    }

    public function backupArquivos(string $tipo): int
    {
        return $this->backupGeneric($tipo, 'ARQUIVOS');
    }

    public function backupCompleto(string $tipo): int
    {
        return $this->backupGeneric($tipo, 'COMPLETO');
    }

    private function backupGeneric(string $tipo, string $escopo): int
    {
        $inicio = new \DateTimeImmutable('now', new \DateTimeZone('America/Sao_Paulo'));
        $backupDir = $this->resolveBackupPath();

        $filenameBase = $this->buildFilenameBase($escopo, $inicio);

        $id = $this->backup->insert([
            'tipo' => $tipo,
            'escopo' => $escopo,
            'caminho_arquivo' => '',
            'tamanho_mb' => null,
            'status' => 'SUCESSO',
            'iniciado_em' => $inicio->format('Y-m-d H:i:s'),
            'finalizado_em' => null,
            'usuario_id' => null,
            'erro' => null,
        ]);

        $db = Database::connection();
        $stmt = $db->prepare('UPDATE backups SET usuario_id = :usuario_id WHERE id = :id');
        $user = \App\Core\Auth::user();
        $usuarioId = $user ? (int) $user['id'] : null;
        $stmt->execute([
            ':usuario_id' => $usuarioId,
            ':id' => $id,
        ]);

        $erro = null;
        $files = [];

        try {
            if ($escopo === 'BANCO' || $escopo === 'COMPLETO') {
                $files[] = $this->runDatabaseDump($backupDir, $filenameBase);
            }

            if ($escopo === 'ARQUIVOS' || $escopo === 'COMPLETO') {
                $files[] = $this->runFilesBackup($backupDir, $filenameBase);
            }

            $finalPath = $this->finalizeBackupFile($backupDir, $filenameBase, $escopo, $files);

            $sizeMb = null;
            if (is_file($finalPath)) {
                $sizeMb = round(filesize($finalPath) / 1024 / 1024, 2);
            }

            $this->updateBackupResult($id, $finalPath, $sizeMb, 'SUCESSO', null, $inicio);
            $this->audit->log('EXECUTAR_BACKUP', 'backups', $id);
            $this->audit->logOperacional(
                'EXECUTAR_BACKUP',
                'backups',
                $id,
                json_encode(
                    [
                        'tipo' => $tipo,
                        'escopo' => $escopo,
                        'arquivo' => $finalPath,
                        'tamanho_mb' => $sizeMb,
                    ],
                    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                )
            );
        } catch (\Throwable $e) {
            $erro = $e->getMessage();
            $this->updateBackupResult($id, '', null, 'FALHA', $erro, $inicio);
            $this->audit->log('FALHA_BACKUP', 'backups', $id);
            $this->audit->logOperacional(
                'FALHA_BACKUP',
                'backups',
                $id,
                json_encode(
                    [
                        'tipo' => $tipo,
                        'escopo' => $escopo,
                        'erro' => $erro,
                    ],
                    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                )
            );
        }

        $this->applyRetentionPolicy();

        return $id;
    }

    private function resolveBackupPath(): string
    {
        $path = Config::get('BACKUP_PATH', '');
        if ($path === null || $path === '') {
            $path = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'backups';
        }

        if (!is_dir($path)) {
            mkdir($path, 0775, true);
        }

        return rtrim($path, DIRECTORY_SEPARATOR);
    }

    private function buildFilenameBase(string $escopo, \DateTimeImmutable $inicio): string
    {
        $prefix = 'backup';
        if ($escopo === 'BANCO') {
            $prefix = 'backup-db';
        } elseif ($escopo === 'ARQUIVOS') {
            $prefix = 'backup-files';
        } elseif ($escopo === 'COMPLETO') {
            $prefix = 'backup-completo';
        }

        return $prefix . '-' . $inicio->format('Ymd-His');
    }

    private function runDatabaseDump(string $backupDir, string $filenameBase): string
    {
        $dbHost = Config::get('DB_HOST', 'localhost') ?? 'localhost';
        $dbName = Config::get('DB_NAME', '') ?? '';
        $dbUser = Config::get('DB_USER', '') ?? '';
        $dbPass = Config::get('DB_PASS', '') ?? '';
        $dbCharset = Config::get('DB_CHARSET', 'utf8mb4') ?? 'utf8mb4';

        if ($dbName === '' || $dbUser === '') {
            throw new \RuntimeException('Configuração de banco de dados incompleta para backup');
        }

        $filePath = $backupDir . DIRECTORY_SEPARATOR . $filenameBase . '.sql.gz';

        $env = [
            'MYSQL_PWD' => $dbPass,
        ];

        $cmd = sprintf(
            'mysqldump --single-transaction --default-character-set=%s -h%s -u%s %s 2>&1',
            escapeshellarg($dbCharset),
            escapeshellarg($dbHost),
            escapeshellarg($dbUser),
            escapeshellarg($dbName)
        );

        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open($cmd, $descriptors, $pipes, null, $env);
        if (!is_resource($process)) {
            throw new \RuntimeException('Falha ao iniciar mysqldump');
        }

        fclose($pipes[0]);

        $gz = gzopen($filePath, 'wb9');
        if ($gz === false) {
            proc_terminate($process);
            throw new \RuntimeException('Falha ao abrir arquivo de saída para backup do banco');
        }

        while (!feof($pipes[1])) {
            $chunk = fread($pipes[1], 8192);
            if ($chunk === false) {
                break;
            }
            gzwrite($gz, $chunk);
        }

        gzclose($gz);

        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        $exitCode = proc_close($process);
        if ($exitCode !== 0) {
            if (is_file($filePath)) {
                unlink($filePath);
            }
            throw new \RuntimeException('mysqldump falhou: ' . $stderr);
        }

        return $filePath;
    }

    private function runFilesBackup(string $backupDir, string $filenameBase): string
    {
        $storagePath = Config::get('FILES_PATH', '');
        if ($storagePath === null || $storagePath === '') {
            $storagePath = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'documentos';
        }

        if (!is_dir($storagePath)) {
            throw new \RuntimeException('Diretório de arquivos não encontrado para backup');
        }

        $zipPath = $backupDir . DIRECTORY_SEPARATOR . $filenameBase . '.zip';

        $zip = new \ZipArchive();
        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('Falha ao criar arquivo ZIP de backup de arquivos');
        }

        $root = rtrim($storagePath, DIRECTORY_SEPARATOR);
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            if (!$file->isFile()) {
                continue;
            }

            $filePath = $file->getPathname();
            $localName = substr($filePath, strlen($root) + 1);
            $zip->addFile($filePath, $localName);
        }

        $zip->close();

        return $zipPath;
    }

    private function finalizeBackupFile(string $backupDir, string $filenameBase, string $escopo, array $files): string
    {
        $files = array_values(array_filter($files, static fn(string $f): bool => $f !== '' && is_file($f)));

        if (empty($files)) {
            throw new \RuntimeException('Nenhum artefato de backup foi gerado');
        }

        if ($escopo !== 'COMPLETO' || count($files) === 1) {
            return $files[0];
        }

        $finalZip = $backupDir . DIRECTORY_SEPARATOR . $filenameBase . '.zip';

        $zip = new \ZipArchive();
        if ($zip->open($finalZip, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('Falha ao criar arquivo ZIP de backup completo');
        }

        foreach ($files as $file) {
            $zip->addFile($file, basename($file));
        }

        $zip->close();

        return $finalZip;
    }

    private function updateBackupResult(int $id, string $path, ?float $sizeMb, string $status, ?string $erro, \DateTimeImmutable $inicio): void
    {
        $db = Database::connection();

        $stmt = $db->prepare(
            'UPDATE backups 
             SET caminho_arquivo = :caminho_arquivo,
                 tamanho_mb = :tamanho_mb,
                 status = :status,
                 erro = :erro,
                 finalizado_em = :finalizado_em
             WHERE id = :id'
        );

        $stmt->execute([
            ':caminho_arquivo' => $path,
            ':tamanho_mb' => $sizeMb,
            ':status' => $status,
            ':erro' => $erro,
            ':finalizado_em' => (new \DateTimeImmutable('now', new \DateTimeZone('America/Sao_Paulo')))->format('Y-m-d H:i:s'),
            ':id' => $id,
        ]);
    }

    private function applyRetentionPolicy(): void
    {
        $daysValue = Config::get('BACKUP_RETENTION_DAYS', '30');
        $days = (int) $daysValue;
        if ($days <= 0) {
            return;
        }

        $limitDate = new \DateTimeImmutable('-' . $days . ' days', new \DateTimeZone('America/Sao_Paulo'));

        $db = Database::connection();

        $stmt = $db->prepare('SELECT id, caminho_arquivo FROM backups WHERE iniciado_em < :limite');
        $stmt->execute([
            ':limite' => $limitDate->format('Y-m-d H:i:s'),
        ]);

        $toDelete = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($toDelete as $row) {
            $id = (int) $row['id'];
            $path = $row['caminho_arquivo'];

            if (is_string($path) && $path !== '' && is_file($path)) {
                unlink($path);
            }

            $this->audit->logOperacional(
                'EXCLUSAO_BACKUP_RETENCAO',
                'backups',
                $id,
                json_encode(
                    [
                        'caminho_arquivo' => $path,
                        'retencao_dias' => $days,
                    ],
                    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                )
            );
        }

        if (!empty($toDelete)) {
            $ids = array_map(static fn(array $row): int => (int) $row['id'], $toDelete);
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $del = $db->prepare('DELETE FROM backups WHERE id IN (' . $placeholders . ')');
            foreach ($ids as $i => $id) {
                $del->bindValue($i + 1, $id, PDO::PARAM_INT);
            }
            $del->execute();
        }
    }
}

