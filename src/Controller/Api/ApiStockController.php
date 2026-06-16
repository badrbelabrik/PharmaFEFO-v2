<?php

declare(strict_types=1);

namespace PharmaFEFO\Controller\Api;

use DateTime;
use PharmaFEFOV2\Entity\StockBatch;
use PharmaFEFOV2\Enum\BatchStatus;
use PharmaFEFOV2\Repository\ProductRepository;
use PharmaFEFOV2\Repository\StockBatchRepository;


class ApiStockController
{
    private StockBatchRepository $stockBatchRepo;
    private ProductRepository $productRepo;

    public function __construct() {
        $this->stockBatchRepo = new StockBatchRepository();
        $this->productRepo = new ProductRepository();
    }

    /**
     * POST /api/v1/stock/receive
     * US 1.1: Receive new batch asynchronously
     */
    public function receive(): void
    {
        header('Content-Type: application/json');

        // Check authentication
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Unauthorized. Please login.']);
            return;
        }

        // Check role (Preparer only)
        if ($_SESSION['user_role'] !== 'preparer') {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Access denied. Preparer role required.']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);

        if (!$input) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid JSON data']);
            return;
        }

        // Validate required fields
        $required = ['product_id', 'lot_number', 'expiration_date', 'quantity', 'purchase_price'];
        $errors = [];

        foreach ($required as $field) {
            if (empty($input[$field])) {
                $errors[] = "Missing field: {$field}";
            }
        }

        if (!empty($errors)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'errors' => $errors]);
            return;
        }

        // Validate expiration date
        try {
            $expirationDate = new DateTime($input['expiration_date']);
            $today = new DateTime();
            $today->setTime(0, 0, 0);

            if ($expirationDate <= $today) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Expiration date must be in the future']);
                return;
            }
        } catch (\Exception $e) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid expiration date format']);
            return;
        }

        // Get product
        $product = $this->productRepo->findById((int)$input['product_id']);
        if (!$product) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Product not found']);
            return;
        }

        // Create and save batch
        $batch = new StockBatch(
            $input['lot_number'],
            (int)$input['quantity'],
            (float)$input['purchase_price'],
            BatchStatus::OK,
            $expirationDate,
            (new DateTime())->format('Y-m-d H:i:s'),
            $product
        );

        $savedBatch = $this->stockBatchRepo->save($batch);

        if ($savedBatch) {
            // Create notification if expiring soon
            $daysUntilExpiry = $batch->getDaysUntilExpiration();
            if ($daysUntilExpiry <= 90) {
                $this->stockBatchRepo->createNotification(
                    $savedBatch->getId(),
                    "Batch {$input['lot_number']} for {$product->getName()} expires in {$daysUntilExpiry} days."
                );
            }

            echo json_encode([
                'success' => true,
                'message' => "Batch {$input['lot_number']} received successfully!",
                'batch' => $savedBatch->jsonSerialize()
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Failed to save batch. Please try again.']);
        }
    }

    /**
     * POST /api/v1/stock/dispense
     * US 3.1: Dispense medication asynchronously (FEFO)
     */
    public function dispense(): void
    {
        header('Content-Type: application/json');

        // Check authentication
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Unauthorized. Please login.']);
            return;
        }

        // Check role (Preparer+)
        if (!in_array($_SESSION['user_role'], ['preparer', 'pharmacist', 'admin'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Access denied. Preparer role required.']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);

        if (!$input || empty($input['product_id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid request. Product ID required.']);
            return;
        }

        $productId = (int)$input['product_id'];
        $quantity = (int)($input['quantity'] ?? 1);

        // Find earliest expiring batch (FEFO)
        $batch = $this->stockBatchRepo->findEarliestExpiringBatch($productId);

        if (!$batch) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'No stock available for this product']);
            return;
        }

        if ($quantity > $batch->getQuantity()) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => "Insufficient stock. Only {$batch->getQuantity()} units available.",
                'available_quantity' => $batch->getQuantity()
            ]);
            return;
        }

        // Dispense
        $success = $this->stockBatchRepo->dispense($batch, $quantity);

        if ($success) {
            $isOutOfStock = $batch->getQuantity() <= 0;
            $remainingBatch = null;

            if (!$isOutOfStock) {
                $remainingBatch = $this->stockBatchRepo->findEarliestExpiringBatch($productId);
            }

            // Check low stock
            if ($batch->getQuantity() <= 5 && $batch->getQuantity() > 0) {
                $this->stockBatchRepo->createNotification(
                    $batch->getId(),
                    "Low stock alert: {$batch->getProduct()->getName()} (Lot: {$batch->getLotNumber()}) has only {$batch->getQuantity()} units remaining."
                );
            }

            echo json_encode([
                'success' => true,
                'message' => "Dispensed {$quantity} unit(s) successfully",
                'dispensed_quantity' => $quantity,
                'batch' => $batch->jsonSerialize(),
                'out_of_stock' => $isOutOfStock,
                'remaining_batch' => $remainingBatch ? $remainingBatch->jsonSerialize() : null
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Failed to dispense medication']);
        }
    }

    /**
     * POST /api/v1/stock/expired
     * US 4.1: Mark batch as expired
     */
    public function markExpired(): void
    {
        header('Content-Type: application/json');

        // Check authentication
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Unauthorized. Please login.']);
            return;
        }

        // Check role (Pharmacist+)
        if (!in_array($_SESSION['user_role'], ['pharmacist', 'admin'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Access denied. Pharmacist role required.']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);

        if (!$input || empty($input['batch_id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Batch ID required']);
            return;
        }

        $batch = $this->stockBatchRepo->findById((int)$input['batch_id']);

        if (!$batch) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Batch not found']);
            return;
        }

        $success = $this->stockBatchRepo->markAsExpired($batch);

        if ($success) {
            // Create notification
            $this->stockBatchRepo->createNotification(
                $batch->getId(),
                "Batch {$batch->getLotNumber()} for {$batch->getProduct()->getName()} has been marked as expired and destroyed."
            );

            echo json_encode([
                'success' => true,
                'message' => "Batch {$batch->getLotNumber()} marked as expired",
                'batch' => $batch->jsonSerialize()
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Failed to mark batch as expired']);
        }
    }

    /**
     * POST /api/v1/stock/return
     * Supplier return request
     */
    public function returnBatch(): void
    {
        header('Content-Type: application/json');

        // Check authentication
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Unauthorized. Please login.']);
            return;
        }

        // Check role (Pharmacist+)
        if (!in_array($_SESSION['user_role'], ['pharmacist', 'admin'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Access denied. Pharmacist role required.']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);

        if (!$input || empty($input['batch_id']) || empty($input['reason'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Batch ID and reason required']);
            return;
        }

        $batch = $this->stockBatchRepo->findById((int)$input['batch_id']);

        if (!$batch) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Batch not found']);
            return;
        }

        $success = $this->stockBatchRepo->requestReturn(
            $batch,
            $input['reason'],
            $input['notes'] ?? ''
        );

        if ($success) {
            echo json_encode([
                'success' => true,
                'message' => "Return requested for batch {$batch->getLotNumber()}",
                'batch' => $batch->jsonSerialize()
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Failed to request return']);
        }
    }
}