<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/autoloader.php';

use PharmaFEFOV2\Controller\AuthController;
use PharmaFEFOV2\Controller\DashboardController;
use PharmaFEFOV2\Controller\HomeController;
use PharmaFEFOV2\Controller\StockController;
use PharmaFEFOV2\Controller\ReportController;
use PharmaFEFOV2\Middleware\AuthMiddleware;
use PharmaFEFOV2\Middleware\RoleMiddleware;

$route = $_GET['route'] ?? 'home';

$authContr = new AuthController();
$homeContr = new HomeController();
$dashboardContr = new DashboardController();
$stockContr = new StockController();
$reportContr = new ReportController();


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
        RoleMiddleware::requireRole('preparator');
        $stockContr->receive();
        break;

    case 'stock-dispatch':
        AuthMiddleware::requireAuth();
        RoleMiddleware::requireRole('preparator');
        $stockContr->dispatch();
        break;

    case 'stock-alerts':
        AuthMiddleware::requireAuth();
        RoleMiddleware::requireRole('pharmacist');
        $stockContr->alerts();
        break;

    case 'stock-expired':
        AuthMiddleware::requireAuth();
        RoleMiddleware::requireRole('pharmacist');
        $stockContr->markAsExpired();
        break;

    case 'reports':
        AuthMiddleware::requireAuth();
        RoleMiddleware::requireRole('pharmacist');
        $reportContr->index();
        break;

    case 'report-financial':
        AuthMiddleware::requireAuth();
        RoleMiddleware::requireRole('admin');
        $reportContr->financial();
        break;

    default:
        http_response_code(404);
        require_once __DIR__ . '/../templates/errors/404.php';
        break;
}