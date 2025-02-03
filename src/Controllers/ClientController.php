<?php
declare(strict_types=1);

namespace SGPA\Controllers;

use SGPA\Models\Client;
use SGPA\Exceptions\ValidationException;
use SGPA\Exceptions\NotFoundException;

class ClientController
{
    private Client $clientModel;

    public function __construct()
    {
        $this->clientModel = new Client();
    }

    public function index(): array
    {
        $orderBy = [
            'razao_social' => 'ASC'
        ];
        
        return [
            'data' => $this->clientModel->findByTenant([], $orderBy),
            'success' => true
        ];
    }

    public function create(): array
    {
        $data = $this->getRequestData();
        
        // Valida os dados
        $errors = $this->clientModel->validate($data);
        if (!empty($errors)) {
            throw new ValidationException('Dados inválidos', $errors);
        }

        // Cria o cliente
        $id = $this->clientModel->create($data);
        $client = $this->clientModel->find($id);

        return [
            'data' => $client,
            'message' => 'Cliente criado com sucesso',
            'success' => true
        ];
    }

    public function show(string $id): array
    {
        $client = $this->clientModel->find($id);
        
        if (!$client) {
            throw new NotFoundException('Cliente não encontrado');
        }

        return [
            'data' => $client,
            'success' => true
        ];
    }

    public function update(string $id): array
    {
        $client = $this->clientModel->find($id);
        
        if (!$client) {
            throw new NotFoundException('Cliente não encontrado');
        }

        $data = $this->getRequestData();
        
        // Valida os dados
        $errors = $this->clientModel->validate($data);
        if (!empty($errors)) {
            throw new ValidationException('Dados inválidos', $errors);
        }

        // Atualiza o cliente
        $this->clientModel->update($id, $data);
        $client = $this->clientModel->find($id);

        return [
            'data' => $client,
            'message' => 'Cliente atualizado com sucesso',
            'success' => true
        ];
    }

    public function delete(string $id): array
    {
        $client = $this->clientModel->find($id);
        
        if (!$client) {
            throw new NotFoundException('Cliente não encontrado');
        }

        $this->clientModel->delete($id);

        return [
            'message' => 'Cliente removido com sucesso',
            'success' => true
        ];
    }

    private function getRequestData(): array
    {
        $json = file_get_contents('php://input');
        return json_decode($json, true) ?? [];
    }
}
