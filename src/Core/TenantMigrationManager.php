<?php
declare(strict_types=1);

namespace SGPA\Core;

use PDO;
use SGPA\Exceptions\TenantException;

class TenantMigrationManager
{
    private PDO $db;
    private string $migrationsPath;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
        $this->migrationsPath = dirname(__DIR__, 2) . '/database/migrations/tenant';
    }

    public function createMigrationTable(PDO $connection): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS migration_history (
            id INT AUTO_INCREMENT PRIMARY KEY,
            migration_name VARCHAR(255) NOT NULL,
            batch INT NOT NULL,
            executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        
        $connection->exec($sql);
    }

    public function runMigrationsForNewTenant(string $tenantId): void
    {
        $tenant = $this->getTenant($tenantId);
        if (!$tenant) {
            throw new TenantException("Tenant não encontrado");
        }

        // Cria o banco de dados se não existir
        if (!empty($tenant['db_name'])) {
            $this->createDatabase($tenant);
            $connection = $this->getTenantConnection($tenant);
            $this->createMigrationTable($connection);
            
            // Executa todas as migrations
            $migrations = $this->getPendingMigrations($connection);
            $this->executeMigrations($connection, $migrations);
        }
    }

    public function runMigrationsForAllTenants(): void
    {
        $stmt = $this->db->query("SELECT * FROM tenants WHERE status = 'ativo'");
        $tenants = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($tenants as $tenant) {
            if (empty($tenant['db_name'])) {
                continue;
            }

            try {
                $connection = $this->getTenantConnection($tenant);
                $this->createMigrationTable($connection);
                
                $migrations = $this->getPendingMigrations($connection);
                if (!empty($migrations)) {
                    $this->executeMigrations($connection, $migrations);
                }
            } catch (\Exception $e) {
                error_log("Erro ao executar migrations para tenant {$tenant['id']}: " . $e->getMessage());
            }
        }
    }

    private function getTenant(string $tenantId): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM tenants WHERE id = ?");
        $stmt->execute([$tenantId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function createDatabase(array $tenant): void
    {
        try {
            $this->db->exec("CREATE DATABASE IF NOT EXISTS `{$tenant['db_name']}`");
            
            // Cria usuário se não existir e concede privilégios
            $this->db->exec("
                CREATE USER IF NOT EXISTS '{$tenant['db_user']}'@'%' 
                IDENTIFIED BY '{$tenant['db_password']}'
            ");
            
            $this->db->exec("
                GRANT ALL PRIVILEGES ON `{$tenant['db_name']}`.* 
                TO '{$tenant['db_user']}'@'%'
            ");
            
            $this->db->exec("FLUSH PRIVILEGES");
        } catch (\PDOException $e) {
            throw new TenantException("Erro ao criar banco de dados: " . $e->getMessage());
        }
    }

    private function getTenantConnection(array $tenant): PDO
    {
        try {
            return new PDO(
                "mysql:host={$tenant['db_host']};dbname={$tenant['db_name']};charset=utf8mb4",
                $tenant['db_user'],
                $tenant['db_password'],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
        } catch (\PDOException $e) {
            throw new TenantException("Erro ao conectar ao banco do tenant: " . $e->getMessage());
        }
    }

    private function getPendingMigrations(PDO $connection): array
    {
        // Pega migrations já executadas
        $executed = [];
        $stmt = $connection->query("SELECT migration_name FROM migration_history");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $executed[] = $row['migration_name'];
        }

        // Pega todas as migrations disponíveis
        $available = [];
        foreach (glob($this->migrationsPath . "/*.sql") as $file) {
            $available[] = basename($file);
        }

        // Retorna migrations não executadas
        return array_diff($available, $executed);
    }

    private function executeMigrations(PDO $connection, array $migrations): void
    {
        if (empty($migrations)) {
            return;
        }

        // Pega o último batch
        $stmt = $connection->query("SELECT MAX(batch) as last_batch FROM migration_history");
        $lastBatch = (int)($stmt->fetch(PDO::FETCH_ASSOC)['last_batch'] ?? 0);
        $currentBatch = $lastBatch + 1;

        sort($migrations); // Garante ordem alfabética

        foreach ($migrations as $migration) {
            $sql = file_get_contents($this->migrationsPath . "/" . $migration);
            
            try {
                $connection->beginTransaction();
                
                // Executa a migration
                $connection->exec($sql);
                
                // Registra a execução
                $stmt = $connection->prepare("
                    INSERT INTO migration_history (migration_name, batch) 
                    VALUES (?, ?)
                ");
                $stmt->execute([$migration, $currentBatch]);
                
                $connection->commit();
            } catch (\Exception $e) {
                $connection->rollBack();
                throw new TenantException("Erro ao executar migration {$migration}: " . $e->getMessage());
            }
        }
    }
}
