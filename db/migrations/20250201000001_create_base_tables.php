<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateBaseTables extends AbstractMigration
{
    public function change(): void
    {
        // Tabela de Empresas (Tenants)
        $this->table('tenants', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'string', ['limit' => 36, 'null' => false])
            ->addColumn('razao_social', 'string', ['limit' => 255])
            ->addColumn('nome_fantasia', 'string', ['limit' => 255])
            ->addColumn('cnpj', 'string', ['limit' => 14])
            ->addColumn('email', 'string', ['limit' => 255])
            ->addColumn('telefone', 'string', ['limit' => 20])
            ->addColumn('status', 'enum', ['values' => ['ativo', 'inativo', 'suspenso']])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'timestamp', ['null' => true])
            ->addIndex(['cnpj'], ['unique' => true])
            ->create();

        // Tabela de Usuários
        $this->table('users', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'string', ['limit' => 36, 'null' => false])
            ->addColumn('tenant_id', 'string', ['limit' => 36, 'null' => false])
            ->addColumn('name', 'string', ['limit' => 255])
            ->addColumn('email', 'string', ['limit' => 255])
            ->addColumn('password', 'string', ['limit' => 255])
            ->addColumn('status', 'enum', ['values' => ['ativo', 'inativo']])
            ->addColumn('last_login', 'timestamp', ['null' => true])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'timestamp', ['null' => true])
            ->addColumn('deleted_at', 'timestamp', ['null' => true])
            ->addIndex(['email', 'tenant_id'], ['unique' => true])
            ->addForeignKey('tenant_id', 'tenants', 'id', ['delete' => 'CASCADE'])
            ->create();

        // Tabela de Papéis (Roles)
        $this->table('roles', ['id' => true, 'signed' => false])
            ->addColumn('name', 'string', ['limit' => 100])
            ->addColumn('tenant_id', 'string', ['limit' => 36, 'null' => true])
            ->addColumn('description', 'string', ['limit' => 255])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->addForeignKey('tenant_id', 'tenants', 'id', ['delete' => 'CASCADE'])
            ->create();

        // Tabela de Permissões
        $this->table('permissions', ['id' => true, 'signed' => false])
            ->addColumn('name', 'string', ['limit' => 100])
            ->addColumn('description', 'string', ['limit' => 255])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->create();

        // Tabela Pivô Roles-Permissions
        $this->table('role_permissions', ['id' => true, 'signed' => false])
            ->addColumn('role_id', 'integer', ['signed' => false])
            ->addColumn('permission_id', 'integer', ['signed' => false])
            ->addForeignKey('role_id', 'roles', 'id', ['delete' => 'CASCADE'])
            ->addForeignKey('permission_id', 'permissions', 'id', ['delete' => 'CASCADE'])
            ->create();

        // Tabela Pivô Users-Roles
        $this->table('user_roles', ['id' => true, 'signed' => false])
            ->addColumn('user_id', 'string', ['limit' => 36])
            ->addColumn('role_id', 'integer', ['signed' => false])
            ->addForeignKey('role_id', 'roles', 'id', ['delete' => 'CASCADE'])
            ->addForeignKey('user_id', 'users', 'id', ['delete' => 'CASCADE'])
            ->create();

        // Tabela de Clientes
        $this->table('clients', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'string', ['limit' => 36, 'null' => false])
            ->addColumn('tenant_id', 'string', ['limit' => 36, 'null' => false])
            ->addColumn('razao_social', 'string', ['limit' => 255])
            ->addColumn('nome_fantasia', 'string', ['limit' => 255])
            ->addColumn('cnpj', 'string', ['limit' => 14])
            ->addColumn('inscricao_estadual', 'string', ['limit' => 20, 'null' => true])
            ->addColumn('email', 'string', ['limit' => 255])
            ->addColumn('telefone', 'string', ['limit' => 20])
            ->addColumn('cep', 'string', ['limit' => 8])
            ->addColumn('logradouro', 'string', ['limit' => 255])
            ->addColumn('numero', 'string', ['limit' => 20])
            ->addColumn('complemento', 'string', ['limit' => 100, 'null' => true])
            ->addColumn('bairro', 'string', ['limit' => 100])
            ->addColumn('cidade', 'string', ['limit' => 100])
            ->addColumn('estado', 'string', ['limit' => 2])
            ->addColumn('responsavel_nome', 'string', ['limit' => 255])
            ->addColumn('responsavel_email', 'string', ['limit' => 255])
            ->addColumn('responsavel_telefone', 'string', ['limit' => 20])
            ->addColumn('observacoes', 'text', ['null' => true])
            ->addColumn('dados_adicionais', 'json', ['null' => true])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'timestamp', ['null' => true])
            ->addColumn('deleted_at', 'timestamp', ['null' => true])
            ->addIndex(['cnpj', 'tenant_id'], ['unique' => true])
            ->addForeignKey('tenant_id', 'tenants', 'id', ['delete' => 'CASCADE'])
            ->create();

        // Tabela de Processos Aduaneiros
        $this->table('customs_processes', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'string', ['limit' => 36, 'null' => false])
            ->addColumn('tenant_id', 'string', ['limit' => 36, 'null' => false])
            ->addColumn('client_id', 'string', ['limit' => 36, 'null' => false])
            ->addColumn('numero_processo', 'string', ['limit' => 50])
            ->addColumn('tipo', 'enum', ['values' => ['importacao', 'exportacao']])
            ->addColumn('modalidade', 'enum', ['values' => ['maritimo', 'aereo', 'rodoviario', 'ferroviario']])
            ->addColumn('status', 'string', ['limit' => 50])
            ->addColumn('data_inicio', 'date')
            ->addColumn('data_previsao', 'date', ['null' => true])
            ->addColumn('data_conclusao', 'date', ['null' => true])
            ->addColumn('ce_mercante', 'string', ['limit' => 100, 'null' => true])
            ->addColumn('freetime_inicial', 'date', ['null' => true])
            ->addColumn('freetime_final', 'date', ['null' => true])
            ->addColumn('valor_mercadoria', 'decimal', ['precision' => 15, 'scale' => 2, 'null' => true])
            ->addColumn('valor_frete', 'decimal', ['precision' => 15, 'scale' => 2, 'null' => true])
            ->addColumn('valor_seguro', 'decimal', ['precision' => 15, 'scale' => 2, 'null' => true])
            ->addColumn('valor_impostos', 'decimal', ['precision' => 15, 'scale' => 2, 'null' => true])
            ->addColumn('dados_adicionais', 'json', ['null' => true])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'timestamp', ['null' => true])
            ->addColumn('deleted_at', 'timestamp', ['null' => true])
            ->addIndex(['numero_processo', 'tenant_id'], ['unique' => true])
            ->addForeignKey('tenant_id', 'tenants', 'id', ['delete' => 'CASCADE'])
            ->addForeignKey('client_id', 'clients', 'id', ['delete' => 'CASCADE'])
            ->create();

        // Tabela de Andamentos
        $this->table('process_updates', ['id' => true, 'signed' => false])
            ->addColumn('process_id', 'string', ['limit' => 36, 'null' => false])
            ->addColumn('tipo', 'string', ['limit' => 50])
            ->addColumn('status', 'string', ['limit' => 50])
            ->addColumn('descricao', 'text')
            ->addColumn('notificar_cliente', 'boolean', ['default' => false])
            ->addColumn('notificado_em', 'timestamp', ['null' => true])
            ->addColumn('dados_extras', 'json', ['null' => true])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'timestamp', ['null' => true])
            ->addColumn('deleted_at', 'timestamp', ['null' => true])
            ->addForeignKey('process_id', 'customs_processes', 'id', ['delete' => 'CASCADE'])
            ->create();
    }
}
