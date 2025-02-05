<?php
declare(strict_types=1);

namespace SGPA;

use SGPA\Controllers\CompanyController;
use SGPA\Controllers\UserController;

class Router {
    private array $routes = [];
    private CompanyController $companyController;
    private UserController $userController;
    private AuthController $authController;

    public function __construct() {
        $this->companyController = new CompanyController();
        $this->userController = new UserController();
        $this->authController = new AuthController();
        $this->initializeRoutes();
    }

    private function initializeRoutes(): void {
        // Rotas de Páginas
        $this->addRoute('GET', '/', fn() => include 'templates/login.php');
        $this->addRoute('GET', '/login', fn() => include 'templates/login.php');
        $this->addRoute('GET', '/dashboard', fn() => include 'templates/dashboard.php');

        // API - Empresas
        $this->addRoute('GET', '/api/companies', fn() => $this->companyController->list());
        $this->addRoute('POST', '/api/companies', fn() => $this->companyController->create());
        $this->addRoute('GET', '/api/companies/{id}', fn($id) => $this->companyController->get($id));
        $this->addRoute('PUT', '/api/companies/{id}', fn($id) => $this->companyController->update($id));
        $this->addRoute('DELETE', '/api/companies/{id}', fn($id) => $this->companyController->delete($id));

        // API - Autenticação
        $this->addRoute('POST', '/api/auth/login', fn() => $this->authController->login());
        $this->addRoute('POST', '/api/auth/logout', fn() => $this->authController->logout());
        $this->addRoute('GET', '/api/auth/me', fn() => $this->authController->me());

        // API - Usuários
        $this->addRoute('GET', '/api/users', fn() => $this->userController->list());
        $this->addRoute('POST', '/api/users', fn() => $this->userController->create());
        $this->addRoute('GET', '/api/users/{id}', fn($id) => $this->userController->get($id));
        $this->addRoute('PUT', '/api/users/{id}', fn($id) => $this->userController->update($id));
        $this->addRoute('DELETE', '/api/users/{id}', fn($id) => $this->userController->delete($id));
        $this->addRoute('POST', '/api/users/reset-password', fn() => $this->userController->resetPassword());
        $this->addRoute('POST', '/api/users/confirm-reset', fn() => $this->userController->confirmPasswordReset());
    }

    private function addRoute(string $method, string $path, callable $handler): void {
        $this->routes[$method][$path] = $handler;
    }

    private function parseUrl(string $url): array {
        $parsedUrl = parse_url($url);
        return $parsedUrl ? rtrim($parsedUrl['path'], '/') : '/';
    }

    private function matchRoute(string $method, string $path): ?array {
        if (isset($this->routes[$method][$path])) {
            return ['handler' => $this->routes[$method][$path], 'params' => []];
        }

        foreach ($this->routes[$method] ?? [] as $route => $handler) {
            $pattern = preg_replace('/\{[^}]+\}/', '([^/]+)', $route);
            $pattern = str_replace('/', '\/', $pattern);
            
            if (preg_match('/^' . $pattern . '$/', $path, $matches)) {
                array_shift($matches);
                preg_match_all('/\{([^}]+)\}/', $route, $paramNames);
                
                return [
                    'handler' => $handler,
                    'params' => $matches
                ];
            }
        }

        return null;
    }

    public function route(): void {
        $method = $_SERVER['REQUEST_METHOD'];
        $path = $this->parseUrl($_SERVER['REQUEST_URI']);

        $match = $this->matchRoute($method, $path);

        if ($match) {
            header('Content-Type: application/json');
            $match['handler'](...$match['params']);
            return;
        }

        http_response_code(404);
        echo json_encode(['error' => 'Rota não encontrada']);
    }
}
?>
