<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

class CreateCompaniesUsersTables extends AbstractMigration
{
    public function change(): void
    {
        // Tabela de empresas
        $this->table('companies', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'uuid')
            ->addColumn('corporate_name', 'string', ['limit' => 255])
            ->addColumn('trade_name', 'string', ['limit' => 255])
            ->addColumn('cnpj', 'string', ['limit' => 14])
            ->addColumn('address', 'string', ['limit' => 255])
            ->addColumn('city', 'string', ['limit' => 100])
            ->addColumn('state', 'string', ['limit' => 2])
            ->addColumn('zip_code', 'string', ['limit' => 8])
            ->addColumn('phone', 'string', ['limit' => 20])
            ->addColumn('email', 'string', ['limit' => 255])
            ->addColumn('logo_url', 'string', ['limit' => 255, 'null' => true])
            ->addColumn('status', 'enum', [
                'values' => ['active', 'inactive', 'suspended'],
                'default' => 'active'
            ])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'timestamp', [
                'default' => 'CURRENT_TIMESTAMP',
                'update' => 'CURRENT_TIMESTAMP'
            ])
            ->addIndex(['cnpj'], ['unique' => true])
            ->addIndex(['email'], ['unique' => true])
            ->create();

        // Tabela de usuários
        $this->table('users', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'uuid')
            ->addColumn('company_id', 'uuid')
            ->addColumn('name', 'string', ['limit' => 255])
            ->addColumn('email', 'string', ['limit' => 255])
            ->addColumn('password_hash', 'string', ['limit' => 255])
            ->addColumn('role', 'enum', [
                'values' => ['admin', 'manager', 'user'],
                'default' => 'user'
            ])
            ->addColumn('status', 'enum', [
                'values' => ['active', 'inactive', 'blocked'],
                'default' => 'active'
            ])
            ->addColumn('last_login', 'timestamp', ['null' => true])
            ->addColumn('password_reset_token', 'string', ['limit' => 100, 'null' => true])
            ->addColumn('password_reset_expires', 'timestamp', ['null' => true])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'timestamp', [
                'default' => 'CURRENT_TIMESTAMP',
                'update' => 'CURRENT_TIMESTAMP'
            ])
            ->addIndex(['company_id'])
            ->addIndex(['email', 'company_id'], ['unique' => true])
            ->addForeignKey('company_id', 'companies', 'id', [
                'delete' => 'CASCADE',
                'update' => 'CASCADE'
            ])
            ->create();

        // Tabela de permissões
        $this->table('permissions', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'uuid')
            ->addColumn('name', 'string', ['limit' => 100])
            ->addColumn('description', 'string', ['limit' => 255])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->addIndex(['name'], ['unique' => true])
            ->create();

        // Tabela de relacionamento usuários-permissões
        $this->table('user_permissions', ['id' => false, 'primary_key' => ['user_id', 'permission_id']])
            ->addColumn('user_id', 'uuid')
            ->addColumn('permission_id', 'uuid')
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->addForeignKey('user_id', 'users', 'id', [
                'delete' => 'CASCADE',
                'update' => 'CASCADE'
            ])
            ->addForeignKey('permission_id', 'permissions', 'id', [
                'delete' => 'CASCADE',
                'update' => 'CASCADE'
            ])
            ->create();
    }
}
