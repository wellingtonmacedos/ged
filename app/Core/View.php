<?php
declare(strict_types=1);

namespace App\Core;

class View
{
    public static function render(string $template, array $data = []): void
    {
        $basePath = Bootstrap::basePath();
        $viewPath = $basePath . '/app/Views/' . $template . '.php';
        $layoutPath = $basePath . '/app/Views/layout/main.php';

        if (!is_file($viewPath)) {
            http_response_code(500);
            echo 'View não encontrada';
            return;
        }

        extract($data);

        ob_start();
        require $viewPath;
        $content = ob_get_clean();

        require $layoutPath;
    }
}

