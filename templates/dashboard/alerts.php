<?php

use PharmaFEFOV2\Service\StockBatchService;

$currentPage = 'alerts';
$userRole = $_SESSION['user_role'] ?? 'preparer';
require_once __DIR__ . '/../layout/sidebar.php';
?>
<main class="flex-grow p-8 overflow-y-auto">
    <header class="mb-8">
        <h1 class="text-2xl font-bold text-slate-900">Expiry Alerts</h1>
        <p class="text-sm text-slate-500">Monitor batches approaching expiration</p>
    </header>

    <!-- Success/Error Messages -->
    <?php if (isset($_SESSION['success'])): ?>
        <div class="mb-6 p-4 bg-emerald-50 border-l-4 border-emerald-500 text-emerald-700 rounded-r-lg">
            ✅ <?= $_SESSION['success']; unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="mb-6 p-4 bg-red-50 border-l-4 border-red-500 text-red-700 rounded-r-lg">
            ❌ <?= $_SESSION['error']; unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>

    <!-- Critical Alerts (Red) -->
    <div class="mb-8">
        <h2 class="text-lg font-semibold text-red-700 mb-4 flex items-center">
            <span class="w-3 h-3 bg-red-500 rounded-full mr-2"></span>
            Critical (< 30 days)
        </h2>
        <div class="bg-white rounded-xl border border-red-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-red-100">
                    <thead class="bg-red-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-red-700 uppercase">Product</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-red-700 uppercase">Lot Number</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-red-700 uppercase">Quantity</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-red-700 uppercase">Expires In</th>
                        <?php if (in_array($userRole, ['pharmacist', 'admin'])): ?>
                            <th class="px-6 py-3 text-right text-xs font-medium text-red-700 uppercase">Actions</th>
                        <?php endif; ?>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-red-100">
                    <?php if (empty($criticalBatches)): ?>
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-center text-slate-500">
                                ✅ No critical batches. Good job!
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($criticalBatches as $batch): ?>
                            <tr class="hover:bg-red-50">
                                <td class="px-6 py-4 font-medium"><?= htmlspecialchars($batch->getProduct()->getName()) ?></td>
                                <td class="px-6 py-4 font-mono text-sm"><?= htmlspecialchars($batch->getLotNumber()) ?></td>
                                <td class="px-6 py-4"><?= $batch->getQuantity() ?> units</td>
                                <td class="px-6 py-4 font-bold text-red-600"><?= StockBatchService::getDaysUntilExpiration($batch) ?> days</td>
                                <?php if (in_array($userRole, ['pharmacist', 'admin'])): ?>
                                    <td class="px-6 py-4 text-right space-x-2">
                                        <a href="index.php?route=stock-expired&id=<?= $batch->getId() ?>"
                                           class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-white bg-red-600 hover:bg-red-700 rounded-lg transition-colors">
                                            🗑️ Mark Expired
                                        </a>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Warning Alerts (Orange) - Similar structure -->
    <div class="mb-8">
        <h2 class="text-lg font-semibold text-amber-700 mb-4 flex items-center">
            <span class="w-3 h-3 bg-amber-500 rounded-full mr-2"></span>
            Warning (30-90 days)
        </h2>
        <div class="bg-white rounded-xl border border-amber-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-amber-100">
                    <thead class="bg-amber-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-amber-700 uppercase">Product</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-amber-700 uppercase">Lot Number</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-amber-700 uppercase">Quantity</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-amber-700 uppercase">Expires In</th>
                        <?php if (in_array($userRole, ['pharmacist', 'admin'])): ?>
                            <th class="px-6 py-3 text-right text-xs font-medium text-amber-700 uppercase">Actions</th>
                        <?php endif; ?>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($warningBatches)): ?>
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-center text-slate-500">
                                ✅ No warning batches.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($warningBatches as $batch): ?>
                            <tr class="hover:bg-amber-50">
                                <td class="px-6 py-4 font-medium"><?= htmlspecialchars($batch->getProduct()->getName()) ?></td>
                                <td class="px-6 py-4 font-mono text-sm"><?= htmlspecialchars($batch->getLotNumber()) ?></td>
                                <td class="px-6 py-4"><?= $batch->getQuantity() ?> units</td>
                                <td class="px-6 py-4 font-medium text-amber-600"><?= StockBatchService::getDaysUntilExpiration($batch) ?> days</td>
                                <?php if (in_array($userRole, ['pharmacist', 'admin'])): ?>
                                    <td class="px-6 py-4 text-right">
                                        <button class="text-amber-600 hover:text-amber-800 text-sm">Return to Supplier</button>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>
</body>
</html>