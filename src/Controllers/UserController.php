<?php
declare(strict_types=1);

namespace SGPA\Controllers;

use SGPA\Models\User;
use SGPA\Exceptions\ValidationException;

class UserController
{
    public function create(): void
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!$data) {
                http_response_code(400);
                echo json_encode(['error' => 'Dados inválidos']);
                return;
            }
            
            // Verifica se já existe um usuário com este email na empresa
            $existingUser = User::findByEmail($data['email'], $data['company_id']);
            if ($existingUser) {
                http_response_code(400);
                echo json_encode(['error' => 'Já existe um usuário com este e-mail']);
                return;
            }
            
            $user = new User($data);
            
            if (isset($data['password'])) {
                $user->setPassword($data['password']);
            }
            
            if ($user->save()) {
                http_response_code(201);
                echo json_encode([
                    'message' => 'Usuário criado com sucesso',
                    'user' => [
                        'id' => $user->getId(),
                        'name' => $user->getName(),
                        'email' => $user->getEmail(),
                        'role' => $user->getRole()
                    ]
                ]);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Erro ao criar usuário']);
            }
        } catch (ValidationException $e) {
            http_response_code(400);
            echo json_encode([
                'error' => $e->getMessage(),
                'errors' => $e->getErrors()
            ]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Erro interno do servidor']);
        }
    }

    public function update(string $id): void
    {
        try {
            $user = User::findById($id);
            
            if (!$user) {
                http_response_code(404);
                echo json_encode(['error' => 'Usuário não encontrado']);
                return;
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!$data) {
                http_response_code(400);
                echo json_encode(['error' => 'Dados inválidos']);
                return;
            }
            
            // Se estiver alterando o email, verifica se já existe
            if (isset($data['email']) && $data['email'] !== $user->getEmail()) {
                $existingUser = User::findByEmail($data['email'], $user->getCompanyId());
                if ($existingUser) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Já existe um usuário com este e-mail']);
                    return;
                }
            }
            
            // Remove o password do array de dados se estiver vazio
            if (isset($data['password']) && empty($data['password'])) {
                unset($data['password']);
            }
            
            $user->fill($data);
            
            // Se foi enviada uma nova senha
            if (isset($data['password'])) {
                $user->setPassword($data['password']);
            }
            
            if ($user->save()) {
                echo json_encode([
                    'message' => 'Usuário atualizado com sucesso',
                    'user' => [
                        'id' => $user->getId(),
                        'name' => $user->getName(),
                        'email' => $user->getEmail(),
                        'role' => $user->getRole()
                    ]
                ]);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Erro ao atualizar usuário']);
            }
        } catch (ValidationException $e) {
            http_response_code(400);
            echo json_encode([
                'error' => $e->getMessage(),
                'errors' => $e->getErrors()
            ]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Erro interno do servidor']);
        }
    }

    public function get(string $id): void
    {
        try {
            $user = User::findById($id);
            
            if (!$user) {
                http_response_code(404);
                echo json_encode(['error' => 'Usuário não encontrado']);
                return;
            }
            
            echo json_encode([
                'id' => $user->getId(),
                'company_id' => $user->getCompanyId(),
                'name' => $user->getName(),
                'email' => $user->getEmail(),
                'role' => $user->getRole(),
                'status' => $user->getStatus(),
                'last_login' => $user->getLastLogin(),
                'created_at' => $user->getCreatedAt(),
                'updated_at' => $user->getUpdatedAt()
            ]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Erro interno do servidor']);
        }
    }

    public function list(): void
    {
        try {
            $filters = [];
            
            // Filtra por empresa
            if (isset($_GET['company_id'])) {
                $filters['company_id'] = $_GET['company_id'];
            }
            
            if (isset($_GET['status'])) {
                $filters['status'] = $_GET['status'];
            }
            
            if (isset($_GET['role'])) {
                $filters['role'] = $_GET['role'];
            }
            
            if (isset($_GET['search'])) {
                $filters['search'] = $_GET['search'];
            }
            
            $users = User::findAll($filters);
            
            $response = array_map(function($user) {
                return [
                    'id' => $user->getId(),
                    'name' => $user->getName(),
                    'email' => $user->getEmail(),
                    'role' => $user->getRole(),
                    'status' => $user->getStatus(),
                    'last_login' => $user->getLastLogin()
                ];
            }, $users);
            
            echo json_encode($response);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Erro interno do servidor']);
        }
    }

    public function delete(string $id): void
    {
        try {
            $user = User::findById($id);
            
            if (!$user) {
                http_response_code(404);
                echo json_encode(['error' => 'Usuário não encontrado']);
                return;
            }
            
            // Em vez de deletar, vamos apenas inativar
            $user->setStatus('inactive');
            
            if ($user->save()) {
                echo json_encode(['message' => 'Usuário inativado com sucesso']);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Erro ao inativar usuário']);
            }
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Erro interno do servidor']);
        }
    }

    public function resetPassword(): void
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['email']) || !isset($data['company_id'])) {
                http_response_code(400);
                echo json_encode(['error' => 'E-mail e ID da empresa são obrigatórios']);
                return;
            }
            
            $user = User::findByEmail($data['email'], $data['company_id']);
            
            if (!$user) {
                http_response_code(404);
                echo json_encode(['error' => 'Usuário não encontrado']);
                return;
            }
            
            $token = $user->generatePasswordResetToken();
            
            if ($user->save()) {
                // TODO: Enviar e-mail com o token
                echo json_encode(['message' => 'Token de recuperação gerado com sucesso']);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Erro ao gerar token de recuperação']);
            }
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Erro interno do servidor']);
        }
    }

    public function confirmPasswordReset(): void
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['token']) || !isset($data['password'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Token e nova senha são obrigatórios']);
                return;
            }
            
            // TODO: Implementar lógica de busca por token
            $user = User::findByResetToken($data['token']);
            
            if (!$user) {
                http_response_code(400);
                echo json_encode(['error' => 'Token inválido ou expirado']);
                return;
            }
            
            $user->setPassword($data['password']);
            $user->clearPasswordResetToken();
            
            if ($user->save()) {
                echo json_encode(['message' => 'Senha alterada com sucesso']);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Erro ao alterar senha']);
            }
        } catch (ValidationException $e) {
            http_response_code(400);
            echo json_encode([
                'error' => $e->getMessage(),
                'errors' => $e->getErrors()
            ]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Erro interno do servidor']);
        }
    }
}
