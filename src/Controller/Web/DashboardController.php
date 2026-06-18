<?php

namespace Controller\Web;

use PharmaFEFOV2\Repository\ProductRepository;
use PharmaFEFOV2\Repository\StockBatchRepository;
use PharmaFEFOV2\Service\StockBatchService;

class DashboardController
{
    private StockBatchRepository $stockBatchRepo;
    private ProductRepository $productRepo;

    public function __construct() {
        $this->stockBatchRepo = new StockBatchRepository();
        $this->productRepo = new ProductRepository();
    }

    public function index(): void {
        $currentUser = $_SESSION['user_firstname'] . ' ' . ($_SESSION['user_lastname'] ?? 'User');
        $userRole = $_SESSION['user_role'] ?? 'preparer';
        $currentPage = 'dashboard';

        require_once __DIR__ . '/../../../templates/dashboard/dashboard.php';
    }
}