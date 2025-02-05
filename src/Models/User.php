<?php
declare(strict_types=1);

namespace SGPA\Models;

use SGPA\Core\Database;
use SGPA\Exceptions\ValidationException;
use PDO;
use Ramsey\Uuid\Uuid;

class User
{
    private ?string $id = null;
    private string $tenantId;
    private string $name;
    private string $email;
    private string $password;
    private string $role = 'user';
    private string $status = 'ativo';
    private ?string $lastLogin = null;
    private ?string $createdAt = null;
    private ?string $updatedAt = null;
    private ?string $deletedAt = null;

    public function __construct(array $data = [])
    {
        if (!empty($data)) {
            $this->fill($data);
        }
    }

    public function fill(array $data): void
    {
        error_log('Debug - Preenchendo objeto User com dados: ' . json_encode($data));
        $this->id = $data['id'] ?? null;
        $this->tenantId = $data['tenant_id'] ?? '';
        $this->name = $data['name'] ?? '';
        $this->email = $data['email'] ?? '';
        $this->password = $data['password'] ?? '';
        $this->role = $data['role'] ?? 'user';
        $this->status = $data['status'] ?? 'ativo';
        $this->lastLogin = $data['last_login'] ?? null;
        $this->createdAt = $data['created_at'] ?? null;
        $this->updatedAt = $data['updated_at'] ?? null;
        $this->deletedAt = $data['deleted_at'] ?? null;
        error_log('Debug - Objeto User preenchido. ID: ' . $this->id . ', Role: ' . $this->role . ', Status: ' . $this->status);
    }

    public function validate(): void
    {
        $errors = [];

        if (empty($this->tenantId)) {
            $errors[] = "ID do tenant é obrigatório";
        }

        if (empty($this->name)) {
            $errors[] = "Nome é obrigatório";
        }

        if (empty($this->email)) {
            $errors[] = "E-mail é obrigatório";
        } elseif (!filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "E-mail inválido";
        }

        if (empty($this->password) && $this->id === null) {
            $errors[] = "Senha é obrigatória";
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
            
            $sql = "INSERT INTO users (
                id, tenant_id, name, email, password, role, 
                status, last_login
            ) VALUES (
                :id, :tenant_id, :name, :email, :password, :role,
                :status, :last_login
            )";
        } else {
            $sql = "UPDATE users SET 
                tenant_id = :tenant_id,
                name = :name,
                email = :email,
                password = :password,
                role = :role,
                status = :status,
                last_login = :last_login,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = :id";
        }

        $stmt = $db->prepare($sql);
        
