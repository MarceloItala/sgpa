<?php
// Verifica se o usuário está logado
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user'])) {
    header('Location: /login');
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - SGPA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            font-size: .875rem;
            padding-top: 60px;
        }

        /* Navbar */
        .navbar {
            height: 60px;
            padding: 0.75rem 1rem;
            border-bottom: 1px solid #e9ecef;
            background-color: #343a40;
        }

        .navbar-brand {
            padding: 0.75rem;
            color: #fff;
        }

        /* Sidebar */
        .sidebar {
            position: fixed;
            top: 60px; /* Altura do navbar */
            bottom: 0;
            left: 0;
            z-index: 100;
            padding: 20px 0;
            box-shadow: inset -1px 0 0 rgba(0, 0, 0, .1);
            background-color: #f8f9fa;
            width: 240px;
            transition: all 0.3s ease-in-out;
        }

        .sidebar-sticky {
            position: sticky;
            top: 20px;
            height: calc(100vh - 80px);
            overflow-x: hidden;
            overflow-y: auto;
        }

        .sidebar .nav-link {
            font-weight: 500;
            color: #333;
            padding: 0.8rem 1rem;
            display: flex;
            align-items: center;
        }

        .sidebar .nav-link i {
            margin-right: 8px;
            font-size: 1.1rem;
            color: #666;
        }

        .sidebar .nav-link:hover {
            color: #007bff;
            background-color: rgba(0, 123, 255, 0.1);
        }

        .sidebar .nav-link.active {
            color: #007bff;
            background-color: rgba(0, 123, 255, 0.15);
        }

        .sidebar .nav-link:hover i,
        .sidebar .nav-link.active i {
            color: #007bff;
        }

        /* Content */
        .main-content {
            margin-left: 240px;
            padding: 20px;
            transition: margin-left 0.3s ease-in-out;
        }

        /* Cards */
        .card {
            margin-bottom: 1rem;
            border: none;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            transition: transform 0.2s ease-in-out;
        }

        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .card-title {
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .card-text.display-4 {
            font-size: 2rem;
            font-weight: 600;
            line-height: 1.2;
            margin: 0;
        }

        /* Responsive */
        @media (max-width: 767.98px) {
            body {
                padding-top: 60px;
            }

            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
                top: 0;
                padding: 10px;
            }

            .sidebar-sticky {
                height: auto;
                position: relative;
                top: 0;
            }

            .main-content {
                margin-left: 0;
                padding: 15px;
            }

            .navbar-brand {
                font-size: 1rem;
                padding: 0.5rem;
            }

            .card {
                margin-bottom: 15px;
            }

            .card-title {
                font-size: 0.8rem;
            }

            .card-text.display-4 {
                font-size: 1.5rem;
            }
        }

        /* Toggle Sidebar */
        .sidebar-toggle {
            display: none;
        }

        @media (max-width: 767.98px) {
            .sidebar-toggle {
                display: block;
                position: fixed;
                top: 12px;
                left: 10px;
                z-index: 1031;
                padding: 5px 10px;
                background-color: transparent;
                border: none;
                color: white;
            }

            .sidebar.collapsed {
                display: none;
            }

            .main-content.expanded {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
    <button class="sidebar-toggle" onclick="toggleSidebar()">
        <i class="bi bi-list"></i>
    </button>

    <nav class="navbar navbar-dark fixed-top">
        <div class="container-fluid">
            <a class="navbar-brand mx-auto" href="#">SGPA - Sistema de Gestão de Processos Aduaneiros</a>
            <div class="d-flex">
                <span class="navbar-text me-3 text-white">
                    <i class="bi bi-person-circle"></i>
                    <?php echo htmlspecialchars($_SESSION['user']['name']); ?>
                </span>
                <button class="btn btn-outline-light btn-sm" onclick="logout()">
                    <i class="bi bi-box-arrow-right"></i>
                    Sair
                </button>
            </div>
        </div>
    </nav>

    <nav id="sidebar" class="sidebar">
        <div class="sidebar-sticky">
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link active" href="/dashboard">
                        <i class="bi bi-speedometer2"></i>
                        Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/clients">
                        <i class="bi bi-people"></i>
                        Clientes
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/processes">
                        <i class="bi bi-folder2"></i>
                        Processos
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/users">
                        <i class="bi bi-person"></i>
                        Usuários
                    </a>
                </li>
            </ul>
        </div>
    </nav>

    <main id="main-content" class="main-content">
        <div class="container-fluid">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
                <h1 class="h2">Dashboard</h1>
            </div>

            <div class="row">
                <div class="col-12 col-sm-6 col-lg-3 mb-4">
                    <div class="card text-white bg-primary h-100">
                        <div class="card-body">
                            <h5 class="card-title">Processos Ativos</h5>
                            <p class="card-text display-4">0</p>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-lg-3 mb-4">
                    <div class="card text-white bg-success h-100">
                        <div class="card-body">
                            <h5 class="card-title">Clientes</h5>
                            <p class="card-text display-4">0</p>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-lg-3 mb-4">
                    <div class="card text-white bg-warning h-100">
                        <div class="card-body">
                            <h5 class="card-title">Andamentos Hoje</h5>
                            <p class="card-text display-4">0</p>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-lg-3 mb-4">
                    <div class="card text-white bg-info h-100">
                        <div class="card-body">
                            <h5 class="card-title">Usuários Ativos</h5>
                            <p class="card-text display-4">0</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('main-content');
            sidebar.classList.toggle('collapsed');
            mainContent.classList.toggle('expanded');
        }

        async function logout() {
            try {
                const response = await fetch('/api/auth/logout', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    }
                });
                
                if (!response.ok) {
                    throw new Error('Erro ao fazer logout');
                }
                
                window.location.href = '/login';
            } catch (error) {
                alert(error.message);
            }
        }
    </script>
</body>
</html>
