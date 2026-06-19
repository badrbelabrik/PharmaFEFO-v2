<?php
$currentPage = 'dispatch';
$userRole = $_SESSION['user_role'] ?? 'preparer';
$currentUser = $_SESSION['user_firstname'] . ' ' . ($_SESSION['user_lastname'] ?? 'User');
$products = $products ?? [];
require_once __DIR__ . '/../layout/sidebar.php';
?>
<main class="flex-grow p-8 overflow-y-auto max-w-3xl">
    <header class="mb-8">
        <h1 class="text-2xl font-bold text-slate-900">Dispense Medication (FEFO)</h1>
        <p class="text-sm text-slate-500 mt-1">System automatically selects the soonest expiring batch (First Expired, First Out).</p>
    </header>

    <!-- Message Container for async responses -->
    <div id="message-container"></div>

    <!-- Product Selection Form (GET) -->
    <form id="product-select-form" action="index.php?route=stock-dispatch" method="GET" class="bg-white rounded-xl border border-slate-200 shadow-xs p-6">
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
        <!-- Dispense Form (POST) - US 3.1: Async submission -->
        <form id="dispense-form" class="bg-white rounded-xl border border-slate-200 shadow-xs p-6 space-y-6 mt-6" novalidate>

            <!-- FEFO Batch Information -->
            <div id="batch-info" class="bg-emerald-50 rounded-lg p-4 border border-emerald-200">
                <h3 class="text-sm font-semibold text-emerald-800 mb-2 flex items-center">
                    <span class="text-lg mr-2">🎯</span> FEFO Selection Result
                </h3>
                <p class="text-xs text-emerald-700 mb-3">This is the batch that will expire first. Dispense this first according to FEFO rule!</p>

                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <span class="text-slate-600">Product:</span>
                        <p id="dispense-product-name" class="font-semibold text-slate-900"><?= htmlspecialchars($selectedBatch->getProduct()->getName()) ?></p>
                    </div>
                    <div>
                        <span class="text-slate-600">Lot Number:</span>
                        <p id="dispense-lot-number" class="font-mono font-semibold text-slate-900"><?= htmlspecialchars($selectedBatch->getLotNumber()) ?></p>
                    </div>
                    <div>
                        <span class="text-slate-600">Expiration Date:</span>
                        <p id="dispense-expiration" class="font-semibold <?= $daysLeft <= 30 ? 'text-red-600' : ($daysLeft <= 90 ? 'text-amber-600' : 'text-emerald-600') ?>">
                            <?= $selectedBatch->getExpirationDate()->format('F j, Y') ?>
                        </p>
                    </div>
                    <div>
                        <span class="text-slate-600">Days until expiry:</span>
                        <p id="dispense-days-left" class="font-semibold <?= $daysLeft <= 30 ? 'text-red-600' : ($daysLeft <= 90 ? 'text-amber-600' : 'text-emerald-600') ?>">
                            <?= $daysLeft ?> days
                        </p>
                    </div>
                    <div>
                        <span class="text-slate-600">Available Quantity:</span>
                        <p id="dispense-quantity-available" class="font-semibold text-slate-900"><?= $selectedBatch->getQuantity() ?> units</p>
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
                <p id="max-quantity-hint" class="mt-1 text-xs text-slate-400">Maximum available: <?= $selectedBatch->getQuantity() ?> units</p>
            </div>

            <input type="hidden" id="batch-id" name="batch_id" value="<?= $selectedBatch->getId() ?>">
            <input type="hidden" id="product-id" name="product_id" value="<?= $selectedProductId ?>">

            <div class="pt-4 border-t border-slate-100 flex justify-end space-x-3">
                <a href="index.php?route=dashboard" class="px-4 py-2 text-sm font-medium text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-lg transition-colors">
                    Cancel
                </a>
                <button type="submit" id="dispense-submit-btn"
                        class="px-4 py-2 text-sm font-medium text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg shadow-xs transition-colors cursor-pointer">
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

