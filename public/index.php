<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/autoloader.php';



use Controller\Web\DashboardController;
use Controller\Web\AdminController;
use Controller\Web\AuthController;
use Controller\Web\HomeController;
use Controller\Web\ReportController;
use Controller\Web\StockController;
use PharmaFEFOV2\Controller\Api\ApiDashboardController;
use PharmaFEFOV2\Controller\Api\ApiStockController;
use PharmaFEFOV2\Middleware\AuthMiddleware;
use PharmaFEFOV2\Middleware\RoleMiddleware;


$route = $_GET['route'] ?? 'home';
$apiAction = $_GET['action'] ?? '';

$authContr = new AuthController();
$homeContr = new HomeController();
$dashboardContr = new DashboardController();
$stockContr = new StockController();
$reportContr = new ReportController();
$adminContr = new AdminController();

$apiStock = new ApiStockController();
$apiDashboard = new ApiDashboardController();
// ============================================
// API ROUTES (Return JSON only)
// ============================================
if ($route === 'api') {

    switch ($apiAction) {
        // US 1.1: Async stock reception
        case 'receive':
            $apiStock->receive();
            break;

        // US 3.1: Async dispense (EPIC 3)
        case 'dispense':
            $apiStock->dispense();
            break;

        // US 4.1: Async mark expired (EPIC 4)
        case 'expired':
            $apiStock->markExpired();
            break;

        // Bonus: Supplier return
        case 'return':
            $apiStock->returnBatch();
            break;

        // US 2.1: Get filtered batches
        case 'batches':
            $apiDashboard->getBatches();
            break;

        // US 2.2: Get dashboard stats
        case 'stats':
            $apiDashboard->getStats();
            break;

        default:
            http_response_code(404);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'API endpoint not found']);
            break;
    }
    exit;
}

// ============================================
// WEB ROUTES (Return HTML)
// ============================================

$authContr = new AuthController();
$homeContr = new HomeController();
$dashboardContr = new DashboardController();
$stockContr = new StockController();

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
    case 'stock-dispatch' :
        AuthMiddleware::requireAuth();
        $stockContr->dispatch();
        break;
    // US 1.1: Receive form (HTML)
    case 'stock-receive':
        AuthMiddleware::requireAuth();
        RoleMiddleware::requireRoleHierarchy('preparer');
        $stockContr->receive();
        break;

    default:
        http_response_code(404);
        require_once __DIR__ . '/../templates/errors/404.php';
        break;
}