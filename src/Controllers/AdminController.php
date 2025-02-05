<?php
declare(strict_types=1);

namespace SGPA\Controllers;

use SGPA\Core\Auth;
use SGPA\Core\Database;
use SGPA\Core\Response;
use SGPA\Models\Company;
use SGPA\Models\User;
use SGPA\Exceptions\ValidationException;
use SGPA\Exceptions\AuthenticationException;

class AdminController
{
    private Auth $auth;

    public function __construct()
    {
        $this->auth = new Auth();
    }

    public function index(): void
    {
        error_log('Debug - AdminController::index() - Iniciando');
        try {
            error_log('Debug - AdminController::index() - Tentando obter usuário atual');
            $currentUser = $this->auth->getCurrentUser();
            
            if (!$currentUser) {
                error_log('Debug - AdminController::index() - Usuário não autenticado, redirecionando para /login');
                header('Location: /login');
                return;
            }

            error_log('Debug - AdminController::index() - Usuário autenticado: ' . $currentUser->getEmail() . ', Role: ' . $currentUser->getRole());
            if ($currentUser->getRole() === 'admin') {
                error_log('Debug - AdminController::index() - Redirecionando admin para /admin/tenants');
                header('Location: /admin/tenants');
            } else {
                error_log('Debug - AdminController::index() - Redirecionando usuário para /dashboard');
                header('Location: /dashboard');
            }
        } catch (\Exception $e) {
            error_log('Debug - AdminController::index() - Erro: ' . $e->getMessage());
            header('Location: /login');
        }
    }

    public function createTenant(): void
    {
        try {
            // Verifica se o usuário atual é um admin
            $currentUser = $this->auth->getCurrentUser();
            if (!$currentUser || $currentUser->getRole() !== 'admin') {
                throw new AuthenticationException('Acesso não autorizado');
            }

            // Recebe os dados do POST
            $data = json_decode(file_get_contents('php://input'), true);
            if (!$data) {
                Response::json(['error' => 'Dados inválidos'], 400);
                return;
            }

            // Cria a empresa (tenant)
            $company = new Company([
                'corporate_name' => $data['corporate_name'],
                'trade_name' => $data['trade_name'],
                'cnpj' => $data['cnpj'],
                'address' => $data['address'],
                'city' => $data['city'],
                'state' => $data['state'],
                'zip_code' => $data['zip_code'],
                'phone' => $data['phone'],
                'email' => $data['email']
            ]);

            // Salva a empresa
            if (!$company->save()) {
                Response::json(['error' => 'Erro ao criar empresa'], 500);
                return;
            }

            // Cria o usuário administrador da empresa
            $user = new User([
                'company_id' => $company->getId(),
                'name' => $data['admin_name'],
                'email' => $data['admin_email'],
                'role' => 'tenant_admin'
            ]);
            $user->setPassword($data['admin_password']);

            // Salva o usuário
            if (!$user->save()) {
                Response::json(['error' => 'Erro ao criar usuário administrador'], 500);
                return;
            }

            Response::json([
                'message' => 'Tenant criado com sucesso',
                'company' => [
                    'id' => $company->getId(),
                    'corporate_name' => $company->getCorporateName(),
                    'trade_name' => $company->getTradeName(),
                    'cnpj' => $company->getCnpj(),
                    'email' => $company->getEmail()
                ],
                'admin' => [
                    'id' => $user->getId(),
                    'name' => $user->getName(),
                    'email' => $user->getEmail()
                ]
            ], 201);

        } catch (ValidationException $e) {
            Response::json(['error' => 'Erro de validação', 'details' => $e->getErrors()], 400);
        } catch (AuthenticationException $e) {
            Response::json(['error' => $e->getMessage()], 403);
        } catch (\Exception $e) {
            Response::json(['error' => 'Erro interno do servidor'], 500);
        }
    }

