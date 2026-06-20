<?php
$currentPage = 'receive';
$userRole = $_SESSION['user_role'] ?? 'preparer';
$currentUser = $_SESSION['user_firstname'] . ' ' . ($_SESSION['user_lastname'] ?? 'User');
require_once __DIR__ . '/../layout/sidebar.php';
?>
<main class="flex-grow p-8 max-w-3xl">
    <header class="mb-8">
        <h1 class="text-2xl font-bold text-slate-900">Intelligent Stock Ingestion</h1>
        <p class="text-sm text-slate-500 mt-1">Log incoming shipments. Batches are automatically organized into FEFO evaluation pipelines.</p>
    </header>

    <div id="message-container"></div>

    <form id="receive-form" class="bg-white rounded-xl border border-slate-200 shadow-xs p-6 space-y-6" novalidate>

        <div>
            <label for="product_id" class="block text-sm font-medium text-slate-700 mb-1">
                Select Medication Reference <span class="text-red-500">*</span>
            </label>
            <select id="product_id" name="product_id" required
                    class="block w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-emerald-500 outline-none focus:border-emerald-500">
                <option value="">-- Loading medications... --</option>
            </select>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label for="lot_number" class="block text-sm font-medium text-slate-700 mb-1">
                    Lot Number / Batch ID <span class="text-red-500">*</span>
                </label>
                <input type="text" id="lot_number" name="lot_number" required
                       placeholder="e.g., LOT-2026-X99"
                       class="block w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-emerald-500 outline-none focus:border-emerald-500 font-mono">
            </div>

            <div>
                <label for="expiration_date" class="block text-sm font-medium text-slate-700 mb-1">
                    Expiration Date (DLU) <span class="text-red-500">*</span>
                </label>
                <input type="date" id="expiration_date" name="expiration_date" required
                       min="<?= date('Y-m-d', strtotime('+1 day')) ?>"
                       class="block w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-emerald-500 outline-none focus:border-emerald-500">
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label for="quantity" class="block text-sm font-medium text-slate-700 mb-1">
                    Quantity Received (units) <span class="text-red-500">*</span>
                </label>
                <input type="number" id="quantity" name="quantity" min="1" required
                       placeholder="100"
                       class="block w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-emerald-500 outline-none focus:border-emerald-500">
            </div>

            <div>
                <label for="purchase_price" class="block text-sm font-medium text-slate-700 mb-1">
                    Unit Purchase Price (€) <span class="text-red-500">*</span>
                </label>
                <input type="number" id="purchase_price" name="purchase_price" step="0.01" min="0.00" required
                       placeholder="4.50"
                       class="block w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-emerald-500 outline-none focus:border-emerald-500">
            </div>
        </div>

        <div class="bg-slate-50 rounded-lg p-4 border border-slate-200">
            <h3 class="text-sm font-semibold text-slate-700 mb-2 flex items-center">
                <span class="text-lg mr-2">📋</span> Batch Summary Preview
            </h3>
            <div class="text-xs text-slate-500 space-y-1">
                <p>• This batch will be prioritized in FEFO dispensing queue based on expiration date</p>
                <p>• Status will be automatically calculated based on days until expiration</p>
                <div class="flex gap-4 mt-2 ml-4">
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs bg-emerald-100 text-emerald-800">&gt; 90 days: Active</span>
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs bg-amber-100 text-amber-800">30-90 days: Warning</span>
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs bg-red-100 text-red-800">&lt; 30 days: Critical</span>
                </div>
            </div>
        </div>

        <div class="pt-4 border-t border-slate-100 flex justify-end space-x-3">
            <a href="index.php?route=dashboard" class="px-4 py-2 text-sm font-medium text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-lg transition-colors">
                Cancel
            </a>
            <button type="submit" id="submit-btn"
                    class="px-4 py-2 text-sm font-medium text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg shadow-xs transition-colors cursor-pointer">
                ✅ Confirm Ingestion
            </button>
        </div>
    </form>
</main>
<script src="../../PharmaFEFO-v2/public/js/receive.js"></script>