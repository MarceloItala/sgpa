# Arquitetura Multi-Tenant SGPA

## 1. Visão Geral
O SGPA utiliza uma arquitetura multi-tenant baseada em subdomínios, onde cada cliente (tenant) tem seu próprio subdomínio personalizado (exemplo: cliente.sgpa.app.br).

### 1.1 Domínios do Sistema
- **admin.sgpa.app.br**: Acesso administrativo do sistema
- **sgpa.app.br**: Landing page do produto
- **[tenant].sgpa.app.br**: Acesso específico de cada cliente

## 2. Estrutura do Banco de Dados

### 2.1 Tabela de Tenants
```sql
CREATE TABLE tenants (
    id VARCHAR(36) PRIMARY KEY,
    subdomain VARCHAR(50) UNIQUE,
    razao_social VARCHAR(255),
    nome_fantasia VARCHAR(255),
    cnpj VARCHAR(14) UNIQUE,
    email VARCHAR(255),
    telefone VARCHAR(20),
    status ENUM('ativo','inativo','suspenso'),
    logo_path VARCHAR(255) NULL,
    db_name VARCHAR(50) NULL,
    db_user VARCHAR(50) NULL,
    db_password VARCHAR(255) NULL,
    db_host VARCHAR(255) NULL DEFAULT 'localhost',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL
);
```

### 2.2 Banco de Dados por Tenant
Cada tenant possui seu próprio banco de dados para isolamento completo dos dados.

#### Tabela de Controle de Migrations
```sql
CREATE TABLE migration_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    migration_name VARCHAR(255) NOT NULL,
    batch INT NOT NULL,
    executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

## 3. Gerenciamento de Migrations

### 3.1 Estrutura de Diretórios
```
/database
  /migrations
    /tenant      # Migrations específicas dos tenants
    /system      # Migrations do sistema principal
```

### 3.2 Processo de Migrations
1. **Novo Tenant**:
   - Criar novo banco de dados
   - Executar todas as migrations da pasta tenant
   - Registrar migrations executadas

2. **Atualização de Tenants**:
   - Verificar tenants ativos
   - Para cada tenant:
     - Conectar ao banco
     - Verificar migrations pendentes
     - Executar migrations necessárias
     - Registrar execução

## 4. Gestão de Arquivos

### 4.1 Logos dos Tenants
- Diretório: `/var/www/sgpa/public/tenant_logos`
- Formato: `[tenant_id].[extensão]`
- Tamanho máximo: 2MB
- Formatos permitidos: jpg, png, webp

## 5. Classes Principais

### 5.1 TenantScope
Responsável por:
- Identificar tenant atual via subdomínio
- Gerenciar conexão com banco do tenant
- Manter contexto do tenant durante a requisição

### 5.2 TenantMigrationManager
Responsável por:
- Executar migrations em novos tenants
- Atualizar bancos de tenants existentes
- Controlar versão do banco de cada tenant

### 5.3 TenantMiddleware
Responsável por:
- Interceptar todas as requisições
- Identificar tenant pelo subdomínio
- Configurar o TenantScope para a requisição
- Retornar 404 para tenants inexistentes ou inativos

## 6. Comandos CLI

### 6.1 Migrations
```bash
php artisan tenant:migrate            # Executa migrations pendentes em todos os tenants
php artisan tenant:migrate --tenant=X # Executa migrations para um tenant específico
php artisan tenant:status            # Mostra status das migrations por tenant
```

### 6.2 Gestão de Tenants
```bash
php artisan tenant:create            # Cria novo tenant
php artisan tenant:list              # Lista todos os tenants
php artisan tenant:disable           # Desativa um tenant
```

## 7. Segurança

### 7.1 Isolamento de Dados
- Banco de dados separado por tenant
- Usuário de banco específico por tenant
- Senhas criptografadas no banco principal

### 7.2 Acesso
- Validação de subdomínio em cada requisição
- Verificação de status do tenant
- Autenticação específica por tenant

## 8. Processo de Criação de Novo Tenant

1. **Registro Inicial**
   - Inserir dados básicos do tenant
   - Gerar subdomínio único
   - Criar registro na tabela tenants

2. **Configuração do Banco**
   - Criar novo banco de dados
   - Criar usuário específico
   - Executar migrations iniciais

3. **Configuração do Ambiente**
   - Criar diretório para arquivos do tenant
   - Configurar domínio/subdomínio
   - Gerar certificado SSL se necessário

4. **Finalização**
   - Criar usuário administrativo do tenant
   - Enviar credenciais por email
   - Ativar tenant no sistema
