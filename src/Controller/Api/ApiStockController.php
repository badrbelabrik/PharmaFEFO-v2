<?php

namespace PharmaFEFOV2\Controller\Api;

use DateTime;
use PharmaFEFOV2\Entity\StockBatch;
use PharmaFEFOV2\Enum\BatchStatus;
use PharmaFEFOV2\Repository\ProductRepository;
use PharmaFEFOV2\Repository\StockBatchRepository;
use PharmaFEFOV2\Service\StockBatchService;

class ApiStockController
{
    private StockBatchRepository $stockBatchRepo;
    private ProductRepository $productRepo;

    public function __construct() {
        $this->stockBatchRepo = new StockBatchRepository();
        $this->productRepo = new ProductRepository();
    }

    public function receive(): void
    {
        header('Content-Type: application/json');

        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Unauthorized. Please login.']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);

        if (!$input) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid JSON data']);
            return;
        }

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

        $product = $this->productRepo->findById((int)$input['product_id']);
        if (!$product) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Product not found']);
            return;
        }

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
            // 8. Create notification if expiring soon
            $daysUntilExpiry = $batch->getDaysUntilExpiration();
            if ($daysUntilExpiry <= 90) {
                $this->stockBatchRepo->createNotification(
                    $savedBatch->getId(),
                    "Batch {$input['lot_number']} for {$product->getName()} expires in {$daysUntilExpiry} days."
                );
            }

            // 9. Return success response
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

    public function dispense(): void
    {
        header('Content-Type: application/json');

        // 1. Check authentication
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Unauthorized. Please login.']);
            return;
        }

        // 2. Check role (Preparer+)
        if (!in_array($_SESSION['user_role'], ['preparer', 'pharmacist', 'admin'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Access denied. Preparer role required.']);
            return;
        }

        // 3. Get JSON input
        $input = json_decode(file_get_contents('php://input'), true);

        if (!$input || empty($input['product_id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid request. Product ID required.']);
            return;
        }

        $productId = (int)$input['product_id'];
        $quantity = (int)($input['quantity'] ?? 1);

        // 4. Validate quantity
        if ($quantity <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Quantity must be greater than 0.']);
            return;
        }

        // 5. Find earliest expiring batch (FEFO rule)
        $batch = $this->stockBatchRepo->findEarliestExpiringBatch($productId);

        if (!$batch) {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'error' => 'No stock available for this product',
                'out_of_stock' => true
            ]);
            return;
        }

        // 6. Check if enough quantity
        if ($quantity > $batch->getQuantity()) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => "Insufficient stock. Only {$batch->getQuantity()} units available.",
                'available_quantity' => $batch->getQuantity()
            ]);
            return;
        }

        // 7. Dispense (decrement quantity)
        $success = $this->stockBatchRepo->dispense($batch, $quantity);

        if ($success) {
            // 8. Check if batch is now out of stock
            $isOutOfStock = $batch->getQuantity() <= 0;

            // 9. Get remaining batch if any (FEFO rule for next dispense)
            $remainingBatch = null;
            if (!$isOutOfStock) {
                $remainingBatch = $this->stockBatchRepo->findEarliestExpiringBatch($productId);
            }

            // 10. Check low stock alert
            if ($batch->getQuantity() <= 5 && $batch->getQuantity() > 0) {
                $this->stockBatchRepo->createNotification(
                    $batch->getId(),
                    "Low stock alert: {$batch->getProduct()->getName()} (Lot: {$batch->getLotNumber()}) has only {$batch->getQuantity()} units remaining."
                );
            }

            // 11. Return success response
            echo json_encode([
                'success' => true,
                'message' => "Dispensed {$quantity} unit(s) of {$batch->getProduct()->getName()}",
                'dispensed_quantity' => $quantity,
                'batch' => [
                    'id' => $batch->getId(),
                    'lot_number' => $batch->getLotNumber(),
                    'quantity' => $batch->getQuantity(),
                    'expiration_date' => $batch->getExpirationDate()->format('Y-m-d'),
                    'days_until_expiration' => StockBatchService::getDaysUntilExpiration($batch),
                    'criticality' => StockBatchService::getCriticalityLevel($batch)
                ],
                'out_of_stock' => $isOutOfStock,
                'remaining_batch' => $remainingBatch ? [
                    'id' => $remainingBatch->getId(),
                    'lot_number' => $remainingBatch->getLotNumber(),
                    'quantity' => $remainingBatch->getQuantity(),
                    'expiration_date' => $remainingBatch->getExpirationDate()->format('Y-m-d')
                ] : null
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Failed to dispense medication']);
        }
    }
}