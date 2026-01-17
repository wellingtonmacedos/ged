<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Services\AuditService;
use App\Services\DashboardService;

class DashboardController extends Controller
{
    private DashboardService $dashboard;
    private AuditService $audit;

    public function __construct()
    {
        $this->dashboard = new DashboardService();
        $this->audit = new AuditService();
    }

    public function index(): void
    {
        if (!Auth::check()) {
            $this->redirect('/login');
        }

        $user = Auth::user();

        $this->view('dashboard/index', ['user' => $user]);
    }

    public function gerencial(): void
    {
        if (!Auth::check()) {
            $this->redirect('/login');
        }

        $user = Auth::user();

        $inicio = isset($_GET['inicio']) && $_GET['inicio'] !== '' ? $_GET['inicio'] : null;
        $fim = isset($_GET['fim']) && $_GET['fim'] !== '' ? $_GET['fim'] : null;

        $filtros = [
            'inicio' => $inicio,
            'fim' => $fim,
        ];

        $kpis = $this->dashboard->getKpis($filtros);

        $this->audit->logOperacional(
            'VISUALIZAR_DASHBOARD',
            'sistema',
            null,
            json_encode(
                [
                    'inicio' => $inicio,
                    'fim' => $fim,
                ],
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            )
        );

        $this->view('dashboard/gerencial', [
            'user' => $user,
            'kpis' => $kpis,
            'filtros' => $filtros,
        ]);
    }
}
