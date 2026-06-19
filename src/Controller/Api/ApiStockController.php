<?php

namespace PharmaFEFOV2\Controller\Api;

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
}