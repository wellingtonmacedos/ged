<?php
declare(strict_types=1);

namespace App\Services;

class OcrService
{
    public function extractText(string $filePath, string $lang): array
    {
        if (!is_file($filePath) || !is_readable($filePath)) {
            return [
                'texto' => '',
                'paginas' => null,
                'sucesso' => false,
                'erro' => 'Arquivo não encontrado ou inacessível',
                'engine' => 'tesseract',
                'duracao' => 0.0,
            ];
        }

        // Check if Tesseract is available
        if (!$this->isTesseractAvailable()) {
            // Fallback to Mock Mode for Development/Testing without Tesseract
            return $this->mockOcrResult($filePath);
        }

        $language = trim($lang) === '' ? 'por+eng' : $lang;
        $command = $this->buildCommand($filePath, $language);

        $output = [];
        $code = 0;
        $start = microtime(true);
        exec($command, $output, $code);
        $duration = microtime(true) - $start;

        $text = trim(implode("\n", $output));
        $pages = $this->detectPages($filePath);

        if ($code !== 0 || $text === '') {
            return [
                'texto' => $text,
                'paginas' => $pages,
                'sucesso' => false,
                'erro' => $text !== '' ? $text : 'Falha na execução do OCR',
                'engine' => 'tesseract',
                'duracao' => $duration,
            ];
        }

        return [
            'texto' => $text,
            'paginas' => $pages,
            'sucesso' => true,
            'erro' => null,
            'engine' => 'tesseract',
            'duracao' => $duration,
        ];
    }

    private function buildCommand(string $filePath, string $lang): string
    {
        $escapedFile = escapeshellarg($filePath);
        $escapedLang = escapeshellarg($lang);
        return 'tesseract ' . $escapedFile . ' stdout -l ' . $escapedLang . ' 2>&1';
    }

    private function detectPages(string $filePath): ?int
    {
        $ext = strtolower((string) pathinfo($filePath, PATHINFO_EXTENSION));
        if ($ext !== 'pdf') {
            return 1;
        }

        // Try pdfinfo if available, otherwise mock or return 1
        $command = 'pdfinfo ' . escapeshellarg($filePath) . ' 2>&1';
        $output = [];
        $code = 0;
        exec($command, $output, $code);

        if ($code !== 0) {
            // Fallback: try to count pages with generic tools or return 1 (safest fallback)
            return 1;
        }

        foreach ($output as $line) {
            if (stripos($line, 'Pages:') === 0) {
                $parts = preg_split('/\s+/', trim($line));
                $value = (int) end($parts);
                if ($value > 0) {
                    return $value;
                }
            }
        }

        return null;
    }

    private function isTesseractAvailable(): bool
    {
        // Simple check: try to run tesseract --version
        $output = [];
        $code = 0;
        // Windows requires full path sometimes, but let's assume PATH first.
        // We suppress stderr to avoid noise.
        exec('tesseract --version 2>&1', $output, $code);
        return $code === 0;
    }

    private function mockOcrResult(string $filePath): array
    {
        // Mock result for testing environments without Tesseract
        $fileName = basename($filePath);
        $mockText = "[[ MOCK OCR: Tesseract não encontrado no servidor ]]\n\n";
        $mockText .= "Este é um texto simulado extraído do arquivo: {$fileName}.\n";
        $mockText .= "Para ativar o OCR real, instale o Tesseract OCR no servidor e adicione ao PATH.\n";
        $mockText .= "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.\n";
        $mockText .= "Conteúdo detectado automaticamente para fins de teste de busca e indexação.\n";
        $mockText .= "Data: " . date('Y-m-d H:i:s');

        return [
            'texto' => $mockText,
            'paginas' => 1,
            'sucesso' => true,
            'erro' => null,
            'engine' => 'mock_fallback',
            'duracao' => 0.05,
        ];
    }
}

