<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h1 class="h3"><?php echo htmlspecialchars($documento['titulo'], ENT_QUOTES, 'UTF-8'); ?></h1>
        <p class="text-muted small mb-0">Versão: <?php echo (int) $documento['versao_atual']; ?> | Status: <?php echo htmlspecialchars($documento['status'], ENT_QUOTES, 'UTF-8'); ?></p>
    </div>
    <div>
        <a href="/assinaturas/configurar?documento_id=<?php echo $documento['id']; ?>" class="btn btn-outline-secondary me-2">Configurar assinaturas</a>
        <a href="/documentos/download?id=<?php echo $documento['id']; ?>" class="btn btn-outline-primary me-2">Download</a>
        <a href="/documentos?pasta_id=<?php echo $documento['pasta_id']; ?>" class="btn btn-secondary">Voltar</a>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header pb-0">
        <ul class="nav nav-tabs card-header-tabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="tab-arquivo" data-bs-toggle="tab" data-bs-target="#tab-pane-arquivo" type="button" role="tab">
                    Documento
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link<?php echo $ocr ? '' : ' disabled'; ?>" id="tab-ocr" data-bs-toggle="tab" data-bs-target="#tab-pane-ocr" type="button" role="tab">
                    Texto OCR
                </button>
            </li>
        </ul>
    </div>
    <div class="card-body p-0">
        <div class="tab-content">
            <div class="tab-pane fade show active" id="tab-pane-arquivo" role="tabpanel">
                <div class="ratio" style="--bs-aspect-ratio: 100%; height: 75vh;">
                    <iframe src="/documentos/stream?id=<?php echo $documento['id']; ?>" style="border:0;" allowfullscreen></iframe>
                </div>
            </div>
            <div class="tab-pane fade" id="tab-pane-ocr" role="tabpanel">
                <?php if ($ocr): ?>
                    <div class="p-3 border-top">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div class="small text-muted">
                                Idioma: <?php echo htmlspecialchars($ocr['idioma'], ENT_QUOTES, 'UTF-8'); ?> |
                                Engine: <?php echo htmlspecialchars($ocr['engine'], ENT_QUOTES, 'UTF-8'); ?>
                                <?php if (!empty($ocr['paginas_processadas'])): ?>
                                    | Páginas: <?php echo (int) $ocr['paginas_processadas']; ?>
                                <?php endif; ?>
                            </div>
                            <?php if (!empty($ocr['created_at'])): ?>
                                <div class="small text-muted">
                                    Processado em: <?php echo date('d/m/Y H:i', strtotime($ocr['created_at'])); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="bg-light border-top" style="max-height: 60vh; overflow-y: auto;">
                            <pre class="p-3 mb-0" style="white-space: pre-wrap;"><?php echo htmlspecialchars($ocr['texto_extraido'], ENT_QUOTES, 'UTF-8'); ?></pre>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="p-4 text-muted">
                        Nenhum texto OCR disponível para esta versão do documento.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
