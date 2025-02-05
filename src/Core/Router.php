<?php
declare(strict_types=1);

namespace SGPA\Core;

use SGPA\Controllers\AdminController;
use SGPA\Controllers\AuthController;
use SGPA\Controllers\CompanyController;
use SGPA\Controllers\UserController;

class Router
{
    private array $routes = [];
    private string $basePath;
    private AdminController $adminController;
    private AuthController $authController;
    private CompanyController $companyController;
    private UserController $userController;

    public function __construct()
    {
        $this->basePath = dirname(dirname(__DIR__));
        $this->adminController = new AdminController();
        $this->authController = new AuthController();
        $this->companyController = new CompanyController();
        $this->userController = new UserController();
    }

    public function get(string $path, $handler): void
    {
        $this->addRoute('GET', $path, $handler);
    }

    public function post(string $path, $handler): void
    {
        $this->addRoute('POST', $path, $handler);
    }

    public function put(string $path, $handler): void
    {
        $this->addRoute('PUT', $path, $handler);
    }

    public function delete(string $path, $handler): void
    {
        $this->addRoute('DELETE', $path, $handler);
    }

    private function addRoute(string $method, string $path, $handler): void
    {
        // Normaliza o path removendo trailing slashes
        $path = rtrim($path, '/');
        if (empty($path)) {
            $path = '/';
        }
        $this->routes[$method][$path] = $handler;
    }

    public function route(): void
    {
        // Aplica o middleware de tenant
        $tenantMiddleware = new \SGPA\Middleware\TenantMiddleware();
        $tenantMiddleware->handle();

        $method = $_SERVER['REQUEST_METHOD'];
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/';

        // Normaliza a URI removendo trailing slashes
        $uri = $uri !== null ? rtrim($uri, '/') : '/';
        if (empty($uri)) {
            $uri = '/';
        }

        // Debug log
        if ($_ENV['APP_DEBUG']) {
            error_log("Method: $method, URI: $uri");
            error_log("Available routes for $method: " . implode(', ', array_keys($this->routes[$method] ?? [])));
        }

        // Verificar se a rota existe para o método HTTP atual
        if (!isset($this->routes[$method])) {
            $this->handleError(405, 'Método não permitido');
            return;
        }

        // Procurar por rota exata
        if (isset($this->routes[$method][$uri])) {
            $this->handleRoute($this->routes[$method][$uri]);
            return;
        }

        // Procurar por rota com parâmetros
        foreach ($this->routes[$method] as $route => $handler) {
            $pattern = preg_replace('/\{[^}]+\}/', '([^/]+)', $route);
            $pattern = "@^" . $pattern . "$@D";
            
            if (preg_match($pattern, $uri, $matches)) {
                array_shift($matches); // Remove a correspondência completa
                $this->handleRoute($handler, $matches);
                return;
            }
        }

        // Se chegou aqui, a rota não foi encontrada
        $this->handleError(404, 'Rota não encontrada');
    }

    private function handleRoute($handler, array $params = []): void
    {
        try {
            // Se o handler for um array [Controller::class, 'method']
            if (is_array($handler)) {
                $controller = new $handler[0]();
                $method = $handler[1];
                $controller->$method(...$params);
            }
            // Se o handler for uma função anônima
            elseif (is_callable($handler)) {
                $handler(...$params);
            }
            // Se o handler for uma string 'path/to/template.php'
            elseif (is_string($handler) && str_ends_with($handler, '.php')) {
                $templatePath = $this->basePath . '/templates/' . $handler;
                if (file_exists($templatePath)) {
                    include $templatePath;
                } else {
                    $this->handleError(500, 'Template não encontrado');
                }
            } else {
                $this->handleError(500, 'Handler inválido');
            }
        } catch (\Throwable $e) {
            error_log($e->getMessage());
            if ($_ENV['APP_DEBUG']) {
                $this->handleError(500, $e->getMessage());
            } else {
                $this->handleError(500, 'Erro interno do servidor');
            }
        }
    }

    private function handleError(int $code, string $message): void
    {
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode(['error' => $message]);
    }

    // Getters para os controllers
    public function getAdminController(): AdminController
    {
        return $this->adminController;
    }

    public function getAuthController(): AuthController
    {
        return $this->authController;
    }

    public function getCompanyController(): CompanyController
    {
        return $this->companyController;
    }

    public function getUserController(): UserController
    {
        return $this->userController;
    }
}
