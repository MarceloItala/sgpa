<?php
declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

class AdminUserSeeder extends AbstractSeed
{
    public function run(): void
    {
        // Criar tenant padrão
        $tenants = $this->table('tenants');
        $tenantId = '00000000-0000-0000-0000-000000000000';
        $tenants->insert([
            'id' => $tenantId,
            'razao_social' => 'Administrador do Sistema',
            'nome_fantasia' => 'Admin',
            'cnpj' => '00000000000000',
            'email' => 'admin@sgpa.app.br',
            'telefone' => '0000000000',
            'status' => 'ativo',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => null
        ])->save();

        // Criar usuário admin
        $users = $this->table('users');
        $users->insert([
            'id' => '00000000-0000-0000-0000-000000000000',
            'tenant_id' => $tenantId,
            'name' => 'Administrador',
            'email' => 'admin@sgpa.app.br',
            'password' => password_hash('admin@123', PASSWORD_DEFAULT),
            'status' => 'ativo',
            'last_login' => null,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => null,
            'deleted_at' => null
        ])->save();

        // Criar papel de administrador
        $roles = $this->table('roles');
        $roles->insert([
            'name' => 'admin',
            'description' => 'Administrador do Sistema',
            'tenant_id' => null,
            'created_at' => date('Y-m-d H:i:s')
        ])->save();

        // Criar permissões básicas
        $permissions = $this->table('permissions');
        $permissionsData = [
            [
                'name' => 'admin.access',
                'description' => 'Acesso total ao sistema',
                'created_at' => date('Y-m-d H:i:s')
            ]
        ];
        $permissions->insert($permissionsData)->save();

        // Vincular permissões ao papel de admin
        $roleId = $roles->getAdapter()->getConnection()->lastInsertId();
        $permissionId = $permissions->getAdapter()->getConnection()->lastInsertId();

        $rolePermissions = $this->table('role_permissions');
        $rolePermissions->insert([
            'role_id' => $roleId,
            'permission_id' => $permissionId
        ])->save();

        // Vincular papel ao usuário admin
        $userRoles = $this->table('user_roles');
        $userRoles->insert([
            'user_id' => '00000000-0000-0000-0000-000000000000',
            'role_id' => $roleId
        ])->save();
    }
}
