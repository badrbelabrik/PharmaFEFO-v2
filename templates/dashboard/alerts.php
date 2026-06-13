<?php
$currentPage = 'alerts';
$userRole = $_SESSION['user_role'] ?? 'preparer';
require_once __DIR__ . '/../layout/sidebar.php';
?>
<?php if (isset($_SESSION['success'])): ?>
    <div class="mb-6 p-4 bg-emerald-50 text-emerald-700 rounded-lg">
        ✅ <?= $_SESSION['success']; unset($_SESSION['success']); ?>
    </div>
<?php endif; ?>
<main class="flex-grow p-8 overflow-y-auto">
    <header class="mb-8">
        <h1 class="text-2xl font-bold text-slate-900">Expiry Alerts</h1>
        <p class="text-sm text-slate-500">Monitor batches approaching expiration</p>
    </header>

    <!-- Critical Alerts (Red) -->
    <div class="mb-8">
        <h2 class="text-lg font-semibold text-red-700 mb-4 flex items-center">
            <span class="w-3 h-3 bg-red-500 rounded-full mr-2"></span>
            Critical (< 30 days)
        </h2>
        <div class="bg-white rounded-xl border border-red-200 overflow-hidden">
            <table class="min-w-full divide-y divide-red-100">
                <thead class="bg-red-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-red-700">Product</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-red-700">Lot Number</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-red-700">Quantity</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-red-700">Expires In</th>
                    <?php if ($userRole === 'pharmacist' || $userRole === 'admin'): ?>
                        <th class="px-6 py-3 text-right text-xs font-medium text-red-700">Actions</th>
                    <?php endif; ?>
                </tr>
                </thead>
                <tbody class="divide-y divide-red-100">
                <?php foreach ($criticalBatches as $batch): ?>
                    <tr class="hover:bg-red-50">
                        <td class="px-6 py-4"><?= htmlspecialchars($batch->getProduct()->getName()) ?></td>
                        <td class="px-6 py-4 font-mono"><?= htmlspecialchars($batch->getLotNumber()) ?></td>
                        <td class="px-6 py-4"><?= $batch->getQuantity() ?> units</td>
                        <td class="px-6 py-4 font-bold text-red-600"><?= StockBatchService::getDaysUntilExpiration($batch) ?> days</td>
                        <?php if ($userRole === 'pharmacist' || $userRole === 'admin'): ?>
                            <td class="px-6 py-4 text-right">
                                <button class="text-amber-600 hover:text-amber-800 mr-3">Return</button>
                                <button class="text-red-600 hover:text-red-800">Destroy</button>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Warning Alerts (Orange) -->
    <div class="mb-8">
        <h2 class="text-lg font-semibold text-amber-700 mb-4 flex items-center">
            <span class="w-3 h-3 bg-amber-500 rounded-full mr-2"></span>
            Warning (30-90 days)
        </h2>
        <div class="bg-white rounded-xl border border-amber-200 overflow-hidden">
            <!-- Similar table structure -->
        </div>
    </div>
</main>
</body>
</html>