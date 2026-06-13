<?php

declare(strict_types=1);

namespace PharmaFEFOV2\Controller;

use DateTime;
use PharmaFEFOV2\Repository\StockBatchRepository;
use PharmaFEFOV2\Repository\ProductRepository;
use PharmaFEFOV2\Entity\StockBatch;
use PharmaFEFOV2\Enum\BatchStatus;
use PharmaFEFOV2\Service\StockBatchService;

class StockController
{
    private StockBatchRepository $stockBatchRepo;
    private ProductRepository $productRepo;

    public function __construct() {
        $this->stockBatchRepo = new StockBatchRepository();
        $this->productRepo = new ProductRepository();
    }

    public function receive(): void {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleReceivePost();
        } else {
            $this->showReceiveForm();
        }
    }

    private function showReceiveForm(): void {
        $products = $this->productRepo->findAll();
        $currentUser = $_SESSION['user_firstname'] . ' ' . ($_SESSION['user_lastname'] ?? '');
        $userRole = $_SESSION['user_role'] ?? 'preparator';

        require_once __DIR__ . '/../../templates/dashboard/receive.php';
    }

    private function handleReceivePost(): void {
        $errors = [];

        $productId = (int)($_POST['product_id'] ?? 0);
        $lotNumber = trim($_POST['lot_number'] ?? '');
        $expirationDateString = $_POST['expiration_date'] ?? '';
        $quantity = (int)($_POST['quantity'] ?? 0);
        $purchasePrice = (float)($_POST['purchase_price'] ?? 0);

        if ($productId <= 0) {
            $errors[] = "Please select a valid medication.";
        }

        if (empty($lotNumber)) {
            $errors[] = "Lot number is required.";
        }

        if (empty($expirationDateString)) {
            $errors[] = "Expiration date is required.";
        } else {
            $expirationDate = new DateTime($expirationDateString);
            $today = new DateTime();
            $today->setTime(0, 0, 0);

            if ($expirationDate < $today) {
                $errors[] = "Expiration date cannot be in the past.";
            }
        }

        if ($quantity <= 0) {
            $errors[] = "Quantity must be greater than 0.";
        }

        if ($purchasePrice < 0) {
            $errors[] = "Purchase price cannot be negative.";
        }

        if (empty($errors)) {
            try {
                $product = $this->productRepo->findById($productId);

                if (!$product) {
                    $errors[] = "Product not found.";
                } else {
                    $batch = new StockBatch(
                        $lotNumber,
                        $quantity,
                        $purchasePrice,
                        BatchStatus::OK,
                        $expirationDate,
                        (new DateTime())->format('Y-m-d H:i:s'),
                        $product
                    );

                    $savedBatch = $this->stockBatchRepo->save($batch);

                    if ($savedBatch) {
                        $daysUntilExpiry = StockBatchService::getDaysUntilExpiration($batch);
                        if ($daysUntilExpiry <= 90) {
                            $this->stockBatchRepo->createNotification(
                                $savedBatch->getId(),
                                "Batch {$lotNumber} for {$product->getName()} expires in {$daysUntilExpiry} days."
                            );
                        }

                        $successMessage = "Batch {$lotNumber} successfully added to stock!";
                        $products = $this->productRepo->findAll();
                        $currentUser = $_SESSION['user_firstname'] . ' ' . ($_SESSION['user_lastname'] ?? '');
                        $userRole = $_SESSION['user_role'] ?? 'preparator';
                        require_once __DIR__ . '/../../templates/dashboard/receive.php';
                        return;
                    } else {
                        $errors[] = "Failed to save batch. Please try again.";
                    }
                }
            } catch (\Exception $e) {
                $errors[] = "An error occurred: " . $e->getMessage();
            }
        }

        $products = $this->productRepo->findAll();
        $currentUser = $_SESSION['user_firstname'] . ' ' . ($_SESSION['user_lastname'] ?? '');
        $userRole = $_SESSION['user_role'] ?? 'preparator';
        $errorMessage = "Please fix the errors below.";
        require_once __DIR__ . '/../../templates/dashboard/receive.php';
    }

    public function dispatch(): void {
        echo "Dispatch method - FEFO dispensing coming soon";
    }

    public function alerts(): void {
        $criticalBatches = $this->stockBatchRepo->findCriticalBatches();

        $warningBatches = $this->stockBatchRepo->findWarningBatches();

        $currentUser = $_SESSION['user_firstname'] . ' ' . ($_SESSION['user_lastname'] ?? '');
        $userRole = $_SESSION['user_role'] ?? 'preparator';
        $currentPage = 'alerts';

        require_once __DIR__ . '/../../templates/dashboard/alerts.php';
    }

    public function markAsExpired(): void {
        // Check if user has permission (pharmacist or admin)
        $userRole = $_SESSION['user_role'] ?? 'preparer';
        if (!in_array($userRole, ['pharmacist', 'admin'])) {
            $_SESSION['error'] = "You don't have permission to mark products as expired.";
            header('Location: index.php?route=dashboard');
            exit();
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $batchId = (int)($_POST['batch_id'] ?? 0);
            $batch = $this->stockBatchRepo->findById($batchId);

            if (!$batch) {
                $_SESSION['error'] = "Batch not found.";
                header('Location: index.php?route=stock-alerts');
                exit();
            }
            
            $success = $this->stockBatchRepo->markAsExpired($batch);

            if ($success) {
                $_SESSION['success'] = "✅ Batch {$batch->getLotNumber()} has been marked as expired and removed from active stock.";

                // Create notification about expired batch
                $this->stockBatchRepo->createNotification(
                    $batch->getId(),
                    "Batch {$batch->getLotNumber()} for {$batch->getProduct()->getName()} has been marked as expired and destroyed."
                );
            } else {
                $_SESSION['error'] = "Failed to mark batch as expired. Please try again.";
            }

            // Redirect back to alerts page
            header('Location: index.php?route=stock-alerts');
            exit();
        }

        // If GET request, show confirmation form
        $batchId = (int)($_GET['id'] ?? 0);
        $batch = $this->stockBatchRepo->findById($batchId);

        if (!$batch) {
            $_SESSION['error'] = "Batch not found.";
            header('Location: index.php?route=stock-alerts');
            exit();
        }

        $currentUser = $_SESSION['user_firstname'] . ' ' . ($_SESSION['user_lastname'] ?? '');
        $userRole = $_SESSION['user_role'] ?? 'preparer';
        $currentPage = 'alerts';

        require_once __DIR__ . '/../../templates/dashboard/mark-expired.php';
    }
}