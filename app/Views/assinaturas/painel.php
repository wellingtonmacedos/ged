<?php
use App\Core\Security;

$token = Security::csrfToken();
?>
<h1 class="h4 mb-3">Painel de assinaturas</h1>
<?php if (empty($pendentes)): ?>
    <p>Não há documentos pendentes de assinatura.</p>
<?php else: ?>
    <table class="table table-sm table-striped">
        <thead>
        <tr>
            <th>Documento</th>
            <th>Ordem</th>
            <th>Status</th>
            <th>Ação</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($pendentes as $ass): ?>
            <tr>
                <td><?php echo htmlspecialchars($ass['titulo'], ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?php echo (int) $ass['ordem']; ?></td>
                <td><?php echo htmlspecialchars($ass['status'], ENT_QUOTES, 'UTF-8'); ?></td>
                <td>
                    <form method="post" action="/assinaturas/assinar" class="d-inline">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($token, ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" name="assinatura_id" value="<?php echo (int) $ass['id']; ?>">
                        <button type="submit" class="btn btn-sm btn-primary">Assinar</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

