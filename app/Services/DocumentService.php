<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Config;
use App\Core\Database;
use App\Models\Documento;
use App\Models\DocumentoArquivo;
use App\Models\DocumentoMetadata;
use App\Models\DocumentoOcr;
use PDO;

class DocumentService
{
    private Documento $documento;
    private DocumentoArquivo $arquivo;
    private DocumentoMetadata $metadata;
    private AuditService $audit;
    private DocumentoOcr $documentoOcr;
    private OcrService $ocrService;

    public function __construct()
    {
        $this->documento = new Documento();
        $this->arquivo = new DocumentoArquivo();
        $this->metadata = new DocumentoMetadata();
        $this->audit = new AuditService();
        $this->documentoOcr = new DocumentoOcr();
        $this->ocrService = new OcrService();
    }

    public function createDocument(int $pastaId, string $titulo, string $tipo, int $usuarioId, array $metadados, array $arquivoUpload): int
    {
        $pdo = Database::connection();
        $pdo->beginTransaction();

        $documentoId = 0;
        $arquivoId = 0;
        $caminho = '';

        try {
            $documentoId = $this->documento->insert([
                'pasta_id' => $pastaId,
                'titulo' => $titulo,
                'tipo' => $tipo,
                'status' => 'EM_EDICAO',
                'versao_atual' => 1,
                'criado_por' => $usuarioId,
            ]);

            $versao = 1;
            $caminho = $this->storeFile($documentoId, $versao, $arquivoUpload);
            $hash = hash_file('sha256', $caminho);

            $arquivoId = $this->arquivo->insert([
                'documento_id' => $documentoId,
                'caminho_arquivo' => $caminho,
                'versao' => $versao,
                'hash_sha256' => $hash,
            ]);

            foreach ($metadados as $chave => $valor) {
                if ($valor === '' || $valor === null) {
                    continue;
                }
                $this->metadata->insert([
                    'documento_id' => $documentoId,
                    'chave' => $chave,
                    'valor' => (string) $valor,
                ]);
            }

            $this->audit->log('CRIAR_DOCUMENTO', 'documentos', $documentoId);

            $pdo->commit();

            return $documentoId;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }

        if ($documentoId > 0 && $arquivoId > 0 && $caminho !== '') {
            $this->runOcrSafe($documentoId, $arquivoId, $caminho);
        }
    }

    public function addVersion(int $documentoId, array $arquivoUpload): void
    {
        $this->addVersionGeneric($documentoId, $arquivoUpload, true);
    }

    public function addVersionFromPath(int $documentoId, string $localPath): void
    {
        // Simula estrutura de $_FILES para reuso, mas indicando que não é upload HTTP
        $fakeFile = [
            'name' => basename($localPath),
            'tmp_name' => $localPath,
            'error' => 0,
            'size' => filesize($localPath)
        ];
        $this->addVersionGeneric($documentoId, $fakeFile, false);
    }

    private function addVersionGeneric(int $documentoId, array $fileInfo, bool $isUploadedFile): void
    {
        // Hardening: Bloqueio de documentos assinados
        $this->checkImmutable($documentoId);

        $pdo = Database::connection();
        $pdo->beginTransaction();
        $arquivoId = 0;
        $caminho = '';


        try {
            $atual = $this->arquivo->findAtual($documentoId);
            $versao = $atual ? ((int) $atual['versao'] + 1) : 1;
            $caminho = $this->storeFileLogic($documentoId, $versao, $fileInfo, $isUploadedFile);
            $hash = hash_file('sha256', $caminho);
            $arquivoId = $this->arquivo->insert([
                'documento_id' => $documentoId,
                'caminho_arquivo' => $caminho,
                'versao' => $versao,
                'hash_sha256' => $hash,
            ]);

            $this->documento->update($documentoId, ['versao_atual' => $versao]);
            $this->audit->log('NOVA_VERSAO_DOCUMENTO', 'documentos', $documentoId);

            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }

        if ($arquivoId > 0 && $caminho !== '') {
            $this->runOcrSafe($documentoId, $arquivoId, $caminho);
        }
    }

    private function storeFile(int $documentoId, int $versao, array $arquivoUpload): string
    {
        return $this->storeFileLogic($documentoId, $versao, $arquivoUpload, true);
    }

