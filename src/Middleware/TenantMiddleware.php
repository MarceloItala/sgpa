<?php
declare(strict_types=1);

namespace SGPA\Middleware;

use SGPA\Core\TenantScope;
use SGPA\Exceptions\TenantException;

class TenantMiddleware
{
    public function handle(): void
    {
        try {
            TenantScope::initFromSubdomain();
        } catch (TenantException $e) {
            http_response_code(404);
            echo json_encode(['error' => $e->getMessage()]);
            exit;
        }
    }
}
