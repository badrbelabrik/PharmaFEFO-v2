<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/autoloader.php';

use Controller\Web\AdminController;
use Controller\Web\AuthController;
use Controller\Web\DashboardController;
use Controller\Web\HomeController;
use Controller\Web\ReportController;
use Controller\Web\StockController;
use PharmaFEFOV2\Middleware\AuthMiddleware;
use PharmaFEFOV2\Middleware\RoleMiddleware;


$route = $_GET['route'] ?? 'home';

$authContr = new AuthController();
$homeContr = new HomeController();
$dashboardContr = new DashboardController();
$stockContr = new StockController();
$reportContr = new ReportController();
$adminContr = new AdminController();

switch ($route) {
    case 'home':
        $homeContr->index();
        break;

    case 'login':
        $authContr->login();
        break;

    case 'logout':
        AuthMiddleware::requireAuth();
        $authContr->logout();
        break;

    case 'dashboard':
        AuthMiddleware::requireAuth();
        $dashboardContr->index();
        break;

    case 'stock-receive':
        AuthMiddleware::requireAuth();
        RoleMiddleware::requireRoleHierarchy('preparer');  // preparer, pharmacist, admin
        $stockContr->receive();
        break;

    case 'stock-dispatch':
        AuthMiddleware::requireAuth();
        RoleMiddleware::requireRoleHierarchy('preparer');  // preparer, pharmacist, admin
        $stockContr->dispatch();
        break;

    case 'stock-alerts':
        AuthMiddleware::requireAuth();
        RoleMiddleware::requireRole(['pharmacist', 'admin']);  // pharmacist OR admin
        $stockContr->alerts();
        break;

    case 'stock-expired':
        AuthMiddleware::requireAuth();
        RoleMiddleware::requireRole(['pharmacist', 'admin']);  // pharmacist OR admin
        $stockContr->markAsExpired();
        break;

    case 'stock-return':
        AuthMiddleware::requireAuth();
        RoleMiddleware::requireRole(['pharmacist', 'admin']);
        $stockContr->returnToSupplier();
        break;

    case 'reports':
        AuthMiddleware::requireAuth();
        RoleMiddleware::requireRole(['pharmacist', 'admin']);
        $reportContr->index();
        break;

    case 'report-financial':
        AuthMiddleware::requireAuth();
        RoleMiddleware::requireRole('admin');  // Only admin
        $reportContr->financial();
        break;


    case 'admin-users':
        AuthMiddleware::requireAuth();
        RoleMiddleware::requireRole('admin');
        $adminContr->users();
        break;

    case 'admin-user-create':
        AuthMiddleware::requireAuth();
        RoleMiddleware::requireRole('admin');
        $adminContr->createUser();
        break;

    case 'admin-user-edit':
        AuthMiddleware::requireAuth();
        RoleMiddleware::requireRole('admin');
        $adminContr->editUser();
        break;

    case 'admin-user-delete':
        AuthMiddleware::requireAuth();
        RoleMiddleware::requireRole('admin');
        $adminContr->deleteUser();
        break;


    default:
        http_response_code(404);
        require_once __DIR__ . '/../templates/errors/404.php';
        break;
}