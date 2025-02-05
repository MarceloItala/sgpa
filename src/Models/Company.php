<?php
declare(strict_types=1);

namespace SGPA\Models;

use SGPA\Core\Database;
use SGPA\Exceptions\ValidationException;
use PDO;
use Ramsey\Uuid\Uuid;

class Company
{
    private ?string $id = null;
    private string $corporateName;
    private string $tradeName;
    private string $cnpj;
    private string $address;
    private string $city;
    private string $state;
    private string $zipCode;
    private string $phone;
    private string $email;
    private ?string $logoUrl = null;
    private string $status = 'active';
    private ?string $createdAt = null;
    private ?string $updatedAt = null;

    public function __construct(array $data = [])
    {
        if (!empty($data)) {
            $this->fill($data);
        }
    }

    public function fill(array $data): void
    {
        error_log('Debug - Company::fill - Preenchendo empresa com dados: ' . json_encode($data));
        $this->id = $data['id'] ?? null;
        $this->corporateName = $data['corporate_name'] ?? '';
        $this->tradeName = $data['trade_name'] ?? '';
        $this->cnpj = $data['cnpj'] ?? '';
        $this->address = $data['address'] ?? '';
        $this->city = $data['city'] ?? '';
        $this->state = $data['state'] ?? '';
        $this->zipCode = $data['zip_code'] ?? '';
        $this->phone = $data['phone'] ?? '';
        $this->email = $data['email'] ?? '';
        $this->logoUrl = $data['logo_url'] ?? null;
        $this->status = $data['status'] ?? 'active';
        $this->createdAt = $data['created_at'] ?? null;
        $this->updatedAt = $data['updated_at'] ?? null;
        error_log('Debug - Company::fill - Empresa preenchida. ID: ' . $this->id . ', Status: ' . $this->status);
    }

    public function validate(): void
    {
        $errors = [];

        if (empty($this->corporateName)) {
            $errors[] = "Razão social é obrigatória";
        }

        if (empty($this->cnpj)) {
            $errors[] = "CNPJ é obrigatório";
        } elseif (!$this->validateCNPJ($this->cnpj)) {
            $errors[] = "CNPJ inválido";
        }

        if (empty($this->email)) {
            $errors[] = "E-mail é obrigatório";
        } elseif (!filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "E-mail inválido";
        }

        if (!empty($errors)) {
            throw new ValidationException("Erros de validação encontrados", $errors);
        }
    }

    public function save(): bool
    {
        $this->validate();

        $db = Database::getInstance()->getConnection();

        if ($this->id === null) {
            $this->id = Uuid::uuid4()->toString();
            
            $sql = "INSERT INTO companies (
                id, corporate_name, trade_name, cnpj, address, city, 
                state, zip_code, phone, email, logo_url, status
            ) VALUES (
                :id, :corporate_name, :trade_name, :cnpj, :address, :city,
                :state, :zip_code, :phone, :email, :logo_url, :status
            )";
        } else {
            $sql = "UPDATE companies SET 
                corporate_name = :corporate_name,
                trade_name = :trade_name,
                cnpj = :cnpj,
                address = :address,
                city = :city,
                state = :state,
                zip_code = :zip_code,
                phone = :phone,
                email = :email,
                logo_url = :logo_url,
                status = :status,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = :id";
        }

        $stmt = $db->prepare($sql);
        
