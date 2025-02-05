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
            error_log('Debug - Validando token: ' . $token);
            $decoded = JWT::decode($token, new Key($_ENV['JWT_SECRET'], 'HS256'));
            error_log('Debug - Token decodificado com sucesso: ' . json_encode($decoded));
            return $decoded;
        } catch (\Exception $e) {
            error_log('Debug - Erro ao validar token: ' . $e->getMessage());
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

    public function getCurrentUser(): ?User
    {
        error_log('Debug - Auth::getCurrentUser() - Iniciando');
        
        if (!isset($_COOKIE['token'])) {
            error_log('Debug - Auth::getCurrentUser() - Token não encontrado no cookie');
            return null;
        }

        try {
            error_log('Debug - Auth::getCurrentUser() - Token encontrado, validando...');
            $payload = self::validateToken($_COOKIE['token']);
            
            error_log('Debug - Auth::getCurrentUser() - Token válido, buscando usuário: ' . $payload->email);
            $userData = User::findByEmail($payload->email, $payload->tenant_id);
            
            if (!$userData) {
                error_log('Debug - Auth::getCurrentUser() - Usuário não encontrado');
                return null;
            }
            
            // Garantir que retornamos um objeto User
            if (!($userData instanceof User)) {
                error_log('Debug - Auth::getCurrentUser() - Convertendo array para objeto User');
                $user = new User($userData);
            } else {
                $user = $userData;
            }
            
            error_log('Debug - Auth::getCurrentUser() - Usuário encontrado: ' . $user->getEmail());
            return $user;
            
        } catch (\Exception $e) {
            error_log('Debug - Auth::getCurrentUser() - Erro: ' . $e->getMessage());
            return null;
        }
    }
}
