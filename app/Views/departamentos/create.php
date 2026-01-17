

<div class="mb-4">
    <a href="/departamentos" class="text-decoration-none">&larr; Voltar para Lista</a>
</div>

<h1>Novo Departamento</h1>

<div class="card shadow-sm mt-4" style="max-width: 600px;">
    <div class="card-body">
        <form action="/departamentos/salvar" method="POST">
            <div class="mb-3">
                <label for="nome" class="form-label">Nome do Departamento *</label>
                <input type="text" class="form-control" id="nome" name="nome" required autofocus>
            </div>
            
            <div class="mb-3">
                <label for="descricao" class="form-label">Descrição</label>
                <textarea class="form-control" id="descricao" name="descricao" rows="3"></textarea>
            </div>

            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary">Salvar Departamento</button>
            </div>
        </form>
    </div>
</div>


