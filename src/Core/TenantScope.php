<?php
declare(strict_types=1);

namespace SGPA\Core;

use PDO;
use SGPA\Exceptions\TenantException;
use SGPA\Models\Company;

class TenantScope
{
    private static ?string $currentTenantId = null;
    private static ?array $currentTenant = null;
    private static ?PDO $tenantConnection = null;
    public const ADMIN_TENANT_ID = '00000000-0000-0000-0000-000000000000';

    public static function initFromSubdomain(?string $host = null): void
    {
        if ($host === null) {
            $host = $_SERVER['HTTP_HOST'] ?? '';
        }
        error_log('Debug - Host: ' . $host);

        // Extrai o subdomínio
        $parts = explode('.', $host);
        error_log('Debug - Parts: ' . implode(', ', $parts));
        if (count($parts) < 3) {
            error_log('Debug - Subdomínio inválido: menos que 3 partes');
            throw new TenantException('Subdomínio inválido');
        }

        $subdomain = $parts[0];
        error_log('Debug - Subdomínio: ' . $subdomain);

        // Se for admin, usa o tenant administrativo
        if ($subdomain === 'admin') {
            error_log('Debug - Subdomínio admin detectado');
            self::setTenant(self::ADMIN_TENANT_ID);
            error_log('Debug - Tenant ID definido como: ' . self::ADMIN_TENANT_ID);
            return;
        }

        // Busca o tenant pelo subdomínio
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare('SELECT * FROM tenants WHERE subdomain = ? AND status = "ativo"');
        $stmt->execute([$subdomain]);
        $tenant = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$tenant) {
            throw new TenantException('Tenant não encontrado ou inativo');
        }

        self::$currentTenant = $tenant;
        self::setTenant($tenant['id']);
    }

    public static function setTenant(string $tenantId): void
    {
        error_log('Debug - Definindo tenant ID: ' . $tenantId);
        self::$currentTenantId = $tenantId;
        self::$tenantConnection = null; // Reset da conexão
        error_log('Debug - Tenant ID definido com sucesso. Current tenant ID: ' . self::$currentTenantId);
    }

    public static function getCurrentTenant(): ?string
    {
        error_log('Debug - Obtendo tenant ID atual: ' . (self::$currentTenantId ?? 'null'));
        return self::$currentTenantId;
    }

    public static function getTenantConnection(): PDO
    {
        if (self::$tenantConnection !== null) {
            return self::$tenantConnection;
        }

        if (self::$currentTenant === null || empty(self::$currentTenant['db_name'])) {
            // Se não tem banco específico, usa o banco principal
            return Database::getInstance()->getConnection();
        }

        try {
            $dsn = sprintf(
                'mysql:host=%s;dbname=%s;charset=utf8mb4',
                self::$currentTenant['db_host'],
                self::$currentTenant['db_name']
            );

            self::$tenantConnection = new PDO(
                $dsn,
                self::$currentTenant['db_user'],
                self::$currentTenant['db_password'],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );

            return self::$tenantConnection;
        } catch (\PDOException $e) {
            throw new TenantException('Erro ao conectar ao banco do tenant: ' . $e->getMessage());
        }
    }

    public static function clear(): void
    {
        self::$currentTenantId = null;
        self::$currentTenant = null;
        self::$tenantConnection = null;
    }

    public static function isAdmin(): bool
    {
        error_log('Debug - isAdmin check - currentTenantId: ' . self::$currentTenantId);
        error_log('Debug - isAdmin check - ADMIN_TENANT_ID: ' . self::ADMIN_TENANT_ID);
        return self::$currentTenantId === self::ADMIN_TENANT_ID;
    }

    public static function findById(string $tenantId): ?Company
    {
        error_log('Debug - TenantScope::findById - Buscando tenant por ID: ' . $tenantId);
        
        // Se for o tenant administrativo, retorna um objeto Company com status ativo
        if ($tenantId === self::ADMIN_TENANT_ID) {
            error_log('Debug - TenantScope::findById - Retornando tenant administrativo');
            return new Company([
                'id' => self::ADMIN_TENANT_ID,
                'corporate_name' => 'Administrador do Sistema',
                'trade_name' => 'Admin',
                'status' => 'ativo'
            ]);
        }
        
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare('SELECT * FROM tenants WHERE id = ?');
        $stmt->execute([$tenantId]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        error_log('Debug - TenantScope::findById - Dados encontrados: ' . ($data ? json_encode($data) : 'null'));
        
        if (!$data) {
            error_log('Debug - TenantScope::findById - Tenant não encontrado');
            return null;
        }
        
        // Converte os dados do tenant em um objeto Company
        $company = new Company([
            'id' => $data['id'],
            'corporate_name' => $data['razao_social'],
            'trade_name' => $data['nome_fantasia'],
            'cnpj' => $data['cnpj'],
            'email' => $data['email'],
            'phone' => $data['telefone'],
            'status' => $data['status']
        ]);
        
        error_log('Debug - TenantScope::findById - Tenant convertido para Company com sucesso');
        return $company;
    }
}
