<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h1 class="h3"><?php echo htmlspecialchars($documento['titulo'], ENT_QUOTES, 'UTF-8'); ?></h1>
        <p class="text-muted small mb-0">Versão: <?php echo (int) $documento['versao_atual']; ?> | Status: <?php echo htmlspecialchars($documento['status'], ENT_QUOTES, 'UTF-8'); ?></p>
    </div>
    <div>
        <a href="/documentos/download?id=<?php echo $documento['id']; ?>" class="btn btn-outline-primary me-2">Download</a>
        <a href="/documentos?pasta_id=<?php echo $documento['pasta_id']; ?>" class="btn btn-secondary">Voltar</a>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <div class="ratio" style="--bs-aspect-ratio: 100%; height: 75vh;">
            <iframe src="/documentos/stream?id=<?php echo $documento['id']; ?>" style="border:0;" allowfullscreen></iframe>
        </div>
    </div>
</div>