    public function listTenants(): void
    {
        try {
            // Verifica se o usuário atual é um admin
            $currentUser = $this->auth->getCurrentUser();
            if (!$currentUser || $currentUser->getRole() !== 'admin') {
                throw new AuthenticationException('Acesso não autorizado');
            }

            // Busca todas as empresas
            $companies = Company::findAll();
            $result = [];

            foreach ($companies as $company) {
                $result[] = [
                    'id' => $company->getId(),
                    'corporate_name' => $company->getCorporateName(),
                    'trade_name' => $company->getTradeName(),
                    'cnpj' => $company->getCnpj(),
                    'email' => $company->getEmail(),
                    'status' => $company->getStatus(),
                    'created_at' => $company->getCreatedAt()
                ];
            }

            Response::json($result);

        } catch (AuthenticationException $e) {
            Response::json(['error' => $e->getMessage()], 403);
        } catch (\Exception $e) {
            Response::json(['error' => 'Erro interno do servidor'], 500);
        }
    }

    public function getTenant(string $id): void
    {
        try {
            // Verifica se o usuário atual é um admin
            $currentUser = $this->auth->getCurrentUser();
            if (!$currentUser || $currentUser->getRole() !== 'admin') {
                throw new AuthenticationException('Acesso não autorizado');
            }

            // Busca a empresa
            $company = Company::findById($id);
            if (!$company) {
                Response::json(['error' => 'Empresa não encontrada'], 404);
                return;
            }

            // Busca os usuários da empresa
            $users = User::findAll(['company_id' => $id]);
            $usersList = [];

            foreach ($users as $user) {
                $usersList[] = [
                    'id' => $user->getId(),
                    'name' => $user->getName(),
                    'email' => $user->getEmail(),
                    'role' => $user->getRole(),
                    'status' => $user->getStatus(),
                    'last_login' => $user->getLastLogin()
                ];
            }

            Response::json([
                'company' => [
                    'id' => $company->getId(),
                    'corporate_name' => $company->getCorporateName(),
                    'trade_name' => $company->getTradeName(),
                    'cnpj' => $company->getCnpj(),
                    'address' => $company->getAddress(),
                    'city' => $company->getCity(),
                    'state' => $company->getState(),
                    'zip_code' => $company->getZipCode(),
                    'phone' => $company->getPhone(),
                    'email' => $company->getEmail(),
                    'logo_url' => $company->getLogoUrl(),
                    'status' => $company->getStatus(),
                    'created_at' => $company->getCreatedAt(),
                    'updated_at' => $company->getUpdatedAt()
                ],
                'users' => $usersList
            ]);

        } catch (AuthenticationException $e) {
            Response::json(['error' => $e->getMessage()], 403);
        } catch (\Exception $e) {
            Response::json(['error' => 'Erro interno do servidor'], 500);
        }
    }

    public function updateTenant(string $id): void
    {
        try {
            // Verifica se o usuário atual é um admin
            $currentUser = $this->auth->getCurrentUser();
            if (!$currentUser || $currentUser->getRole() !== 'admin') {
                throw new AuthenticationException('Acesso não autorizado');
            }

            // Busca a empresa
            $company = Company::findById($id);
            if (!$company) {
                Response::json(['error' => 'Empresa não encontrada'], 404);
                return;
            }

            // Recebe os dados do PUT
            $data = json_decode(file_get_contents('php://input'), true);
            if (!$data) {
                Response::json(['error' => 'Dados inválidos'], 400);
                return;
            }

            // Atualiza os dados da empresa
            $company->fill($data);

            // Salva as alterações
            if (!$company->save()) {
                Response::json(['error' => 'Erro ao atualizar empresa'], 500);
                return;
            }

            Response::json([
                'message' => 'Empresa atualizada com sucesso',
                'company' => [
                    'id' => $company->getId(),
                    'corporate_name' => $company->getCorporateName(),
                    'trade_name' => $company->getTradeName(),
                    'cnpj' => $company->getCnpj(),
                    'email' => $company->getEmail(),
                    'status' => $company->getStatus()
                ]
            ]);

        } catch (ValidationException $e) {
            Response::json(['error' => 'Erro de validação', 'details' => $e->getErrors()], 400);
        } catch (AuthenticationException $e) {
            Response::json(['error' => $e->getMessage()], 403);
        } catch (\Exception $e) {
            Response::json(['error' => 'Erro interno do servidor'], 500);
        }
    }
}
