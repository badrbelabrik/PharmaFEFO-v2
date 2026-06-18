<?php

declare(strict_types=1);

namespace PharmaFEFOV2\Controller\Api;

use PharmaFEFOV2\Repository\ProductRepository;
use PharmaFEFOV2\Repository\StockBatchRepository;
use PharmaFEFOV2\Service\StockBatchService;

class ApiDashboardController
{
    private StockBatchRepository $stockBatchRepo;
    private ProductRepository $productRepo;

    public function __construct() {
        $this->stockBatchRepo = new StockBatchRepository();
        $this->productRepo = new ProductRepository();
    }

    /**
     * GET /api?action=batches&filter=all|critical|warning|healthy
     * Returns filtered batches with statistics
     * Uses StockBatchService for business logic
     */
    public function getBatches(): void
    {
        header('Content-Type: application/json');

        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            return;
        }

        $currentFilter = $_GET['filter'] ?? 'all';

        // Get all batches with criticality
        $allBatches = $this->stockBatchRepo->findAllWithCriticality();

        // Use Service for statistics
        $stats = StockBatchService::getBatchStatistics($allBatches);

        // Get filtered batches based on criteria
        $displayBatches = match($currentFilter) {
            'critical' => $stats['critical'],
            'warning' => $stats['warning'],
            'healthy' => $stats['healthy'],
            default => $allBatches
        };

        // Get expiring next month count
        $expiringNextMonth = $this->stockBatchRepo->getExpiringNextMonth();

        // Serialize batches for JSON response
        $data = array_map(function($batch) {
            return [
                'id' => $batch->getId(),
                'lot_number' => $batch->getLotNumber(),
                'quantity' => $batch->getQuantity(),
                'purchase_price' => $batch->getPurchasePrice(),
                'status' => $batch->getStatus()->value,
                'status_label' => $batch->getStatus()->getLabel(),
                'expiration_date' => $batch->getExpirationDate()->format('Y-m-d'),
                'expiration_formatted' => $batch->getExpirationDate()->format('F j, Y'),
                'days_until_expiration' => StockBatchService::getDaysUntilExpiration($batch),
                'criticality' => StockBatchService::getCriticalityLevel($batch),
                'product' => [
                    'id' => $batch->getProduct()->getId(),
                    'name' => $batch->getProduct()->getName(),
                    'serial_number' => $batch->getProduct()->getSerialNumber(),
                    'description' => $batch->getProduct()->getDescription()
                ]
            ];
        }, $displayBatches);

        echo json_encode([
            'success' => true,
            'data' => $data,
            'count' => count($data),
            'stats' => [
                'total_batches' => $stats['totalBatches'],
                'critical_count' => $stats['criticalCount'],
                'warning_count' => $stats['warningCount'],
                'healthy_count' => $stats['healthyCount'],
                'expiring_next_month' => count($expiringNextMonth)
            ],
            'filters' => [
                'current' => $currentFilter,
                'available' => ['all', 'critical', 'warning', 'healthy']
            ]
        ]);
    }

    /**
     * GET /api?action=stats
     * Returns dashboard statistics
     * Uses StockBatchService for business logic
     */
    public function getStats(): void
    {
        header('Content-Type: application/json');

        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            return;
        }

        // Get all batches
        $allBatches = $this->stockBatchRepo->findAllWithCriticality();

        // Use Service for statistics
        $stats = StockBatchService::getBatchStatistics($allBatches);
        $totalStockValue = StockBatchService::getTotalValueForBatches($allBatches);

        // Get expiring next month count
        $expiringNextMonth = $this->stockBatchRepo->getExpiringNextMonth();

        // Get unread notifications
        $unreadNotifications = $this->stockBatchRepo->getUnreadNotifications();
        $unreadNotificationsCount = count($unreadNotifications);

        // Get total products count
        $totalProducts = count($this->productRepo->findAll());

        echo json_encode([
            'success' => true,
            'stats' => [
                'total_batches' => $stats['totalBatches'],
                'critical_count' => $stats['criticalCount'],
                'warning_count' => $stats['warningCount'],
                'healthy_count' => $stats['healthyCount'],
                'total_products' => $totalProducts,
                'total_stock_value' => $totalStockValue,
                'expiring_next_month' => count($expiringNextMonth),
                'unread_notifications' => $unreadNotificationsCount
            ],
            'user' => [
                'name' => $_SESSION['user_firstname'] . ' ' . ($_SESSION['user_lastname'] ?? ''),
                'role' => $_SESSION['user_role'] ?? 'preparer'
            ]
        ]);
    }
}