<!-- JavaScript for Async Dispensing -->
<script>
    /**
     * US 3.1: FEFO Dispensing - Async form submission
     */

    document.addEventListener('DOMContentLoaded', () => {
        const dispenseForm = document.getElementById('dispense-form');

        if (dispenseForm) {
            dispenseForm.addEventListener('submit', handleDispenseSubmit);
        }
    });

    /**
     * US 3.1: Handle dispense form submission asynchronously
     */
    async function handleDispenseSubmit(event) {
        // Intercept form submission (Event.preventDefault())
        event.preventDefault();

        const form = event.target;
        const formData = new FormData(form);
        const data = Object.fromEntries(formData.entries());
        const quantity = parseInt(data.quantity);

        // Validate quantity
        const maxQty = parseInt(document.getElementById('dispense-quantity-available').textContent);
        if (quantity <= 0 || quantity > maxQty) {
            showMessage('error', `Please enter a quantity between 1 and ${maxQty}.`);
            return;
        }

        // Show loading state
        const submitBtn = document.getElementById('dispense-submit-btn');
        const originalText = submitBtn.textContent;
        submitBtn.disabled = true;
        submitBtn.textContent = '⏳ Processing...';
        submitBtn.classList.add('opacity-50', 'cursor-not-allowed');

        // Clear previous messages
        clearMessages();

        try {
            // Send data as JSON via fetch()
            const response = await fetch('/index.php?route=api&action=dispense', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    product_id: parseInt(data.product_id),
                    quantity: quantity
                })
            });

            const result = await response.json();

            // Handle different HTTP status codes
            if (response.status === 401) {
                showMessage('error', 'Session expired. Please login again.');
                setTimeout(() => {
                    window.location.href = '/index.php?route=login';
                }, 2000);
                return;
            }

            if (response.status === 403) {
                showMessage('error', 'Access denied. You need Preparer role to dispense.');
                return;
            }

            if (response.status === 404 && result.out_of_stock) {
                showMessage('error', 'No stock available for this product.');
                // Reload page to show updated state
                setTimeout(() => window.location.reload(), 2000);
                return;
            }

            if (!response.ok) {
                throw new Error(result.error || `HTTP ${response.status}`);
            }

            // US 3.1: Display success message and update UI instantly
            if (result.success) {
                showMessage('success', result.message);

                // US 3.1: Update UI without page reload
                updateBatchDisplay(result);

                // If out of stock, reload after animation
                if (result.out_of_stock) {
                    setTimeout(() => {
                        window.location.reload();
                    }, 3000);
                } else {
                    // Update the available quantity display
                    const qtyDisplay = document.getElementById('dispense-quantity-available');
                    if (qtyDisplay) {
                        qtyDisplay.textContent = result.batch.quantity + ' units';
                    }

                    // Update max quantity hint
                    const hint = document.getElementById('max-quantity-hint');
                    if (hint) {
                        hint.textContent = `Maximum available: ${result.batch.quantity} units`;
                    }

                    // Update quantity input max attribute
                    const qtyInput = document.getElementById('quantity');
                    if (qtyInput) {
                        qtyInput.max = result.batch.quantity;
                    }
                }
            } else {
                showMessage('error', result.error || 'Failed to dispense medication');
            }
        } catch (error) {
            console.error('Dispense error:', error);
            showMessage('error', 'Network error. Please check your connection and try again.');
        } finally {
            // Restore button state
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
            submitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
        }
    }

    /**
     * US 3.1: Update batch display without page reload
     */
    function updateBatchDisplay(result) {
        const batch = result.batch;

        // Update lot number if changed (FEFO rule - next batch)
        if (result.remaining_batch) {
            const lotElement = document.getElementById('dispense-lot-number');
            if (lotElement) {
                lotElement.textContent = result.remaining_batch.lot_number;
            }

            // Update expiration date
            const expElement = document.getElementById('dispense-expiration');
            if (expElement) {
                expElement.textContent = result.remaining_batch.expiration_date;
            }

            // Update days left
            const daysElement = document.getElementById('dispense-days-left');
            if (daysElement) {
                daysElement.textContent = result.remaining_batch.days_until_expiration + ' days';
            }

            // Update batch ID hidden field
            const batchIdInput = document.getElementById('batch-id');
            if (batchIdInput) {
                batchIdInput.value = result.remaining_batch.id;
            }
        }

        // Update quantity display
        const qtyDisplay = document.getElementById('dispense-quantity-available');
        if (qtyDisplay) {
            qtyDisplay.textContent = batch.quantity + ' units';
        }

        // If out of stock, show message and gray out
        if (result.out_of_stock) {
            const batchInfo = document.getElementById('batch-info');
            if (batchInfo) {
                batchInfo.classList.remove('bg-emerald-50', 'border-emerald-200');
                batchInfo.classList.add('bg-gray-50', 'border-gray-200', 'opacity-50');
            }

            const qtyInput = document.getElementById('quantity');
            if (qtyInput) {
                qtyInput.disabled = true;
            }

            const submitBtn = document.getElementById('dispense-submit-btn');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.textContent = 'Out of Stock';
                submitBtn.classList.add('bg-gray-400', 'cursor-not-allowed');
                submitBtn.classList.remove('bg-emerald-600', 'hover:bg-emerald-700');
            }

            showMessage('info', '🎯 This batch is now out of stock. The next available batch has been selected automatically (FEFO rule).');
        }
    }

    /**
     * Show message
     */
    function showMessage(type, message) {
        const container = document.getElementById('message-container');
        if (!container) return;

        const colors = {
            success: 'bg-emerald-50 border-emerald-500 text-emerald-700',
            error: 'bg-red-50 border-red-500 text-red-700',
            info: 'bg-blue-50 border-blue-500 text-blue-700'
        };

        container.innerHTML = `
        <div class="mb-4 p-4 border-l-4 rounded-r-lg ${colors[type] || colors.info}">
            <div class="flex items-center">
                <span class="text-xl mr-2">${type === 'success' ? '✅' : type === 'error' ? '❌' : 'ℹ️'}</span>
                <span>${escapeHtml(message)}</span>
            </div>
        </div>
    `;

        container.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    /**
     * Clear messages
     */
    function clearMessages() {
        const container = document.getElementById('message-container');
        if (container) {
            container.innerHTML = '';
        }
    }

    /**
     * Escape HTML
     */
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
</script>
</body>
</html>