<?php

namespace PharmaFEFOV2\Controller\Api;

use PharmaFEFOV2\Repository\ProductRepository;
use PharmaFEFOV2\Repository\StockBatchRepository;

class ApiDashboardController
{
    private StockBatchRepository $stockBatchRepo;
    private ProductRepository $productRepo;

    public function __construct() {
        $this->stockBatchRepo = new StockBatchRepository();
        $this->productRepo = new ProductRepository();
    }

    public function getBatches(): void
    {
        header('Content-Type: application/json');

        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            return;
        }

        $criteria = $_GET['filter'] ?? 'all';

        switch ($criteria) {
            case 'critical':
                $batches = $this->stockBatchRepo->findCriticalBatches();
                break;
            case 'warning':
                $batches = $this->stockBatchRepo->findWarningBatches();
                break;
            case 'healthy':
                $batches = $this->stockBatchRepo->findHealthyBatches();
                break;
            default:
                $batches = $this->stockBatchRepo->findAllWithCriticality();
                break;
        }

        $data = array_map(function($batch) {
            return $batch->jsonSerialize();
        }, $batches);

        $expiringNextMonth = $this->stockBatchRepo->getExpiringNextMonth();

        echo json_encode([
            'success' => true,
            'data' => $data,
            'count' => count($data),
            'expiring_next_month' => count($expiringNextMonth),
            'filters' => [
                'current' => $criteria,
                'available' => ['all', 'critical', 'warning', 'healthy']
            ]
        ]);
    }

    public function getStats(): void
    {
        header('Content-Type: application/json');

        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            return;
        }

        $allBatches = $this->stockBatchRepo->findAllWithCriticality();
        $critical = 0;
        $warning = 0;
        $healthy = 0;

        foreach ($allBatches as $batch) {
            $days = $batch->getDaysUntilExpiration();
            if ($days <= 30) {
                $critical++;
            } elseif ($days <= 90) {
                $warning++;
            } else {
                $healthy++;
            }
        }

        $expiringNextMonth = $this->stockBatchRepo->getExpiringNextMonth();
        $totalValue = $this->stockBatchRepo->getTotalStockValue();

        echo json_encode([
            'success' => true,
            'stats' => [
                'total_batches' => count($allBatches),
                'critical_count' => $critical,
                'warning_count' => $warning,
                'healthy_count' => $healthy,
                'total_value' => $totalValue,
                'expiring_next_month' => count($expiringNextMonth)
            ],
            'user' => [
                'name' => $_SESSION['user_firstname'] . ' ' . ($_SESSION['user_lastname'] ?? ''),
                'role' => $_SESSION['user_role']
            ]
        ]);
    }
}