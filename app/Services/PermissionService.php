<?php
declare(strict_types=1);

namespace App\Services;

use App\Models\Permissao;

class PermissionService
{
    private Permissao $permissao;

    public function __construct()
    {
        $this->permissao = new Permissao();
    }

    public function canView(int $usuarioId, int $pastaId, string $perfil): bool
    {
        return true;
    }

    public function canUpload(int $usuarioId, int $pastaId, string $perfil): bool
    {
        return true;
    }

    public function canEdit(int $usuarioId, int $pastaId, string $perfil): bool
    {
        return true;
    }

    public function canSign(int $usuarioId, int $pastaId, string $perfil): bool
    {
        return true;
    }

    public function canDelete(int $usuarioId, int $pastaId, string $perfil): bool
    {
        return true;
    }
}

