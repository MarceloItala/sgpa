<?php
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

// Carrega as variáveis de ambiente
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// Configuração de erro para desenvolvimento
if ($_ENV['APP_DEBUG'] ?? false) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}

// Configuração do fuso horário
date_default_timezone_set('America/Sao_Paulo');

// Configuração de sessão
session_start([
    'cookie_httponly' => true,
    'cookie_secure' => true,
    'cookie_samesite' => 'Lax',
    'use_strict_mode' => true
]);

// Handler para erros não capturados
set_exception_handler(function (\Throwable $e) {
    error_log($e->getMessage());
    http_response_code(500);
    if ($_ENV['APP_DEBUG'] ?? false) {
        header('Content-Type: application/json');
        echo json_encode([
            'error' => 'Erro interno do servidor',
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Erro interno do servidor']);
    }
    exit;
});

// Inicializa o roteador
$router = new SGPA\Core\Router();

// Middleware de autenticação
$auth = function() {
    if (!isset($_SESSION['user'])) {
        if (strpos($_SERVER['REQUEST_URI'], '/api/') === 0) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Não autorizado']);
            exit;
        } else {
            header('Location: /login');
            exit;
        }
    }
};

// Rotas públicas
$router->get('/', 'login.php');
$router->get('/login', 'login.php');
$router->post('/api/auth/login', [SGPA\Controllers\AuthController::class, 'login']);
$router->post('/api/auth/logout', [SGPA\Controllers\AuthController::class, 'logout']);

// Rotas protegidas
$router->get('/dashboard', function() use ($auth) {
    $auth();
    include __DIR__ . '/../templates/dashboard.php';
});

// Rotas de usuários
$router->get('/api/users', function() use ($auth) {
    $auth();
    (new SGPA\Controllers\UserController())->index();
});
$router->post('/api/users', function() use ($auth) {
    $auth();
    (new SGPA\Controllers\UserController())->create();
});
$router->get('/api/users/{id}', function($id) use ($auth) {
    $auth();
    (new SGPA\Controllers\UserController())->show($id);
});
$router->put('/api/users/{id}', function($id) use ($auth) {
    $auth();
    (new SGPA\Controllers\UserController())->update($id);
});
$router->delete('/api/users/{id}', function($id) use ($auth) {
    $auth();
    (new SGPA\Controllers\UserController())->delete($id);
});

// Rotas de clientes
$router->get('/api/clients', function() use ($auth) {
    $auth();
    (new SGPA\Controllers\ClientController())->index();
});
$router->post('/api/clients', function() use ($auth) {
    $auth();
    (new SGPA\Controllers\ClientController())->create();
});
$router->get('/api/clients/{id}', function($id) use ($auth) {
    $auth();
    (new SGPA\Controllers\ClientController())->show($id);
});
$router->put('/api/clients/{id}', function($id) use ($auth) {
    $auth();
    (new SGPA\Controllers\ClientController())->update($id);
});
$router->delete('/api/clients/{id}', function($id) use ($auth) {
    $auth();
    (new SGPA\Controllers\ClientController())->delete($id);
});

// Rotas de processos aduaneiros
$router->get('/api/processes', function() use ($auth) {
    $auth();
    (new SGPA\Controllers\CustomsProcessController())->index();
});
$router->post('/api/processes', function() use ($auth) {
    $auth();
    (new SGPA\Controllers\CustomsProcessController())->create();
});
$router->get('/api/processes/{id}', function($id) use ($auth) {
    $auth();
    (new SGPA\Controllers\CustomsProcessController())->show($id);
});
$router->put('/api/processes/{id}', function($id) use ($auth) {
    $auth();
    (new SGPA\Controllers\CustomsProcessController())->update($id);
});
$router->delete('/api/processes/{id}', function($id) use ($auth) {
    $auth();
    (new SGPA\Controllers\CustomsProcessController())->delete($id);
});

// Rotas de andamentos
$router->get('/api/processes/{id}/updates', function($id) use ($auth) {
    $auth();
    (new SGPA\Controllers\ProcessUpdateController())->index($id);
});
$router->post('/api/processes/{id}/updates', function($id) use ($auth) {
    $auth();
    (new SGPA\Controllers\ProcessUpdateController())->create($id);
});
$router->put('/api/processes/{id}/updates/{updateId}', function($id, $updateId) use ($auth) {
    $auth();
    (new SGPA\Controllers\ProcessUpdateController())->update($id, $updateId);
});
$router->delete('/api/processes/{id}/updates/{updateId}', function($id, $updateId) use ($auth) {
    $auth();
    (new SGPA\Controllers\ProcessUpdateController())->delete($id, $updateId);
});

// Processa a rota
$router->route();
