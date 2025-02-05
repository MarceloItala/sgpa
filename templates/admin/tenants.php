<?php
// Verifica se o usuário está logado e é admin
$currentUser = (new SGPA\Core\Auth())->getCurrentUser();
if (!$currentUser || $currentUser->getRole() !== 'admin') {
    header('Location: /login');
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administração de Tenants - SGPA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <?php include __DIR__ . '/../partials/navbar.php'; ?>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Gestão de Tenants</h1>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createTenantModal">
                <i class="bi bi-plus-lg"></i> Novo Tenant
            </button>
        </div>

        <div class="alert alert-success" id="successAlert" style="display: none;"></div>
        <div class="alert alert-danger" id="errorAlert" style="display: none;"></div>

        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Razão Social</th>
                                <th>Nome Fantasia</th>
                                <th>CNPJ</th>
                                <th>Email</th>
                                <th>Status</th>
                                <th>Criado em</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody id="tenantsTable">
                            <!-- Preenchido via JavaScript -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Criação de Tenant -->
    <div class="modal fade" id="createTenantModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Novo Tenant</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="createTenantForm">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Razão Social</label>
                                <input type="text" class="form-control" name="corporate_name" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Nome Fantasia</label>
                                <input type="text" class="form-control" name="trade_name" required>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="form-label">CNPJ</label>
                                <input type="text" class="form-control" name="cnpj" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Telefone</label>
                                <input type="text" class="form-control" name="phone" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" name="email" required>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Endereço</label>
                                <input type="text" class="form-control" name="address" required>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">CEP</label>
                                <input type="text" class="form-control" name="zip_code" required>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Cidade</label>
                                <input type="text" class="form-control" name="city" required>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Estado</label>
                                <input type="text" class="form-control" name="state" required>
                            </div>
                        </div>
                        <hr>
                        <h6>Dados do Administrador</h6>
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="form-label">Nome</label>
                                <input type="text" class="form-control" name="admin_name" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" name="admin_email" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Senha</label>
                                <input type="password" class="form-control" name="admin_password" required>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" onclick="createTenant()">Criar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Detalhes do Tenant -->
    <div class="modal fade" id="tenantDetailsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detalhes do Tenant</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="tenantDetails">
                        <!-- Preenchido via JavaScript -->
                    </div>
                    <hr>
                    <h6>Usuários</h6>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Nome</th>
                                    <th>Email</th>
                                    <th>Papel</th>
                                    <th>Status</th>
                                    <th>Último Login</th>
                                </tr>
                            </thead>
                            <tbody id="tenantUsers">
                                <!-- Preenchido via JavaScript -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Função para carregar a lista de tenants
        async function loadTenants() {
            try {
                const response = await fetch('/api/admin/tenants');
                const tenants = await response.json();
                
                const tbody = document.getElementById('tenantsTable');
                tbody.innerHTML = '';
                
                tenants.forEach(tenant => {
                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td>${tenant.corporate_name}</td>
                        <td>${tenant.trade_name}</td>
                        <td>${tenant.cnpj}</td>
                        <td>${tenant.email}</td>
                        <td><span class="badge bg-${tenant.status === 'active' ? 'success' : 'danger'}">${tenant.status}</span></td>
                        <td>${new Date(tenant.created_at).toLocaleDateString('pt-BR')}</td>
                        <td>
                            <button class="btn btn-sm btn-info" onclick="viewTenant('${tenant.id}')">
                                <i class="bi bi-eye"></i>
                            </button>
                            <button class="btn btn-sm btn-primary" onclick="editTenant('${tenant.id}')">
                                <i class="bi bi-pencil"></i>
                            </button>
                        </td>
                    `;
                    tbody.appendChild(tr);
                });
            } catch (error) {
                showError('Erro ao carregar tenants');
                console.error(error);
            }
        }

        // Função para criar um novo tenant
        async function createTenant() {
            try {
                const form = document.getElementById('createTenantForm');
                const formData = new FormData(form);
                const data = Object.fromEntries(formData.entries());
                
                const response = await fetch('/api/admin/tenants', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(data)
                });
                
                if (!response.ok) {
                    const error = await response.json();
                    throw new Error(error.error || 'Erro ao criar tenant');
                }
                
                const result = await response.json();
                showSuccess('Tenant criado com sucesso');
                
                // Fecha o modal e recarrega a lista
                const modal = bootstrap.Modal.getInstance(document.getElementById('createTenantModal'));
                modal.hide();
                form.reset();
                loadTenants();
                
            } catch (error) {
                showError(error.message);
                console.error(error);
            }
        }

        // Função para visualizar detalhes do tenant
        async function viewTenant(id) {
            try {
                const response = await fetch(`/api/admin/tenants/${id}`);
                const data = await response.json();
                
                const details = document.getElementById('tenantDetails');
                details.innerHTML = `
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Razão Social:</strong> ${data.company.corporate_name}</p>
                            <p><strong>Nome Fantasia:</strong> ${data.company.trade_name}</p>
                            <p><strong>CNPJ:</strong> ${data.company.cnpj}</p>
                            <p><strong>Email:</strong> ${data.company.email}</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Endereço:</strong> ${data.company.address}</p>
                            <p><strong>Cidade:</strong> ${data.company.city} - ${data.company.state}</p>
                            <p><strong>CEP:</strong> ${data.company.zip_code}</p>
                            <p><strong>Telefone:</strong> ${data.company.phone}</p>
                        </div>
                    </div>
                `;
                
                const tbody = document.getElementById('tenantUsers');
                tbody.innerHTML = '';
                
                data.users.forEach(user => {
                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td>${user.name}</td>
                        <td>${user.email}</td>
                        <td>${user.role}</td>
                        <td><span class="badge bg-${user.status === 'active' ? 'success' : 'danger'}">${user.status}</span></td>
                        <td>${user.last_login ? new Date(user.last_login).toLocaleString('pt-BR') : 'Nunca'}</td>
                    `;
                    tbody.appendChild(tr);
                });
                
                const modal = new bootstrap.Modal(document.getElementById('tenantDetailsModal'));
                modal.show();
                
            } catch (error) {
                showError('Erro ao carregar detalhes do tenant');
                console.error(error);
            }
        }

        // Funções de feedback
        function showSuccess(message) {
            const alert = document.getElementById('successAlert');
            alert.textContent = message;
            alert.style.display = 'block';
            setTimeout(() => alert.style.display = 'none', 3000);
        }

        function showError(message) {
            const alert = document.getElementById('errorAlert');
            alert.textContent = message;
            alert.style.display = 'block';
            setTimeout(() => alert.style.display = 'none', 3000);
        }

        // Carrega a lista inicial
        document.addEventListener('DOMContentLoaded', loadTenants);
    </script>
</body>
</html>
