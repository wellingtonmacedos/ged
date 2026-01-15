<?php
declare(strict_types=1);

namespace App\Services;

use App\Models\DocumentoArquivo;
use App\Models\User;
use setasign\Fpdi\Tcpdf\Fpdi;
use RuntimeException;

class SignatureService
{
    private DocumentoArquivo $documentoArquivo;
    private DocumentService $documentService;
    private AuditService $auditService;

    public function __construct()
    {
        $this->documentoArquivo = new DocumentoArquivo();
        $this->documentService = new DocumentService();
        $this->auditService = new AuditService();
    }

    /**
     * Aplica assinatura visual no PDF e gera nova versão.
     */
    public function applyVisualSignature(int $documentoId, int $assinaturaId, int $usuarioId): void
    {
        // Verifica dependências
        if (!class_exists(Fpdi::class)) {
            // Se não houver biblioteca, apenas seguimos sem assinatura visual (fallback silencioso ou log)
            // Em produção, isso deveria ser um erro se a assinatura visual for obrigatória.
            // Aqui, vamos apenas logar que não foi possível aplicar o visual.
            error_log("TCPDF/FPDI não encontrados. Assinatura visual pulada para doc ID $documentoId.");
            return;
        }

        // 1. Busca arquivo atual
        $arquivoAtual = $this->documentoArquivo->findAtual($documentoId);
        if (!$arquivoAtual) {
            throw new RuntimeException("Arquivo não encontrado para documento $documentoId");
        }

        $caminhoOrigem = $arquivoAtual['caminho_arquivo'];
        if (!file_exists($caminhoOrigem)) {
            throw new RuntimeException("Arquivo físico não encontrado: $caminhoOrigem");
        }

        // Verifica se é PDF
        $mime = mime_content_type($caminhoOrigem);
        if ($mime !== 'application/pdf') {
            // Assinatura visual só suportada em PDF por enquanto
            return;
        }

        // 2. Prepara dados da assinatura
        $userModel = new User();
        $user = $userModel->find($usuarioId);
        $nomeUsuario = $user ? $user['nome'] : 'Usuário Desconhecido';
        $dataHora = date('d/m/Y H:i:s');
        $versaoNova = (int)$arquivoAtual['versao'] + 1;
        
        $textoAssinatura = "Assinado eletronicamente por:\n" . 
                           mb_strtoupper($nomeUsuario) . "\n" . 
                           "Em: $dataHora\n" . 
                           "Versão: v$versaoNova";

        try {
            // 3. Processamento do PDF com FPDI
            $pdf = new Fpdi();
            
            // Define metadados básicos
            $pdf->SetCreator('GED Institucional');
            $pdf->SetAuthor($nomeUsuario);
            $pdf->SetTitle('Documento Assinado');

            // Importa páginas
            $pageCount = $pdf->setSourceFile($caminhoOrigem);
            
            for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                $templateId = $pdf->importPage($pageNo);
                $size = $pdf->getTemplateSize($templateId);
                
                // Adiciona página com mesma orientação/tamanho
                $orientation = ($size['width'] > $size['height']) ? 'L' : 'P';
                $pdf->AddPage($orientation, [$size['width'], $size['height']]);
                $pdf->useTemplate($templateId);

                // Se for a última página, aplica assinatura no rodapé direito
                if ($pageNo === $pageCount) {
                    $pdf->SetFont('helvetica', '', 9);
                    $pdf->SetTextColor(0, 0, 128); // Azul escuro
                    
                    // Posição: Canto inferior direito, margem de ~5cm de largura
                    $x = $size['width'] - 60;
                    $y = $size['height'] - 25;
                    
                    // Desenha um box leve
                    $pdf->SetAlpha(0.1);
                    $pdf->SetFillColor(0, 0, 0);
                    $pdf->Rect($x - 2, $y - 2, 55, 20, 'F');
                    $pdf->SetAlpha(1);

                    $pdf->SetXY($x, $y);
                    $pdf->MultiCell(50, 4, $textoAssinatura, 0, 'L');
                }
            }

            // 4. Salva em arquivo temporário
            $tempDir = sys_get_temp_dir();
            $tempFile = tempnam($tempDir, 'signed_pdf_');
            $pdf->Output($tempFile, 'F');

            // 5. Gera nova versão no GED
            $this->documentService->addVersionFromPath($documentoId, $tempFile);

            // Limpa temp
            @unlink($tempFile);

            // Log específico
            $this->auditService->log('ASSINATURA_VISUAL_APLICADA', 'assinaturas', $assinaturaId);

        } catch (\Throwable $e) {
            // Log erro e relança ou ignora dependendo da política. 
            // Vamos logar e lançar para interromper o fluxo se falhar a geração visual
            error_log("Erro ao gerar assinatura visual: " . $e->getMessage());
            throw new RuntimeException("Falha ao aplicar assinatura visual no PDF: " . $e->getMessage());
        }
    }
}
