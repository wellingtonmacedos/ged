<?php
$title = 'Nova Pasta';
ob_start();
?>

<div class="mb-4">
    <?php if ($parentId): ?>
        <a href="/documentos?pasta_id=<?php echo $parentId; ?>" class="text-decoration-none">&larr; Voltar para Pasta</a>
    <?php else: ?>
        <a href="/pastas" class="text-decoration-none">&larr; Voltar para Lista</a>
    <?php endif; ?>
</div>

<h1>Nova Pasta</h1>

<?php if ($parentFolder): ?>
    <div class="alert alert-info">
        Criando pasta dentro de: <strong><?php echo htmlspecialchars($parentFolder['nome']); ?></strong>
    </div>
<?php else: ?>
    <div class="alert alert-warning">
        Criando pasta na <strong>Raiz</strong> do departamento.
    </div>
<?php endif; ?>

<div class="card shadow-sm mt-4" style="max-width: 600px;">
    <div class="card-body">
        <form action="/pastas/salvar" method="POST">
            <?php if ($parentId): ?>
                <input type="hidden" name="parent_id" value="<?php echo $parentId; ?>">
            <?php endif; ?>

            <div class="mb-3">
                <label for="nome" class="form-label">Nome da Pasta *</label>
                <input type="text" class="form-control" id="nome" name="nome" required autofocus placeholder="Ex: Relatórios, 2024, Janeiro...">
                <div class="form-text">Você pode criar pastas para organizar por Ano, Mês ou Categoria.</div>
            </div>
            
            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary">Criar Pasta</button>
            </div>
        </form>
    </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout/main.php';
?>
