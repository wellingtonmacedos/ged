<?php
/** @var array $kpis */
/** @var array $filtros */
?>
<div class="d-flex justify-content-between align-items-center mb-4" role="banner">
    <div>
        <h1 class="h3 mb-1">Dashboard Gerencial</h1>
        <p class="text-muted mb-0">Visão consolidada de documentos, assinaturas e OCR.</p>
    </div>
    <form class="d-flex align-items-end gap-2" method="get" action="/dashboard/gerencial" aria-label="Filtros do dashboard">
        <div>
            <label for="filtro-inicio" class="form-label form-label-sm mb-1">Início</label>
            <input type="date" id="filtro-inicio" name="inicio" class="form-control form-control-sm" value="<?php echo htmlspecialchars($filtros['inicio'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
        </div>
        <div>
            <label for="filtro-fim" class="form-label form-label-sm mb-1">Fim</label>
            <input type="date" id="filtro-fim" name="fim" class="form-control form-control-sm" value="<?php echo htmlspecialchars($filtros['fim'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
        </div>
        <div class="pt-2">
            <button type="submit" class="btn btn-sm btn-primary" aria-label="Aplicar filtros do período">Aplicar</button>
        </div>
    </form>
</div>

<div class="row g-3 mb-4" role="region" aria-label="Indicadores principais">
    <div class="col-md-3">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <p class="text-muted text-uppercase small mb-1">Documentos no período</p>
                <p class="display-6 mb-0"><?php echo (int) ($kpis['criados_periodo'] ?? 0); ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <p class="text-muted text-uppercase small mb-1">Pendentes de assinatura</p>
                <p class="display-6 mb-0"><?php echo (int) ($kpis['pendentes_assinatura'] ?? 0); ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <p class="text-muted text-uppercase small mb-1">OCR processados</p>
                <p class="display-6 mb-0"><?php echo (int) ($kpis['ocr_sucesso_falha']['sucesso'] ?? 0); ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <p class="text-muted text-uppercase small mb-1">OCR com falha</p>
                <p class="display-6 mb-0"><?php echo (int) ($kpis['ocr_sucesso_falha']['falha'] ?? 0); ?></p>
            </div>
        </div>
    </div>
</div>

<div class="row g-4" role="region" aria-label="Gráficos do dashboard">
    <div class="col-md-4">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <h2 class="h6 mb-3">Documentos por status</h2>
                <canvas id="chart-status" aria-label="Gráfico de barras de documentos por status" role="img"></canvas>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <h2 class="h6 mb-3">Evolução de assinaturas</h2>
                <canvas id="chart-assinaturas" aria-label="Gráfico de linha de documentos assinados por mês" role="img"></canvas>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <h2 class="h6 mb-3">Documentos por departamento</h2>
                <canvas id="chart-departamentos" aria-label="Gráfico de pizza de documentos por departamento" role="img"></canvas>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js" integrity="sha384-6JwB59Mf+8H8XwNttET2Ht2shy2trjt1ZH+FJg4iBgRZcoAetKJcap6yiyt7FQ2m" crossorigin="anonymous"></script>
<script>
    (function () {
        var kpis = <?php echo json_encode($kpis, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;

        var ctxStatus = document.getElementById('chart-status');
        if (ctxStatus && window.Chart) {
            var labelsStatus = Object.keys(kpis.por_status || {});
            var dataStatus = Object.values(kpis.por_status || {});
            new Chart(ctxStatus, {
                type: 'bar',
                data: {
                    labels: labelsStatus,
                    datasets: [{
                        label: 'Documentos',
                        data: dataStatus,
                        backgroundColor: '#0d6efd'
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        x: { ticks: { autoSkip: false } },
                        y: { beginAtZero: true }
                    }
                }
            });
        }

        var ctxAssin = document.getElementById('chart-assinaturas');
        if (ctxAssin && window.Chart) {
            var labelsAssin = Object.keys(kpis.assinados_periodo || {});
            var dataAssin = Object.values(kpis.assinados_periodo || {});
            new Chart(ctxAssin, {
                type: 'line',
                data: {
                    labels: labelsAssin,
                    datasets: [{
                        label: 'Documentos assinados',
                        data: dataAssin,
                        borderColor: '#198754',
                        backgroundColor: 'rgba(25,135,84,0.1)',
                        tension: 0.2
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        y: { beginAtZero: true }
                    }
                }
            });
        }

        var ctxDept = document.getElementById('chart-departamentos');
        if (ctxDept && window.Chart) {
            var labelsDept = Object.keys(kpis.por_departamento || {});
            var dataDept = Object.values(kpis.por_departamento || {});
            var colors = ['#0d6efd', '#6f42c1', '#20c997', '#fd7e14', '#dc3545', '#0dcaf0'];
            new Chart(ctxDept, {
                type: 'pie',
                data: {
                    labels: labelsDept,
                    datasets: [{
                        data: dataDept,
                        backgroundColor: colors.slice(0, dataDept.length)
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        }
    })();
</script>

