<?php
namespace SGPA\Controllers;

class AuthController {
    public function login() {
        // Verifica se é uma requisição POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Método não permitido']);
            return;
        }

        // Pega os dados do POST
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Valida os dados
        if (!isset($data['email']) || !isset($data['password'])) {
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Email e senha são obrigatórios']);
            return;
        }

        try {
            // Debug log
            if ($_ENV['APP_DEBUG']) {
                error_log("Tentativa de login para o email: {$data['email']}");
            }

            // Conecta ao banco de dados
            $pdo = new \PDO(
                "mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_NAME']};charset=utf8mb4",
                $_ENV['DB_USER'],
                $_ENV['DB_PASS'],
                [
                    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                    \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC
                ]
            );

            // Busca o usuário
            $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? AND deleted_at IS NULL');
            $stmt->execute([$data['email']]);
            $user = $stmt->fetch();

            // Debug log
            if ($_ENV['APP_DEBUG']) {
                error_log("Usuário encontrado: " . ($user ? 'Sim' : 'Não'));
                if ($user) {
                    error_log("Status do usuário: {$user['status']}");
                }
            }

            // Verifica se o usuário existe e a senha está correta
            if (!$user || !password_verify($data['password'], $user['password'])) {
                http_response_code(401);
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Email ou senha inválidos']);
                return;
            }

            // Verifica se o usuário está ativo
            if ($user['status'] !== 'ativo') {
                http_response_code(403);
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Usuário inativo']);
                return;
            }

            // Atualiza o último login
            $stmt = $pdo->prepare('UPDATE users SET last_login = NOW() WHERE id = ?');
            $stmt->execute([$user['id']]);

            // Busca o tenant do usuário
            $stmt = $pdo->prepare('SELECT * FROM tenants WHERE id = ?');
            $stmt->execute([$user['tenant_id']]);
            $tenant = $stmt->fetch();

            // Busca as roles do usuário
            $stmt = $pdo->prepare('
                SELECT r.* 
                FROM roles r 
                JOIN user_roles ur ON ur.role_id = r.id 
                WHERE ur.user_id = ?
            ');
            $stmt->execute([$user['id']]);
            $roles = $stmt->fetchAll();

            // Busca as permissões do usuário
            $stmt = $pdo->prepare('
                SELECT DISTINCT p.* 
                FROM permissions p 
                JOIN role_permissions rp ON rp.permission_id = p.id 
                JOIN user_roles ur ON ur.role_id = rp.role_id 
                WHERE ur.user_id = ?
            ');
            $stmt->execute([$user['id']]);
            $permissions = $stmt->fetchAll();

            // Garante que a sessão está iniciada
            if (session_status() !== PHP_SESSION_ACTIVE) {
                session_start();
            }

            // Regenera o ID da sessão por segurança
            session_regenerate_id(true);

            // Cria a sessão
            $_SESSION['user'] = [
                'id' => $user['id'],
                'name' => $user['name'],
                'email' => $user['email'],
                'tenant_id' => $user['tenant_id'],
                'tenant' => $tenant,
                'roles' => $roles,
                'permissions' => $permissions
            ];

            // Debug log
            if ($_ENV['APP_DEBUG']) {
                error_log("Sessão criada com sucesso. ID da sessão: " . session_id());
                error_log("Dados da sessão: " . json_encode($_SESSION['user']));
            }

            // Retorna os dados do usuário
            header('Content-Type: application/json');
            echo json_encode([
                'user' => [
                    'id' => $user['id'],
                    'name' => $user['name'],
                    'email' => $user['email'],
                    'tenant' => $tenant,
                    'roles' => $roles,
                    'permissions' => array_map(fn($p) => $p['name'], $permissions)
                ]
            ]);

        } catch (\PDOException $e) {
            error_log($e->getMessage());
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Erro ao autenticar usuário']);
        }
    }

    public function logout() {
        // Garante que a sessão está iniciada
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        // Limpa e destrói a sessão
        $_SESSION = [];
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }
        session_destroy();
        
        header('Content-Type: application/json');
        echo json_encode(['message' => 'Logout realizado com sucesso']);
    }
}
