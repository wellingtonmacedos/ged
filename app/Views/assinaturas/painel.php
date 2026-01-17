<?php
use App\Core\Security;

$token = Security::csrfToken();
?>
<h1 class="h4 mb-3" id="titulo-painel-assinaturas">Painel de assinaturas</h1>

<?php if (!empty($missingLib)): ?>
    <div class="alert alert-warning" role="alert" aria-live="polite">
        <strong>Atenção:</strong> As bibliotecas de geração de PDF (TCPDF/FPDI) não foram encontradas.<br>
        A assinatura visual não será aplicada no documento.<br>
        Por favor, solicite ao administrador para rodar <code>composer install</code> no servidor.
    </div>
<?php endif; ?>

<?php if (empty($pendentes)): ?>
    <p>Não há documentos pendentes de assinatura.</p>
<?php else: ?>
    <table class="table table-sm table-striped" role="table" aria-describedby="titulo-painel-assinaturas">
        <thead class="table-light">
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
                    <form method="post" action="/assinaturas/assinar" class="d-inline me-1">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($token, ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" name="assinatura_id" value="<?php echo (int) $ass['id']; ?>">
                        <button type="submit" class="btn btn-sm btn-outline-secondary" aria-label="Assinar eletronicamente o documento <?php echo htmlspecialchars($ass['titulo'], ENT_QUOTES, 'UTF-8'); ?>">Assinar eletronicamente</button>
                    </form>
                    <form method="post" action="/assinaturas/assinar-icp" class="d-inline form-assinar-icp">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($token, ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" name="assinatura_id" value="<?php echo (int) $ass['id']; ?>">
                        <input type="hidden" name="assinatura_icp" value="">
                        <input type="hidden" name="certificado_pem" value="">
                        <button type="button" class="btn btn-sm btn-primary btn-assinar-icp" data-documento-id="<?php echo (int) $ass['documento_id']; ?>" data-assinatura-id="<?php echo (int) $ass['id']; ?>" data-usuario-id="<?php echo (int) $user['id']; ?>" aria-label="Assinar com ICP-Brasil o documento <?php echo htmlspecialchars($ass['titulo'], ENT_QUOTES, 'UTF-8'); ?>">Assinar com ICP-Brasil</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<script>
    (function () {
        function assinaturaIcpDisponivel() {
            return typeof window.assinadorIcpBrasil === 'object' && typeof window.assinadorIcpBrasil.assinar === 'function';
        }

        function construirMensagem(documentoId, assinaturaId, usuarioId) {
            return 'DOC:' + documentoId + ';ASS:' + assinaturaId + ';USER:' + usuarioId;
        }

        var botoes = document.querySelectorAll('.btn-assinar-icp');
        botoes.forEach(function (botao) {
            botao.addEventListener('click', function () {
                if (!assinaturaIcpDisponivel()) {
                    alert('Módulo de assinatura ICP-Brasil não está disponível neste navegador. Verifique a instalação do componente do certificado digital.');
                    return;
                }

                var documentoId = botao.getAttribute('data-documento-id');
                var assinaturaId = botao.getAttribute('data-assinatura-id');
                var usuarioId = botao.getAttribute('data-usuario-id');

                var mensagem = construirMensagem(documentoId, assinaturaId, usuarioId);

                window.assinadorIcpBrasil.assinar(mensagem).then(function (resultado) {
                    if (!resultado || !resultado.assinaturaBase64 || !resultado.certificadoPem) {
                        alert('Retorno inválido do módulo ICP-Brasil.');
                        return;
                    }

                    var form = botao.closest('form');
                    if (!form) {
                        return;
                    }

                    form.querySelector('input[name=\"assinatura_icp\"]').value = resultado.assinaturaBase64;
                    form.querySelector('input[name=\"certificado_pem\"]').value = resultado.certificadoPem;

                    form.submit();
                }).catch(function () {
                    alert('Erro ao realizar assinatura com ICP-Brasil.');
                });
            });
        });
    })();
</script>
