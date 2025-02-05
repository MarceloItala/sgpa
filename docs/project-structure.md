# Estrutura do Projeto SGPA

Este documento descreve a estrutura de diretórios e a organização do código do Sistema de Gestão de Processos Aduaneiros (SGPA).

## Estrutura de Diretórios

```
/var/www/sgpa/
├── db/                    # Scripts e migrações do banco de dados
│   ├── migrations/       # Migrações do banco de dados
│   └── seeds/           # Seeds para popular o banco
├── docs/                 # Documentação do projeto
│   └── architecture/    # Documentação da arquitetura
├── public/               # Arquivos públicos acessíveis via web
│   ├── assets/         # Assets estáticos (CSS, JS, imagens)
│   └── index.php       # Ponto de entrada da aplicação
├── src/                  # Código fonte da aplicação
│   ├── Controllers/    # Controladores da aplicação
│   ├── Core/          # Classes do núcleo do sistema
│   ├── Exceptions/    # Classes de exceção
│   ├── Middleware/    # Middlewares da aplicação
│   ├── Models/        # Modelos da aplicação
│   └── routes.php     # Definição das rotas
├── templates/            # Templates/Views da aplicação
│   └── admin/         # Templates específicos do admin
├── vendor/              # Dependências do Composer
├── .env                 # Configurações do ambiente
├── .env.example         # Exemplo de configurações
├── composer.json        # Dependências e configuração do projeto
└── README.md           # Documentação principal
```

## Principais Componentes

### 1. Controllers (/src/Controllers/)
Controladores que gerenciam a lógica de negócios e o fluxo da aplicação:
- `AdminController.php` - Gerenciamento de tenants e funções administrativas
- `AuthController.php` - Autenticação e controle de acesso
- `UserController.php` - Gerenciamento de usuários
- `CompanyController.php` - Gerenciamento de empresas
- `ClientController.php` - Gerenciamento de clientes

### 2. Core (/src/Core/)
Classes fundamentais do sistema:
- `Router.php` - Gerenciamento de rotas
- `Database.php` - Conexão e operações com banco de dados
- `Auth.php` - Serviços de autenticação
- `TenantScope.php` - Implementação do escopo multi-tenant
- `TenantMigrationManager.php` - Gerenciamento de migrações por tenant

### 3. Models (/src/Models/)
Modelos que representam as entidades do sistema:
- `BaseModel.php` - Classe base para todos os modelos
- `User.php` - Modelo de usuário
- `Company.php` - Modelo de empresa
- `Client.php` - Modelo de cliente

### 4. Templates (/templates/)
Views da aplicação usando PHP puro com HTML:
- `login.php` - Página de login
- `dashboard.php` - Dashboard principal
- `admin/tenants.php` - Gerenciamento de tenants
- Outros templates específicos de cada funcionalidade

### 5. Middleware (/src/Middleware/)
Camada de middleware para processamento de requisições:
- `AuthMiddleware.php` - Validação de autenticação
- `TenantMiddleware.php` - Processamento do contexto multi-tenant

## Convenções de Código

1. **Namespaces**: Todos os arquivos seguem a estrutura PSR-4 com namespace base `SGPA`
2. **Autoloading**: Gerenciado pelo Composer
3. **Estilo de Código**: Segue PSR-12
4. **Banco de Dados**: Usa UUIDs para IDs e inclui campos de auditoria (created_at, updated_at, deleted_at)

## Configuração Multi-tenant

O sistema utiliza uma arquitetura multi-tenant baseada em subdomínios:
- `admin.sgpa.app.br` - Acesso administrativo
- `{tenant}.sgpa.app.br` - Acesso específico do tenant

Cada tenant possui:
- Banco de dados isolado logicamente (mesmo banco físico, filtrado por tenant_id)
- Arquivos específicos (quando necessário)
- Configurações próprias

## Credenciais Padrão

Usuário Administrador:
- Email: admin@sgpa.app.br
- Senha: Bit@12120
- Tenant ID: 00000000-0000-0000-0000-000000000000
