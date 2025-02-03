<?php
class Router {
    public function route() {
        $uri = $_SERVER['REQUEST_URI'];
        switch ($uri) {
            case '/':
            case '/login':
                include 'templates/login.php';
                break;
            // Adicione mais rotas conforme necessário
            case '/clients':
                include 'templates/clients.php';
                break;
            case '/customs_processes':
                include 'templates/customs_processes.php';
                break;
            case '/process_updates':
                include 'templates/process_updates.php';
                break;
            case '/permissions':
                include 'templates/permissions.php';
                break;
            default:
                http_response_code(404);
                echo "404 Not Found";
                break;
        }
    }
}
?>
