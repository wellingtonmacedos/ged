<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Auth;
use App\Core\Database;

class RateLimiterService
{
    private int $maxRequests;
    private int $windowSeconds;

    public function __construct(int $maxRequests = 60, int $windowSeconds = 60)
    {
        $this->maxRequests = $maxRequests;
        $this->windowSeconds = $windowSeconds;
    }

    public function check(string $rota): void
    {
        $db = Database::connection();
        $user = Auth::user();
        $usuarioId = $user ? (int) $user['id'] : null;
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

        $agora = time();
        $windowStart = $agora - ($agora % $this->windowSeconds);
        $janela = date('Y-m-d H:i:s', $windowStart);

        $stmt = $db->prepare(
            'SELECT id, contador FROM rate_limits 
             WHERE usuario_id <=> :usuario_id 
               AND ip = :ip 
               AND rota = :rota 
               AND janela_inicio = :janela_inicio
             LIMIT 1'
        );

        $stmt->execute([
            ':usuario_id' => $usuarioId,
            ':ip' => $ip,
            ':rota' => $rota,
            ':janela_inicio' => $janela,
        ]);

        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($row) {
            if ((int) $row['contador'] >= $this->maxRequests) {
                http_response_code(429);
                echo 'Muitas requisições. Tente novamente mais tarde.';
                exit;
            }

            $upd = $db->prepare('UPDATE rate_limits SET contador = contador + 1 WHERE id = :id');
            $upd->execute([':id' => $row['id']]);
        } else {
            $ins = $db->prepare(
                'INSERT INTO rate_limits (usuario_id, ip, rota, janela_inicio, contador) 
                 VALUES (:usuario_id, :ip, :rota, :janela_inicio, 1)'
            );
            $ins->execute([
                ':usuario_id' => $usuarioId,
                ':ip' => $ip,
                ':rota' => $rota,
                ':janela_inicio' => $janela,
            ]);
        }
    }
}

