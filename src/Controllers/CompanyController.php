<?php
declare(strict_types=1);

namespace SGPA\Controllers;

use SGPA\Models\Company;
use SGPA\Exceptions\ValidationException;

class CompanyController
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
            
            $company = new Company($data);
            
            if ($company->save()) {
                http_response_code(201);
                echo json_encode([
                    'message' => 'Empresa criada com sucesso',
                    'company' => [
                        'id' => $company->getId(),
                        'corporate_name' => $company->getCorporateName(),
                        'trade_name' => $company->getTradeName(),
                        'cnpj' => $company->getCnpj(),
                        'email' => $company->getEmail()
                    ]
                ]);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Erro ao criar empresa']);
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
            $company = Company::findById($id);
            
            if (!$company) {
                http_response_code(404);
                echo json_encode(['error' => 'Empresa não encontrada']);
                return;
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!$data) {
                http_response_code(400);
                echo json_encode(['error' => 'Dados inválidos']);
                return;
            }
            
            $company->fill($data);
            
            if ($company->save()) {
                echo json_encode([
                    'message' => 'Empresa atualizada com sucesso',
                    'company' => [
                        'id' => $company->getId(),
                        'corporate_name' => $company->getCorporateName(),
                        'trade_name' => $company->getTradeName(),
                        'cnpj' => $company->getCnpj(),
                        'email' => $company->getEmail()
                    ]
                ]);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Erro ao atualizar empresa']);
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
            $company = Company::findById($id);
            
            if (!$company) {
                http_response_code(404);
                echo json_encode(['error' => 'Empresa não encontrada']);
                return;
            }
            
            echo json_encode([
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
            
            if (isset($_GET['status'])) {
                $filters['status'] = $_GET['status'];
            }
            
            if (isset($_GET['search'])) {
                $filters['search'] = $_GET['search'];
            }
            
            $companies = Company::findAll($filters);
            
            $response = array_map(function($company) {
                return [
                    'id' => $company->getId(),
                    'corporate_name' => $company->getCorporateName(),
                    'trade_name' => $company->getTradeName(),
                    'cnpj' => $company->getCnpj(),
                    'city' => $company->getCity(),
                    'state' => $company->getState(),
                    'status' => $company->getStatus()
                ];
            }, $companies);
            
            echo json_encode($response);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Erro interno do servidor']);
        }
    }

    public function delete(string $id): void
    {
        try {
            $company = Company::findById($id);
            
            if (!$company) {
                http_response_code(404);
                echo json_encode(['error' => 'Empresa não encontrada']);
                return;
            }
            
            // Em vez de deletar, vamos apenas inativar
            $company->setStatus('inactive');
            
            if ($company->save()) {
                echo json_encode(['message' => 'Empresa inativada com sucesso']);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Erro ao inativar empresa']);
            }
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Erro interno do servidor']);
        }
    }
}
