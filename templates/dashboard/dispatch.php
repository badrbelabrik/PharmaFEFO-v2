<?php
$currentPage = 'dispatch';
$userRole = $_SESSION['user_role'] ?? 'preparer';
require_once __DIR__ . '/../layout/sidebar.php';
?>
<main class="flex-grow p-8 overflow-y-auto max-w-3xl">
    <header class="mb-8">
        <h1 class="text-2xl font-bold text-slate-900">Dispense Medication (FEFO)</h1>
        <p class="text-sm text-slate-500 mt-1">System automatically selects the soonest expiring batch (First Expired, First Out).</p>
    </header>

    <!-- Success Message -->
    <?php if (isset($_SESSION['success'])): ?>
        <div class="mb-6 p-4 bg-emerald-50 border-l-4 border-emerald-500 text-emerald-700 rounded-r-lg text-sm">
            <div class="flex items-center">
                <span class="text-lg mr-2">✅</span>
                <span><?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></span>
            </div>
        </div>
    <?php endif; ?>

    <!-- Error Message -->
    <?php if (isset($_SESSION['error'])): ?>
        <div class="mb-6 p-4 bg-red-50 border-l-4 border-red-500 text-red-700 rounded-r-lg text-sm">
            <div class="flex items-center">
                <span class="text-lg mr-2">❌</span>
                <span><?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></span>
            </div>
        </div>
    <?php endif; ?>

    <!-- Form for product selection (GET) -->
    <form action="index.php?route=stock-dispatch" method="GET" class="bg-white rounded-xl border border-slate-200 shadow-xs p-6">
        <input type="hidden" name="route" value="stock-dispatch">

        <div>
            <label for="product_id" class="block text-sm font-medium text-slate-700 mb-1">
                Select Medication <span class="text-red-500">*</span>
            </label>
            <select id="product_id" name="product_id" required
                    onchange="this.form.submit()"
                    class="block w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-emerald-500 outline-none">
                <option value="">-- Choose Medication --</option>
                <?php foreach ($products as $prod): ?>
                    <option value="<?= $prod->getId() ?>" <?= (isset($selectedProductId) && $selectedProductId == $prod->getId()) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($prod->getName()) ?> (SN: <?= htmlspecialchars($prod->getSerialNumber()) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </form>

    <?php if (isset($selectedBatch) && $selectedBatch): ?>
        <!-- Dispense Form (POST) -->
        <form action="index.php?route=stock-dispatch" method="POST" class="bg-white rounded-xl border border-slate-200 shadow-xs p-6 space-y-6 mt-6">

            <!-- FEFO Batch Information -->
            <div class="bg-emerald-50 rounded-lg p-4 border border-emerald-200">
                <h3 class="text-sm font-semibold text-emerald-800 mb-2 flex items-center">
                    <span class="text-lg mr-2">🎯</span> FEFO Selection Result
                </h3>
                <p class="text-xs text-emerald-700 mb-3">This is the batch that will expire first. Dispense this first according to FEFO rule!</p>

                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <span class="text-slate-600">Product:</span>
                        <p class="font-semibold text-slate-900"><?= htmlspecialchars($selectedBatch->getProduct()->getName()) ?></p>
                    </div>
                    <div>
                        <span class="text-slate-600">Lot Number:</span>
                        <p class="font-mono font-semibold text-slate-900"><?= htmlspecialchars($selectedBatch->getLotNumber()) ?></p>
                    </div>
                    <div>
                        <span class="text-slate-600">Expiration Date:</span>
                        <p class="font-semibold <?= $daysLeft <= 30 ? 'text-red-600' : ($daysLeft <= 90 ? 'text-amber-600' : 'text-emerald-600') ?>">
                            <?= $selectedBatch->getExpirationDate()->format('F j, Y') ?>
                        </p>
                    </div>
                    <div>
                        <span class="text-slate-600">Days until expiry:</span>
                        <p class="font-semibold <?= $daysLeft <= 30 ? 'text-red-600' : ($daysLeft <= 90 ? 'text-amber-600' : 'text-emerald-600') ?>">
                            <?= $daysLeft ?> days
                        </p>
                    </div>
                    <div>
                        <span class="text-slate-600">Available Quantity:</span>
                        <p class="font-semibold text-slate-900"><?= $selectedBatch->getQuantity() ?> units</p>
                    </div>
                    <div>
                        <span class="text-slate-600">Unit Price:</span>
                        <p class="font-semibold text-slate-900">€<?= number_format($selectedBatch->getPurchasePrice(), 2) ?></p>
                    </div>
                </div>
            </div>

            <!-- Quantity to Dispense -->
            <div>
                <label for="quantity" class="block text-sm font-medium text-slate-700 mb-1">
                    Quantity to Dispense <span class="text-red-500">*</span>
                </label>
                <input type="number" id="quantity" name="quantity" min="1" max="<?= $selectedBatch->getQuantity() ?>" required
                       value="1"
                       class="block w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-emerald-500 outline-none">
                <p class="mt-1 text-xs text-slate-400">Maximum available: <?= $selectedBatch->getQuantity() ?> units</p>
            </div>

            <input type="hidden" name="batch_id" value="<?= $selectedBatch->getId() ?>">
            <input type="hidden" name="product_id" value="<?= $selectedProductId ?>">

            <div class="pt-4 border-t border-slate-100 flex justify-end space-x-3">
                <a href="index.php?route=dashboard" class="px-4 py-2 text-sm font-medium text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-lg transition-colors">
                    Cancel
                </a>
                <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg shadow-xs transition-colors cursor-pointer">
                    ✅ Confirm Dispensation
                </button>
            </div>
        </form>
    <?php elseif (isset($selectedProductId) && $selectedProductId > 0): ?>
        <div class="bg-amber-50 rounded-lg p-4 border border-amber-200 text-amber-800 mt-6">
            <div class="flex items-center">
                <span class="text-xl mr-3">⚠️</span>
                <div>
                    <p class="font-semibold">No stock available</p>
                    <p class="text-sm">This medication is out of stock. Please receive new stock first.</p>
                </div>
            </div>
        </div>
        <div class="mt-4 flex justify-end">
            <a href="index.php?route=stock-receive" class="px-4 py-2 text-sm font-medium text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg transition-colors">
                + Receive New Stock
            </a>
        </div>
    <?php endif; ?>
</main>
</body>
</html>