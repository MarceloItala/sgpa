<?php
declare(strict_types=1);

namespace SGPA\Models;

use SGPA\Core\TenantScope;

class Client extends BaseModel
{
    protected string $table = 'clients';

    public function __construct()
    {
        parent::__construct();
    }

    public function create(array $data): string
    {
        $data['id'] = \Ramsey\Uuid\Uuid::uuid4()->toString();
        $data['tenant_id'] = TenantScope::getCurrentTenant();
        return parent::create($data);
    }

    public function update(string $id, array $data): bool
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        return parent::update($id, $data);
    }

    public function find(string $id): ?array
    {
        $result = parent::findBy([
            'id' => $id,
            'tenant_id' => TenantScope::getCurrentTenant()
        ]);
        
        return !empty($result) ? $result[0] : null;
    }

    public function findByTenant(array $criteria = [], array $orderBy = []): array
    {
        $criteria['tenant_id'] = TenantScope::getCurrentTenant();
        return parent::findBy($criteria, $orderBy);
    }

    public function delete(string $id): bool
    {
        // Soft delete
        return parent::delete($id);
    }

    public function validate(array $data): array
    {
        $errors = [];

        // Validação do CNPJ
        if (empty($data['cnpj']) || !$this->validateCNPJ($data['cnpj'])) {
            $errors['cnpj'] = 'CNPJ inválido';
        }

        // Validação do e-mail
        if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'E-mail inválido';
        }

        // Validação do CEP
        if (empty($data['cep']) || !preg_match('/^[0-9]{8}$/', $data['cep'])) {
            $errors['cep'] = 'CEP inválido';
        }

        // Outras validações conforme necessário...

        return $errors;
    }

    private function validateCNPJ(string $cnpj): bool
    {
        $cnpj = preg_replace('/[^0-9]/', '', $cnpj);
        
        if (strlen($cnpj) != 14) {
            return false;
        }

        if (preg_match('/(\d)\1{13}/', $cnpj)) {
            return false;
        }

        for ($i = 0, $j = 5, $sum = 0; $i < 12; $i++) {
            $sum += $cnpj[$i] * $j;
            $j = ($j == 2) ? 9 : $j - 1;
        }

        $rest = $sum % 11;
        if ($cnpj[12] != ($rest < 2 ? 0 : 11 - $rest)) {
            return false;
        }

        for ($i = 0, $j = 6, $sum = 0; $i < 13; $i++) {
            $sum += $cnpj[$i] * $j;
            $j = ($j == 2) ? 9 : $j - 1;
        }

        $rest = $sum % 11;
        return $cnpj[13] == ($rest < 2 ? 0 : 11 - $rest);
    }
}