        return $stmt->execute([
            'id' => $this->id,
            'tenant_id' => $this->tenantId,
            'name' => $this->name,
            'email' => $this->email,
            'password' => $this->password,
            'role' => $this->role,
            'status' => $this->status,
            'last_login' => $this->lastLogin
        ]);
    }

    public static function findById(string $id): ?self
    {
        error_log('Debug - Buscando usuário por ID: ' . $id);
        $db = Database::getInstance()->getConnection();
        
        $stmt = $db->prepare("SELECT * FROM users WHERE id = :id");
        $stmt->execute(['id' => $id]);
        
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        error_log('Debug - Dados encontrados: ' . json_encode($data));
        
        if (!$data) {
            error_log('Debug - Usuário não encontrado');
            return null;
        }
        
        $user = new self($data);
        error_log('Debug - Objeto User criado com sucesso. Role: ' . $user->getRole());
        return $user;
    }

    public static function findByEmail(string $email, string $tenantId): ?self
    {
        error_log('Debug - Buscando usuário - Email: ' . $email . ', Tenant ID: ' . $tenantId);
        $db = Database::getInstance()->getConnection();
        
        $stmt = $db->prepare("SELECT * FROM users WHERE email = :email AND tenant_id = :tenant_id AND deleted_at IS NULL");
        $stmt->execute(['email' => $email, 'tenant_id' => $tenantId]);
        
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        error_log('Debug - Dados encontrados: ' . json_encode($data));
        
        if (!$data) {
            error_log('Debug - Usuário não encontrado');
            return null;
        }
        
        $user = new self($data);
        error_log('Debug - Objeto User criado com sucesso. Role: ' . $user->getRole());
        return $user;
    }

    public static function findAll(array $filters = []): array
    {
        error_log('Debug - Buscando todos os usuários com filtros: ' . json_encode($filters));
        $db = Database::getInstance()->getConnection();
        
        $sql = "SELECT * FROM users WHERE 1=1";
        $params = [];
        
        if (!empty($filters['company_id'])) {
            $sql .= " AND company_id = :company_id";
            $params['company_id'] = $filters['company_id'];
        }
        
        if (!empty($filters['status'])) {
            $sql .= " AND status = :status";
            $params['status'] = $filters['status'];
        }
        
        if (!empty($filters['role'])) {
            $sql .= " AND role = :role";
            $params['role'] = $filters['role'];
        }
        
        if (!empty($filters['search'])) {
            $sql .= " AND (name LIKE :search OR email LIKE :search)";
            $params['search'] = "%{$filters['search']}%";
        }
        
        error_log('Debug - SQL: ' . $sql);
        error_log('Debug - Parâmetros: ' . json_encode($params));
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        
        $users = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            error_log('Debug - Criando objeto User para: ' . ($row['email'] ?? 'unknown'));
            $user = new self($row);
            error_log('Debug - Objeto User criado com sucesso. Role: ' . $user->getRole());
            $users[] = $user;
        }
        
        error_log('Debug - Total de usuários encontrados: ' . count($users));
        return $users;
    }

    public function setPassword(string $password): void
    {
        if (strlen($password) < 8) {
            throw new ValidationException("A senha deve ter no mínimo 8 caracteres");
        }
        
        $this->password = password_hash($password, PASSWORD_DEFAULT);
    }

    public function verifyPassword(string $password): bool
    {
        error_log('Debug - Senha fornecida: ' . $password);
        error_log('Debug - Hash armazenado: ' . $this->password);
        $result = password_verify($password, $this->password);
        error_log('Debug - Resultado da verificação: ' . ($result ? 'true' : 'false'));
        return $result;
    }

    public function generatePasswordResetToken(): string
    {
        $this->passwordResetToken = bin2hex(random_bytes(32));
        $this->passwordResetExpires = date('Y-m-d H:i:s', strtotime('+1 hour'));
        return $this->passwordResetToken;
    }

    public function clearPasswordResetToken(): void
    {
        $this->passwordResetToken = null;
        $this->passwordResetExpires = null;
    }

    public function updateLastLogin(): bool
    {
        $db = Database::getInstance()->getConnection();
        
        $stmt = $db->prepare("UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE id = :id");
        return $stmt->execute(['id' => $this->id]);
    }

    // Getters
    public function getId(): ?string
    {
        return $this->id;
    }

    public function getTenantId(): string
    {
        return $this->tenantId;
    }

    public function getCompanyId(): string
    {
        return $this->companyId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getRole(): string
    {
        return $this->role;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getLastLogin(): ?string
    {
        return $this->lastLogin;
    }

    public function getPasswordResetToken(): ?string
    {
        return $this->passwordResetToken;
    }

    public function getPasswordResetExpires(): ?string
    {
        return $this->passwordResetExpires;
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
    public function setCompanyId(string $companyId): void
    {
        $this->companyId = $companyId;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function setEmail(string $email): void
    {
        $this->email = strtolower($email);
    }

    public function setRole(string $role): void
    {
        if (!in_array($role, ['admin', 'manager', 'user'])) {
            throw new ValidationException("Função inválida");
        }
        $this->role = $role;
    }

    public function setStatus(string $status): void
    {
        if (!in_array($status, ['active', 'inactive', 'blocked'])) {
            throw new ValidationException("Status inválido");
        }
        $this->status = $status;
    }
}
