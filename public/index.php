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

        case 'receive':
            $apiStock->receive();
            break;
        case 'products':
            $apiStock->getProducts();
            break;

        case 'dispense':
            $apiStock->dispense();
            break;

        case 'expired':
            $apiStock->markExpired();
            break;

        case 'return':
            $apiStock->returnBatch();
            break;


        case 'batches':
            $apiDashboard->getBatches();
            break;


        case 'stats':
            $apiDashboard->getStats();
            break;

        // Admin only
        case 'loss-report':
            $apiStock->lossReport();
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

switch ($route) {
    // ========== PUBLIC ROUTES ==========
    case 'home':
        $homeContr->index();
        break;

    case 'login':
        $authContr->login();
        break;

    // ========== AUTHENTICATED ROUTES ==========
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
        RoleMiddleware::requireAnyRole(['preparer', 'pharmacist', 'admin']);
        $stockContr->receive();
        break;

    // ========== EPIC 3: FEFO Dispensing (US 3.1) ==========
    case 'stock-dispatch':
        AuthMiddleware::requireAuth();
        RoleMiddleware::requireAnyRole(['preparer', 'pharmacist', 'admin']);
        $stockContr->dispatch();
        break;


    case 'stock-alerts':
        AuthMiddleware::requireAuth();
        RoleMiddleware::requireAnyRole(['pharmacist', 'admin']);
        $stockContr->alerts();
        break;

    case 'stock-expired':
        AuthMiddleware::requireAuth();
        RoleMiddleware::requireAnyRole(['pharmacist', 'admin']);
        $stockContr->markAsExpired();
        break;

    case 'stock-return':
        AuthMiddleware::requireAuth();
        RoleMiddleware::requireAnyRole(['pharmacist', 'admin']);
        $stockContr->returnToSupplier();
        break;


    // ========== 404 ERROR ==========
    default:
        http_response_code(404);
        require_once __DIR__ . '/../templates/errors/404.php';
        break;
}