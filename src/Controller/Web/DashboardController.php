<?php

declare(strict_types=1);

namespace PharmaFEFOV2\Controller;


use PharmaFEFOV2\Repository\ProductRepository;
use PharmaFEFOV2\Repository\StockBatchRepository;

class StockController
{
    private ProductRepository $productRepo;
    private StockBatchRepository $stockBatchRepo;

    public function __construct() {
        $this->productRepo = new ProductRepository();
        $this->stockBatchRepo = new StockBatchRepository();
    }

    /**
     * Show receive form (HTML only)
     */
    public function receive(): void
    {
        $products = $this->productRepo->findAll();
        $currentUser = $_SESSION['user_firstname'] . ' ' . ($_SESSION['user_lastname'] ?? '');
        $userRole = $_SESSION['user_role'] ?? 'preparer';
        $currentPage = 'receive';

        require_once __DIR__ . '/../../../templates/dashboard/receive.php';
    }

    /**
     * Show dispatch form (HTML only)
     */
    public function dispatch(): void
    {
        $products = $this->productRepo->findAll();
        $selectedProductId = (int)($_GET['product_id'] ?? 0);
        $selectedBatch = null;
        $daysLeft = null;

        if ($selectedProductId > 0) {
            $selectedBatch = $this->stockBatchRepo->findEarliestExpiringBatch($selectedProductId);
            if ($selectedBatch) {
                $daysLeft = $selectedBatch->getDaysUntilExpiration();
            }
        }

        $currentUser = $_SESSION['user_firstname'] . ' ' . ($_SESSION['user_lastname'] ?? '');
        $userRole = $_SESSION['user_role'] ?? 'preparer';
        $currentPage = 'dispatch';

        require_once __DIR__ . '/../../../templates/dashboard/dispatch.php';
    }

    /**
     * Show alerts page (HTML only)
     */
    public function alerts(): void
    {
        $currentUser = $_SESSION['user_firstname'] . ' ' . ($_SESSION['user_lastname'] ?? '');
        $userRole = $_SESSION['user_role'] ?? 'pharmacist';
        $currentPage = 'alerts';

        require_once __DIR__ . '/../../../templates/dashboard/alerts.php';
    }

    /**
     * Show mark expired confirmation page (HTML only)
     */
    public function markAsExpired(): void
    {
        $batchId = (int)($_GET['id'] ?? 0);
        $batch = $this->stockBatchRepo->findById($batchId);

        if (!$batch) {
            $_SESSION['error'] = "Batch not found.";
            header('Location: index.php?route=stock-alerts');
            exit();
        }

        $currentUser = $_SESSION['user_firstname'] . ' ' . ($_SESSION['user_lastname'] ?? '');
        $userRole = $_SESSION['user_role'] ?? 'pharmacist';
        $currentPage = 'alerts';

        require_once __DIR__ . '/../../../templates/dashboard/mark-expired.php';
    }

    /**
     * Show supplier return page (HTML only)
     */
    public function returnToSupplier(): void
    {
        $eligibleBatches = $this->stockBatchRepo->getReturnEligibleBatches();
        $selectedBatchId = (int)($_GET['batch_id'] ?? 0);
        $selectedBatch = null;

        if ($selectedBatchId > 0) {
            $selectedBatch = $this->stockBatchRepo->findById($selectedBatchId);
        }

        $currentUser = $_SESSION['user_firstname'] . ' ' . ($_SESSION['user_lastname'] ?? '');
        $userRole = $_SESSION['user_role'] ?? 'pharmacist';
        $currentPage = 'returns';

        require_once __DIR__ . '/../../../templates/dashboard/return.php';
    }
}