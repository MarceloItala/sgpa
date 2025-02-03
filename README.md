# SGPA - Sistema de Gestão de Processos Aduaneiros

Sistema web multi-tenant para gestão de processos aduaneiros, desenvolvido para empresas de despachantes aduaneiros.

## Requisitos do Sistema

- PHP 8.2 ou superior
- MySQL 8.0 ou superior
- Nginx
- Composer
- Git

## Funcionalidades Principais

- Multi-tenant: cada empresa tem seu ambiente isolado
- Gestão de usuários com sistema de permissões
- Gestão de clientes
- Controle de processos aduaneiros (importação/exportação)
- Acompanhamento de andamentos
- Sistema de notificações

## Instalação

1. Clone o repositório:
```bash
git clone [URL_DO_REPOSITORIO]
cd sgpa
```

2. Instale as dependências:
```bash
composer install
```

3. Configure o ambiente:
```bash
cp .env.example .env
# Edite o arquivo .env com suas configurações
```

4. Configure o banco de dados:
```bash
vendor/bin/phinx migrate
```

5. Configure o Nginx:
```bash
cp nginx.conf.example /etc/nginx/sites-available/sgpa.conf
ln -s /etc/nginx/sites-available/sgpa.conf /etc/nginx/sites-enabled/
```

6. Reinicie o Nginx:
```bash
sudo service nginx restart
```

## Estrutura do Projeto

```
sgpa/
├── db/
│   ├── migrations/
│   └── seeds/
├── public/
│   └── index.php
├── src/
│   ├── Core/
│   ├── Models/
│   ├── Controllers/
│   └── Services/
├── templates/
├── tests/
├── vendor/
├── .env
├── .env.example
├── composer.json
├── nginx.conf.example
├── phinx.php
└── README.md
```

## Desenvolvimento

Para iniciar o ambiente de desenvolvimento:

1. Configure seu ambiente local:
```bash
composer install
cp .env.example .env
# Configure as variáveis de ambiente para desenvolvimento
```

2. Execute as migrações:
```bash
vendor/bin/phinx migrate
```

3. Inicie o servidor de desenvolvimento (opcional):
```bash
php -S localhost:8000 -t public
```

## Segurança

- Todas as senhas são armazenadas usando Argon2id
- Autenticação via JWT
- HTTPS obrigatório em produção
- Proteção contra CSRF
- Cookies seguros e HttpOnly
- Headers de segurança configurados no Nginx

## Contribuição

1. Fork o projeto
2. Crie sua branch de feature (`git checkout -b feature/AmazingFeature`)
3. Commit suas mudanças (`git commit -m 'Add some AmazingFeature'`)
4. Push para a branch (`git push origin feature/AmazingFeature`)
5. Abra um Pull Request

## Licença

Este projeto está sob a licença [LICENÇA]. Veja o arquivo `LICENSE` para mais detalhes.

## Suporte

Para suporte, envie um email para [EMAIL_SUPORTE] ou abra uma issue no GitHub.
