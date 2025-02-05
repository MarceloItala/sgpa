<?php
declare(strict_types=1);

use SGPA\Core\Router;
use SGPA\Controllers\AdminController;
use SGPA\Controllers\AuthController;
use SGPA\Controllers\CompanyController;
use SGPA\Controllers\UserController;

$router = new Router();

// Rotas de páginas
$router->get('/', fn() => $router->getAdminController()->index());
$router->get('/login', 'login.php');
$router->get('/dashboard', 'dashboard.php');
$router->get('/admin/tenants', 'admin/tenants.php');

// API - Administração
$router->post('/api/admin/tenants', fn() => $router->getAdminController()->createTenant());
$router->get('/api/admin/tenants', fn() => $router->getAdminController()->listTenants());
$router->get('/api/admin/tenants/{id}', fn($id) => $router->getAdminController()->getTenant($id));
$router->put('/api/admin/tenants/{id}', fn($id) => $router->getAdminController()->updateTenant($id));

// API - Autenticação
$router->post('/api/auth/login', fn() => $router->getAuthController()->login());
$router->post('/api/auth/logout', fn() => $router->getAuthController()->logout());
$router->get('/api/auth/me', fn() => $router->getAuthController()->me());

// API - Usuários
$router->get('/api/users', fn() => $router->getUserController()->list());
$router->post('/api/users', fn() => $router->getUserController()->create());
$router->get('/api/users/{id}', fn($id) => $router->getUserController()->get($id));
$router->put('/api/users/{id}', fn($id) => $router->getUserController()->update($id));
$router->delete('/api/users/{id}', fn($id) => $router->getUserController()->delete($id));

// API - Empresas
$router->get('/api/companies', fn() => $router->getCompanyController()->list());
$router->post('/api/companies', fn() => $router->getCompanyController()->create());
$router->get('/api/companies/{id}', fn($id) => $router->getCompanyController()->get($id));
$router->put('/api/companies/{id}', fn($id) => $router->getCompanyController()->update($id));
$router->delete('/api/companies/{id}', fn($id) => $router->getCompanyController()->delete($id));

return $router;
