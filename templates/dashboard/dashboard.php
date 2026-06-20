<?php
$currentPage = 'dashboard';
$userRole = $_SESSION['user_role'] ?? 'preparer';
$currentUser = $_SESSION['user_firstname'] . ' ' . ($_SESSION['user_lastname'] ?? 'User');

error_log("📊 Dashboard - User role from session: " . $userRole);
require_once __DIR__ . '/../layout/sidebar.php';
?>
<script>
    window.userRole = '<?= htmlspecialchars($userRole) ?>';
    console.log('👤 User role set from PHP:', window.userRole);
</script>
<main class="flex-grow p-8 overflow-y-auto">
    <header class="flex justify-between items-center pb-6 border-b border-slate-200 mb-8">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Dispensary Overview</h1>
            <p class="text-sm text-slate-500">Monitor batch queues and anticipate upcoming losses.</p>
        </div>
        <div class="relative p-2 bg-white rounded-lg border border-slate-200 cursor-pointer hover:bg-slate-50 transition-colors">
            <span class="text-xl">🔔</span>
            <span id="notification-badge" class="absolute top-1 right-1 hidden w-3 h-3 bg-red-500 rounded-full animate-pulse"></span>
        </div>
    </header>

    <!-- US 2.2: Expiring Next Month Banner -->
    <div id="expiring-banner" class="hidden mb-6 p-4 bg-amber-50 border-l-4 border-amber-500 text-amber-700 rounded-r-lg">
        <div class="flex items-center">
            <span class="text-xl mr-3">⚠️</span>
            <span id="expiring-message"></span>
        </div>
    </div>

    <!-- Stats Cards - Populated by JavaScript -->
    <section class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-xs">
            <p class="text-sm font-medium text-slate-500">Total Tracked Batches</p>
            <p id="total-batches" class="text-3xl font-bold text-slate-900 mt-2">-</p>
        </div>
        <div class="bg-white p-5 rounded-xl border border-slate-200 border-l-4 border-emerald-500 shadow-xs">
            <p class="text-sm font-medium text-slate-500">Healthy (> 90 days)</p>
            <p id="healthy-count" class="text-3xl font-bold text-emerald-600 mt-2">-</p>
        </div>
        <div class="bg-white p-5 rounded-xl border border-slate-200 border-l-4 border-amber-500 shadow-xs">
            <p class="text-sm font-medium text-slate-500">Warning (< 90 days)</p>
            <p id="warning-count" class="text-3xl font-bold text-amber-600 mt-2">-</p>
        </div>
        <div class="bg-white p-5 rounded-xl border border-slate-200 border-l-4 border-red-500 shadow-xs">
            <p class="text-sm font-medium text-slate-500">Critical (< 30 days)</p>
            <p id="critical-count" class="text-3xl font-bold text-red-600 mt-2">-</p>
        </div>
    </section>

    <!-- Filter Section - US 2.1 -->
    <section class="bg-white p-4 rounded-xl border border-slate-200 mb-6 flex flex-col sm:flex-row justify-between items-center gap-4">
        <div class="flex flex-wrap gap-2">
            <button data-filter="all" class="filter-btn px-4 py-2 text-sm font-medium bg-emerald-600 text-white rounded-lg transition-colors">
                📊 All Batches
            </button>
            <button data-filter="critical" class="filter-btn px-4 py-2 text-sm font-medium bg-white text-slate-700 border border-slate-300 rounded-lg hover:bg-slate-50 transition-colors">
                🔴 Critical (< 30 days)
            </button>
            <button data-filter="warning" class="filter-btn px-4 py-2 text-sm font-medium bg-white text-slate-700 border border-slate-300 rounded-lg hover:bg-slate-50 transition-colors">
                🟠 Warning (< 90 days)
            </button>
            <button data-filter="healthy" class="filter-btn px-4 py-2 text-sm font-medium bg-white text-slate-700 border border-slate-300 rounded-lg hover:bg-slate-50 transition-colors">
                🟢 Healthy (> 90 days)
            </button>
        </div>
        <a href="index.php?route=stock-receive" class="inline-flex justify-center items-center px-4 py-2 text-sm font-medium text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg shadow-xs transition-colors">
            + Ingest New Batch
        </a>
    </section>

    <!-- Batches Table - Populated by JavaScript -->
    <section class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wider text-slate-500">
                <tr>
                    <th class="px-6 py-4">Medication Name</th>
                    <th class="px-6 py-4">Lot Number</th>
                    <th class="px-6 py-4">Available Qty</th>
                    <th class="px-6 py-4">Expiration Date (DLU)</th>
                    <th class="px-6 py-4">Risk Evaluation</th>
                    <th class="px-6 py-4 text-right">Actions</th>
                </tr>
                </thead>
                <tbody id="batch-table-body" class="divide-y divide-slate-200 bg-white">
                <tr>
                    <td colspan="6" class="px-6 py-8 text-center text-slate-500">
                        <div class="flex justify-center items-center space-x-3">
                            <div class="w-5 h-5 border-2 border-emerald-500 border-t-transparent rounded-full animate-spin"></div>
                            <span>Loading batches...</span>
                        </div>
                    </td>
                </tr>
                </tbody>
            </table>
        </div>
    </section>
</main>

<!-- Loading Overlay -->
<div id="loading-overlay" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 flex items-center space-x-4 shadow-xl">
        <div class="w-8 h-8 border-4 border-emerald-500 border-t-transparent rounded-full animate-spin"></div>
        <span class="text-slate-700 font-medium">Loading...</span>
    </div>
</div>

<!-- Toast Container -->
<div id="toast-container" class="fixed bottom-4 right-4 space-y-2 z-50"></div>

<script src="../../PharmaFEFO-v2/public/js/dashboard.js"></script>
