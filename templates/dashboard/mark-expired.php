<?php
$currentPage = 'alerts';
$userRole = $_SESSION['user_role'] ?? 'preparer';
require_once __DIR__ . '/../layout/sidebar.php';
?>
<main class="flex-grow p-8 overflow-y-auto max-w-2xl">
    <header class="mb-8">
        <h1 class="text-2xl font-bold text-slate-900">Mark Batch as Expired</h1>
        <p class="text-sm text-slate-500 mt-1">Confirm destruction of expired medication batch</p>
    </header>

    <div class="bg-white rounded-xl border border-slate-200 shadow-xs p-6">
        <!-- Batch Information -->
        <div class="mb-6 p-4 bg-red-50 rounded-lg border border-red-200">
            <div class="flex items-center mb-3">
                <span class="text-2xl mr-2">⚠️</span>
                <h2 class="text-lg font-semibold text-red-700">Batch to be Destroyed</h2>
            </div>
            <div class="grid grid-cols-2 gap-4 text-sm">
                <div>
                    <span class="text-slate-600">Product:</span>
                    <p class="font-semibold text-slate-900"><?= htmlspecialchars($batch->getProduct()->getName()) ?></p>
                </div>
                <div>
                    <span class="text-slate-600">Lot Number:</span>
                    <p class="font-mono font-semibold text-slate-900"><?= htmlspecialchars($batch->getLotNumber()) ?></p>
                </div>
                <div>
                    <span class="text-slate-600">Quantity to Destroy:</span>
                    <p class="font-semibold text-red-600"><?= $batch->getQuantity() ?> units</p>
                </div>
                <div>
                    <span class="text-slate-600">Expiration Date:</span>
                    <p class="font-semibold text-red-600"><?= $batch->getExpirationDate()->format('F j, Y') ?></p>
                </div>
                <div>
                    <span class="text-slate-600">Purchase Price:</span>
                    <p class="font-semibold text-slate-900">€<?= number_format($batch->getPurchasePrice(), 2) ?> per unit</p>
                </div>
                <div>
                    <span class="text-slate-600">Total Value Lost:</span>
                    <p class="font-semibold text-red-600">€<?= number_format($batch->getQuantity() * $batch->getPurchasePrice(), 2) ?></p>
                </div>
            </div>
        </div>

        <!-- Destruction Confirmation Form -->
        <form action="index.php?route=stock-expired" method="POST" class="space-y-6">
            <input type="hidden" name="batch_id" value="<?= $batch->getId() ?>">

            <div>
                <label for="destruction_reason" class="block text-sm font-medium text-slate-700 mb-1">
                    Destruction Reason <span class="text-red-500">*</span>
                </label>
                <select id="destruction_reason" name="destruction_reason" required
                        class="block w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-emerald-500 outline-none">
                    <option value="">Select reason...</option>
                    <option value="expired">Expired (past DLU)</option>
                    <option value="damaged">Damaged packaging</option>
                    <option value="recall">Manufacturer recall</option>
                    <option value="compromised">Cold chain compromised</option>
                </select>
            </div>

            <div>
                <label for="destruction_notes" class="block text-sm font-medium text-slate-700 mb-1">
                    Additional Notes
                </label>
                <textarea id="destruction_notes" name="destruction_notes" rows="3"
                          class="block w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-emerald-500 outline-none"
                          placeholder="Witness name, disposal method, etc."></textarea>
            </div>

            <div class="bg-amber-50 rounded-lg p-4 border border-amber-200">
                <div class="flex items-start">
                    <input type="checkbox" id="confirm_destruction" name="confirm_destruction" required
                           class="mt-1 mr-3 text-red-600 focus:ring-red-500">
                    <label for="confirm_destruction" class="text-sm text-amber-800">
                        I confirm that this batch has been physically destroyed according to pharmaceutical waste regulations (Cyclamed protocol).
                        This action cannot be undone.
                    </label>
                </div>
            </div>

            <div class="pt-4 border-t border-slate-100 flex justify-end space-x-3">
                <a href="index.php?route=stock-alerts"
                   class="px-4 py-2 text-sm font-medium text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-lg transition-colors">
                    Cancel
                </a>
                <button type="submit"
                        class="px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-lg shadow-xs transition-colors cursor-pointer">
                    ✅ Confirm Destruction
                </button>
            </div>
        </form>
    </div>
</main>
</body>
</html>