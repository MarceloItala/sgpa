<?php
declare(strict_types=1);

namespace SGPA\Middleware;

use SGPA\Core\Auth;
use SGPA\Core\TenantScope;
use SGPA\Exceptions\AuthenticationException;

class AuthMiddleware
{
    public function handle(?string $params = null): void
    {
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? '';

        if (!preg_match('/Bearer\s+(.+)/', $authHeader, $matches)) {
            throw new AuthenticationException('Token de autenticação não fornecido');
        }

        try {
            $token = $matches[1];
            $payload = Auth::validateToken($token);

            // Define o tenant atual baseado no token
            TenantScope::setTenant($payload->tenant_id);

            // Armazena o usuário atual na sessão
            $_SESSION['current_user'] = [
                'id' => $payload->uid,
                'email' => $payload->email,
                'tenant_id' => $payload->tenant_id
            ];
        } catch (\Exception $e) {
            throw new AuthenticationException('Token de autenticação inválido');
        }
    }
}
