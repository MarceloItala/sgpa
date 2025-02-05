<?php
declare(strict_types=1);

namespace SGPA\Controllers;

use SGPA\Core\Auth;
use SGPA\Core\TenantScope;
use SGPA\Models\User;
use SGPA\Models\Company;
use SGPA\Exceptions\ValidationException;

class AuthController
{
    public function login(): void
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['email']) || !isset($data['password'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Email e senha são obrigatórios']);
                return;
            }

            // Se estiver no subdomínio admin, usa o tenant administrativo
            if (TenantScope::isAdmin()) {
                $data['tenant_id'] = TenantScope::ADMIN_TENANT_ID;
                error_log('Debug - Login admin: ' . json_encode($data));
            } else if (!isset($data['tenant_id'])) {
                http_response_code(400);
                echo json_encode(['error' => 'ID do tenant é obrigatório']);
                return;
            }
            
            // Busca o usuário pelo email e tenant
            $user = User::findByEmail($data['email'], $data['tenant_id']);
            error_log('Debug - Usuário encontrado: ' . ($user ? 'sim' : 'não'));
            
            if (!$user) {
                http_response_code(401);
                echo json_encode(['error' => 'Email ou senha inválidos']);
                error_log('Debug - Usuário não encontrado');
                return;
            }

            if (!$user->verifyPassword($data['password'])) {
                http_response_code(401);
                echo json_encode(['error' => 'Email ou senha inválidos']);
                error_log('Debug - Senha incorreta');
                return;
            }
            
            if ($user->getStatus() !== 'ativo') {
                http_response_code(403);
                echo json_encode(['error' => 'Usuário inativo']);
                return;
            }
            
            // Busca o tenant
            $tenant = TenantScope::findById($user->getTenantId());
            
            if (!$tenant || $tenant->getStatus() !== 'ativo') {
                http_response_code(403);
                echo json_encode(['error' => 'Tenant inativo']);
                return;
            }
            
            // Atualiza o último login
            $user->updateLastLogin();
            
            // Define o tenant atual
            TenantScope::setTenant($user->getTenantId());
            
            // Gera o token JWT
            $token = Auth::generateToken($user);
            
            // Define o cookie com o token
            setcookie('token', $token, [
                'expires' => time() + (int)$_ENV['JWT_EXPIRATION'],
                'path' => '/',
                'domain' => '.sgpa.app.br',
                'secure' => true,
                'httponly' => true,
                'samesite' => 'Lax'
            ]);
            
            // Busca a empresa do usuário (no caso do admin, é o próprio tenant)
            $company = Company::findById($user->getTenantId());
            
            error_log('Debug - Login bem sucedido para: ' . $user->getEmail());
            
            // Retorna os dados do usuário e o token
            echo json_encode([
                'token' => $token,
                'user' => [
                    'id' => $user->getId(),
                    'name' => $user->getName(),
                    'email' => $user->getEmail(),
                    'role' => $user->getRole(),
                    'tenant_id' => $user->getTenantId(),
                    'company' => $company ? [
                        'id' => $company->getId(),
                        'name' => $company->getCorporateName()
                    ] : null
                ]
            ]);
        } catch (ValidationException $e) {
            http_response_code(400);
            echo json_encode([
                'error' => $e->getMessage(),
                'errors' => $e->getErrors()
            ]);
        } catch (\Exception $e) {
            error_log($e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Erro interno do servidor']);
        }
    }

    public function logout(): void
    {
        try {
            // Limpa o tenant atual
            TenantScope::clear();
            
            // Retorna sucesso
            echo json_encode(['message' => 'Logout realizado com sucesso']);
        } catch (\Exception $e) {
            error_log($e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Erro ao realizar logout']);
        }
    }

    public function me(): void
    {
        try {
            $headers = getallheaders();
            $authHeader = $headers['Authorization'] ?? '';
            error_log('Debug - Headers recebidos: ' . json_encode($headers));
            error_log('Debug - Authorization header: ' . $authHeader);
            
            if (!preg_match('/Bearer\s+(.+)/', $authHeader, $matches)) {
                http_response_code(401);
                echo json_encode(['error' => 'Token não fornecido']);
                return;
            }
            
            $token = $matches[1];
            error_log('Debug - Token extraído: ' . $token);
            $payload = Auth::validateToken($token);
            error_log('Debug - Payload do token: ' . json_encode($payload));
            
            $user = User::findById($payload->uid);
            error_log('Debug - Usuário encontrado: ' . ($user ? json_encode($user) : 'null'));
            
            // No caso do admin, usamos o tenant_id
            $tenantId = $user->getTenantId();
            error_log('Debug - Tenant ID do usuário: ' . $tenantId);
            $company = Company::findById($tenantId);
            
            echo json_encode([
                'user' => [
                    'id' => $user->getId(),
                    'name' => $user->getName(),
                    'email' => $user->getEmail(),
                    'role' => $user->getRole(),
                    'company' => [
                        'id' => $company->getId(),
                        'name' => $company->getCorporateName()
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            error_log($e->getMessage());
            http_response_code(401);
            echo json_encode(['error' => 'Token inválido']);
        }
    }
}
