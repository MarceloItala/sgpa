<?php
declare(strict_types=1);

namespace SGPA\Core;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use SGPA\Models\User;

class Auth
{
    public static function generateToken(User $user): string
    {
        $issuedAt = time();
        $expire = $issuedAt + (int)$_ENV['JWT_EXPIRATION'];

        $payload = [
            'iat' => $issuedAt,
            'exp' => $expire,
            'uid' => $user->getId(),
            'tenant_id' => $user->getTenantId(),
            'email' => $user->getEmail()
        ];

        return JWT::encode($payload, $_ENV['JWT_SECRET'], 'HS256');
    }

    public static function validateToken(string $token): object
    {
        try {
            return JWT::decode($token, new Key($_ENV['JWT_SECRET'], 'HS256'));
        } catch (\Exception $e) {
            throw new \RuntimeException('Token inválido: ' . $e->getMessage());
        }
    }

    public static function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_ARGON2ID);
    }

    public static function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }
}