        return $stmt->execute([
            'id' => $this->id,
            'corporate_name' => $this->corporateName,
            'trade_name' => $this->tradeName,
            'cnpj' => $this->cnpj,
            'address' => $this->address,
            'city' => $this->city,
            'state' => $this->state,
            'zip_code' => $this->zipCode,
            'phone' => $this->phone,
            'email' => $this->email,
            'logo_url' => $this->logoUrl,
            'status' => $this->status
        ]);
    }

    public static function findById(string $id): ?self
    {
        error_log('Debug - Company::findById - Buscando empresa com ID: ' . $id);
        $db = Database::getInstance()->getConnection();
        
        $stmt = $db->prepare("SELECT * FROM tenants WHERE id = :id");
        $stmt->execute(['id' => $id]);
        
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        error_log('Debug - Company::findById - Dados encontrados: ' . json_encode($data));
        
        if (!$data) {
            error_log('Debug - Company::findById - Empresa não encontrada');
            return null;
        }
        
        // Converte os campos da tabela tenants para o formato da classe Company
        $company = new self([
            'id' => $data['id'],
            'corporate_name' => $data['razao_social'],
            'trade_name' => $data['nome_fantasia'],
            'cnpj' => $data['cnpj'],
            'email' => $data['email'],
            'phone' => $data['telefone'],
            'status' => $data['status'],
            'logo_url' => $data['logo_path'],
            'created_at' => $data['created_at'],
            'updated_at' => $data['updated_at']
        ]);
        
        error_log('Debug - Company::findById - Empresa criada com sucesso. Status: ' . $company->getStatus());
        return $company;
    }

    public static function findAll(array $filters = []): array
    {
        error_log('Debug - Company::findAll - Buscando empresas com filtros: ' . json_encode($filters));
        $db = Database::getInstance()->getConnection();
        
        $sql = "SELECT * FROM tenants WHERE 1=1";
        $params = [];
        
        if (!empty($filters['status'])) {
            $sql .= " AND status = :status";
            $params['status'] = $filters['status'];
        }
        
        if (!empty($filters['search'])) {
            $sql .= " AND (razao_social LIKE :search OR nome_fantasia LIKE :search OR cnpj LIKE :search)";
            $params['search'] = "%{$filters['search']}%";
        }
        
        error_log('Debug - Company::findAll - SQL: ' . $sql);
        error_log('Debug - Company::findAll - Parâmetros: ' . json_encode($params));
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        
        $companies = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            error_log('Debug - Company::findAll - Criando empresa com dados: ' . json_encode($row));
            $company = new self([
                'id' => $row['id'],
                'corporate_name' => $row['razao_social'],
                'trade_name' => $row['nome_fantasia'],
                'cnpj' => $row['cnpj'],
                'email' => $row['email'],
                'phone' => $row['telefone'],
                'status' => $row['status'],
                'logo_url' => $row['logo_path'],
                'created_at' => $row['created_at'],
                'updated_at' => $row['updated_at']
            ]);
            error_log('Debug - Company::findAll - Empresa criada com sucesso. Status: ' . $company->getStatus());
            $companies[] = $company;
        }
        
        error_log('Debug - Company::findAll - Total de empresas encontradas: ' . count($companies));
        return $companies;
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

    // Getters
    public function getId(): ?string
    {
        return $this->id;
    }

    public function getCorporateName(): string
    {
        return $this->corporateName;
    }

    public function getTradeName(): string
    {
        return $this->tradeName;
    }

    public function getCnpj(): string
    {
        return $this->cnpj;
    }

    public function getAddress(): string
    {
        return $this->address;
    }

    public function getCity(): string
    {
        return $this->city;
    }

    public function getState(): string
    {
        return $this->state;
    }

    public function getZipCode(): string
    {
        return $this->zipCode;
    }

    public function getPhone(): string
    {
        return $this->phone;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getLogoUrl(): ?string
    {
        return $this->logoUrl;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getCreatedAt(): ?string
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?string
    {
        return $this->updatedAt;
    }

    // Setters
    public function setCorporateName(string $corporateName): void
    {
        $this->corporateName = $corporateName;
    }

    public function setTradeName(string $tradeName): void
    {
        $this->tradeName = $tradeName;
    }

    public function setCnpj(string $cnpj): void
    {
        $this->cnpj = preg_replace('/[^0-9]/', '', $cnpj);
    }

    public function setAddress(string $address): void
    {
        $this->address = $address;
    }

    public function setCity(string $city): void
    {
        $this->city = $city;
    }

    public function setState(string $state): void
    {
        $this->state = strtoupper($state);
    }

    public function setZipCode(string $zipCode): void
    {
        $this->zipCode = preg_replace('/[^0-9]/', '', $zipCode);
    }

    public function setPhone(string $phone): void
    {
        $this->phone = preg_replace('/[^0-9]/', '', $phone);
    }

    public function setEmail(string $email): void
    {
        $this->email = strtolower($email);
    }

    public function setLogoUrl(?string $logoUrl): void
    {
        $this->logoUrl = $logoUrl;
    }

    public function setStatus(string $status): void
    {
        if (!in_array($status, ['active', 'inactive', 'suspended'])) {
            throw new ValidationException("Status inválido");
        }
        $this->status = $status;
    }
}
