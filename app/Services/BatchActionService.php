<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Auth;
use App\Core\Database;
use App\Models\Documento;
use App\Models\DocumentoArquivo;

class BatchActionService
{
    private PermissionService $permissao;
    private AuditService $audit;
    private Documento $documento;
    private DocumentoArquivo $arquivo;
    private int $maxPorLote = 50;

    public function __construct()
    {
        $this->permissao = new PermissionService();
        $this->audit = new AuditService();
        $this->documento = new Documento();
        $this->arquivo = new DocumentoArquivo();
    }

    public function executarDownloadZip(array $ids): void
    {
        $user = Auth::user();
        if (!$user) {
            http_response_code(401);
            echo 'Não autenticado';
            return;
        }

        $ids = $this->normalizarIds($ids);
        if (empty($ids)) {
            http_response_code(400);
            echo 'Nenhum documento selecionado';
            return;
        }

        if (count($ids) > $this->maxPorLote) {
            http_response_code(400);
            echo 'Limite máximo de ' . $this->maxPorLote . ' documentos por lote excedido';
            return;
        }

        if (!class_exists(\ZipArchive::class)) {
            http_response_code(500);
            echo 'Extensão ZIP do PHP não está habilitada';
            return;
        }

        $docs = $this->buscarDocumentosAutorizados($ids, $user);
        if (empty($docs)) {
            http_response_code(403);
            echo 'Sem permissão para os documentos selecionados';
            return;
        }

        $zip = new \ZipArchive();
        $tmpDir = sys_get_temp_dir();
        $nomeZip = 'documentos_lote_' . date('Ymd_His') . '.zip';
        $caminhoZip = $tmpDir . DIRECTORY_SEPARATOR . $nomeZip;

        if ($zip->open($caminhoZip, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            http_response_code(500);
            echo 'Não foi possível gerar o arquivo ZIP';
            return;
        }

        $filesAdded = 0;

        foreach ($docs as $doc) {
            $arquivoAtual = $this->arquivo->findAtual((int) $doc['id']);
            if (!$arquivoAtual) {
                continue;
            }
            $caminho = $arquivoAtual['caminho'];
            if (!is_file($caminho)) {
                continue;
            }
            $nomeBase = $this->sanitizeFilename($doc['titulo'] ?? ('documento_' . $doc['id']));
            $ext = pathinfo($caminho, PATHINFO_EXTENSION) ?: 'pdf';
            $nomeNoZip = $nomeBase . '_v' . (int) $arquivoAtual['versao'] . '.' . $ext;
            $zip->addFile($caminho, $nomeNoZip);
            $filesAdded++;
        }

        $zip->close();

        if ($filesAdded === 0 || !is_file($caminhoZip)) {
            @unlink($caminhoZip);
            http_response_code(404);
            echo 'Nenhum arquivo disponível para download';
            return;
        }

        $this->audit->log('DOWNLOAD_LOTE_DOCUMENTOS', 'documentos', null, json_encode($ids, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        header('Content-Type: application/zip');
        header('Content-Length: ' . filesize($caminhoZip));
        header('Content-Disposition: attachment; filename="' . $nomeZip . '"');
        readfile($caminhoZip);
        @unlink($caminhoZip);
    }

    public function exportarAuditoriaLote(array $ids): void
    {
        $user = Auth::user();
        if (!$user) {
            http_response_code(401);
            echo 'Não autenticado';
            return;
        }

        $ids = $this->normalizarIds($ids);
        if (empty($ids)) {
            http_response_code(400);
            echo 'Nenhum documento selecionado';
            return;
        }

        if (count($ids) > $this->maxPorLote) {
            http_response_code(400);
            echo 'Limite máximo de ' . $this->maxPorLote . ' documentos por lote excedido';
            return;
        }

        $docs = $this->buscarDocumentosAutorizados($ids, $user);
        if (empty($docs)) {
            http_response_code(403);
            echo 'Sem permissão para os documentos selecionados';
            return;
        }

        $db = Database::connection();

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $db->prepare("
            SELECT 
                l.id,
                l.entidade_id AS documento_id,
                l.acao,
                l.entidade,
                l.usuario_id,
                l.ip,
                l.created_at,
                l.dados
            FROM logs_auditoria l
            WHERE l.entidade = 'documentos'
              AND l.entidade_id IN ($placeholders)
            ORDER BY l.entidade_id ASC, l.created_at ASC
        ");
        $stmt->execute($ids);
        $logs = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $this->audit->log('EXPORTAR_AUDITORIA_LOTE', 'documentos', null, json_encode($ids, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="auditoria_lote_' . date('Ymd_His') . '.csv"');

        $fh = fopen('php://output', 'w');
        fputcsv($fh, ['Documento ID', 'Ação', 'Entidade', 'Usuário ID', 'IP', 'Data/Hora', 'Dados']);
        foreach ($logs as $log) {
            fputcsv($fh, [
                $log['documento_id'],
                $log['acao'],
                $log['entidade'],
                $log['usuario_id'],
                $log['ip'],
                $log['created_at'],
                $log['dados'],
            ]);
        }
        fclose($fh);
    }

    private function normalizarIds(array $ids): array
    {
        $out = [];
        foreach ($ids as $id) {
            $n = (int) $id;
            if ($n > 0) {
                $out[] = $n;
            }
        }
        return array_values(array_unique($out));
    }

    private function buscarDocumentosAutorizados(array $ids, array $user): array
    {
        $permitidos = [];
        foreach ($ids as $id) {
            $doc = $this->documento->find($id);
            if (!$doc) {
                continue;
            }
            $pastaId = (int) $doc['pasta_id'];
            if ($this->permissao->canView((int) $user['id'], $pastaId, $user['perfil'])) {
                $permitidos[] = $doc;
            }
        }
        return $permitidos;
    }

    private function sanitizeFilename(string $name): string
    {
        $name = preg_replace('/[^\w\d\-]+/u', '_', $name) ?: 'documento';
        return mb_substr($name, 0, 100);
    }
}

