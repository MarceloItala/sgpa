<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - SGPA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #f5f5f5;
        }
        .login-container {
            width: 100%;
            max-width: 400px;
            padding: 2rem;
            background: white;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .login-logo {
            text-align: center;
            margin-bottom: 2rem;
        }
        .login-logo h1 {
            color: #333;
            font-size: 2rem;
        }
        .alert {
            display: none;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-logo">
            <h1>SGPA</h1>
            <p class="text-muted">Sistema de Gestão de Processos Aduaneiros</p>
        </div>

        <div class="alert alert-danger" id="error-message" role="alert"></div>

        <form id="login-form" class="needs-validation" novalidate>
            <div class="mb-3">
                <label for="email" class="form-label">Email:</label>
                <input type="email" class="form-control" id="email" name="email" value="admin@sgpa.app.br" required>
                <div class="invalid-feedback">
                    Por favor, informe um email válido.
                </div>
            </div>

            <div class="mb-3">
                <label for="password" class="form-label">Senha:</label>
                <input type="password" class="form-control" id="password" name="password" value="Bit@12120" required>
                <div class="invalid-feedback">
                    Por favor, informe sua senha.
                </div>
            </div>

            <button type="submit" class="btn btn-primary w-100" id="submit-button">
                Entrar
            </button>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('login-form').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            // Reset form validation
            this.classList.remove('was-validated');
            
            // Hide error message
            document.getElementById('error-message').style.display = 'none';
            
            // Get form data
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            
            // Validate form
            if (!email || !password) {
                this.classList.add('was-validated');
                return;
            }
            
            // Disable submit button
            const submitButton = document.getElementById('submit-button');
            const originalButtonText = submitButton.innerHTML;
            submitButton.disabled = true;
            submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Aguarde...';
            
            try {
                console.log('Enviando requisição de login...');
                // Send login request
                const response = await fetch('/api/auth/login', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ email, password })
                });
                
                console.log('Resposta recebida:', response.status);
                const data = await response.json();
                console.log('Dados recebidos:', data);
                
                if (!response.ok) {
                    throw new Error(data.error || 'Erro ao fazer login');
                }
                
                console.log('Login bem sucedido, redirecionando...');
                // Redirect to dashboard on success
                window.location.href = '/dashboard';
                
            } catch (error) {
                // Show error message
                const errorMessage = document.getElementById('error-message');
                errorMessage.textContent = error.message;
                errorMessage.style.display = 'block';
                
                // Re-enable submit button
                submitButton.disabled = false;
                submitButton.innerHTML = originalButtonText;
            }
        });
    </script>
</body>
</html>
