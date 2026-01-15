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
        if ($perfil === 'ADMIN_GERAL') {
            return true;
        }

        $perm = $this->permissao->findByUsuarioAndPasta($usuarioId, $pastaId);
        if (!$perm) {
            return false;
        }

        return (bool) $perm['pode_ver'];
    }

    public function canUpload(int $usuarioId, int $pastaId, string $perfil): bool
    {
        if ($perfil === 'ADMIN_GERAL') {
            return true;
        }

        $perm = $this->permissao->findByUsuarioAndPasta($usuarioId, $pastaId);
        if (!$perm) {
            return false;
        }

        return (bool) $perm['pode_enviar'];
    }

    public function canEdit(int $usuarioId, int $pastaId, string $perfil): bool
    {
        if ($perfil === 'ADMIN_GERAL') {
            return true;
        }

        $perm = $this->permissao->findByUsuarioAndPasta($usuarioId, $pastaId);
        if (!$perm) {
            return false;
        }

        return (bool) $perm['pode_editar'];
    }

    public function canSign(int $usuarioId, int $pastaId, string $perfil): bool
    {
        if ($perfil === 'ADMIN_GERAL') {
            return true;
        }

        $perm = $this->permissao->findByUsuarioAndPasta($usuarioId, $pastaId);
        if (!$perm) {
            return false;
        }

        return (bool) $perm['pode_assinar'];
    }

    public function canDelete(int $usuarioId, int $pastaId, string $perfil): bool
    {
        if ($perfil === 'ADMIN_GERAL') {
            return true;
        }

        $perm = $this->permissao->findByUsuarioAndPasta($usuarioId, $pastaId);
        if (!$perm) {
            return false;
        }

        return (bool) $perm['pode_excluir'];
    }
}