    private function storeFileLogic(int $documentoId, int $versao, array $fileInfo, bool $isUploadedFile): string
    {
        $storagePath = Config::get('FILES_PATH', '');
        if ($storagePath === '') {
            $storagePath = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'documentos';
        }

        if (!is_dir($storagePath)) {
            mkdir($storagePath, 0775, true);
        }

        $ext = pathinfo($fileInfo['name'], PATHINFO_EXTENSION);
        $nomeBase = 'doc_' . $documentoId . '_v' . $versao . '_' . bin2hex(random_bytes(8));
        $nomeArquivo = $nomeBase . ($ext ? '.' . $ext : '');
        $destino = rtrim($storagePath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $nomeArquivo;

        if ($isUploadedFile) {
            if (!move_uploaded_file($fileInfo['tmp_name'], $destino)) {
                throw new \RuntimeException('Falha ao mover arquivo enviado');
            }
        } else {
            if (!copy($fileInfo['tmp_name'], $destino)) {
                throw new \RuntimeException('Falha ao copiar arquivo gerado');
            }
        }

        return $destino;
    }

    private function checkImmutable(int $documentoId): void
    {
        $doc = $this->documento->find($documentoId);
        if ($doc && $doc['status'] === 'ASSINADO') {
            $this->audit->log('BLOQUEIO_EDICAO_DOCUMENTO_ASSINADO', 'documentos', $documentoId);
            throw new \RuntimeException('Documentos assinados não podem ser modificados.');
        }
    }

    private function runOcrSafe(int $documentoId, int $arquivoId, string $caminho): void
    {
        if (!$this->shouldProcessOcr($caminho)) {
            return;
        }

        $lang = Config::get('OCR_LANG', 'por+eng') ?? 'por+eng';
        $maxPagesValue = Config::get('OCR_MAX_PAGES', '50');
        $maxPages = (int) $maxPagesValue;

        try {
            $result = $this->ocrService->extractText($caminho, $lang);
            $pages = $result['paginas'] ?? null;

            if ($maxPages > 0 && $pages !== null && $pages > $maxPages) {
                $payload = [
                    'engine' => $result['engine'] ?? 'tesseract',
                    'idioma' => $lang,
                    'paginas' => $pages,
                    'limite_paginas' => $maxPages,
                ];
                $this->audit->log('OCR_FALHA', 'documentos', $documentoId);
                $this->audit->logOperacional(
                    'OCR_FALHA',
                    'documentos',
                    $documentoId,
                    json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                );
                return;
            }

            if (!empty($result['sucesso']) && !empty($result['texto'])) {
                $this->documentoOcr->insert([
                    'documento_id' => $documentoId,
                    'documento_arquivo_id' => $arquivoId,
                    'idioma' => $lang,
                    'texto_extraido' => $result['texto'],
                    'paginas_processadas' => $pages,
                    'engine' => $result['engine'] ?? 'tesseract',
                ]);

                $payload = [
                    'engine' => $result['engine'] ?? 'tesseract',
                    'idioma' => $lang,
                    'paginas' => $pages,
                    'tempo_execucao' => $result['duracao'] ?? null,
                ];

                $this->audit->log('OCR_EXECUTADO', 'documentos', $documentoId);
                $this->audit->logOperacional(
                    'OCR_EXECUTADO',
                    'documentos',
                    $documentoId,
                    json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                );
            } else {
                $payload = [
                    'engine' => $result['engine'] ?? 'tesseract',
                    'idioma' => $lang,
                    'erro' => $result['erro'] ?? 'Falha desconhecida',
                ];
                $this->audit->log('OCR_FALHA', 'documentos', $documentoId);
                $this->audit->logOperacional(
                    'OCR_FALHA',
                    'documentos',
                    $documentoId,
                    json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                );
            }
        } catch (\Throwable $e) {
            $payload = [
                'engine' => 'tesseract',
                'idioma' => $lang,
                'erro' => $e->getMessage(),
            ];
            $this->audit->log('OCR_FALHA', 'documentos', $documentoId);
            $this->audit->logOperacional(
                'OCR_FALHA',
                'documentos',
                $documentoId,
                json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            );
        }
    }

    private function shouldProcessOcr(string $caminho): bool
    {
        $ext = strtolower((string) pathinfo($caminho, PATHINFO_EXTENSION));
        $supported = ['pdf', 'png', 'jpg', 'jpeg', 'tif', 'tiff', 'bmp'];
        return in_array($ext, $supported, true);
    }
}
