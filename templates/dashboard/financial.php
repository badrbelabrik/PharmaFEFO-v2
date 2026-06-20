<?php
$currentPage = 'reports';
$userRole = $_SESSION['user_role'] ?? 'admin';
$currentUser = $_SESSION['user_firstname'] . ' ' . ($_SESSION['user_lastname'] ?? 'User');
require_once __DIR__ . '/../layout/sidebar.php';
?>

<main class="flex-grow p-8 overflow-y-auto">
    <header class="mb-8">
        <h1 class="text-2xl font-bold text-slate-900">Financial Loss Reports</h1>
        <p class="text-sm text-slate-500 mt-1">Monitor financial impact of expired medications</p>
    </header>

    <!-- Message Container -->
    <div id="message-container"></div>

    <!-- Summary Cards -->
    <section class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
            <p class="text-sm font-medium text-slate-500">Total Loss Value</p>
            <p id="total-loss" class="text-3xl font-bold text-red-600 mt-2">-</p>
        </div>
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
            <p class="text-sm font-medium text-slate-500">Expired Batches</p>
            <p id="expired-count" class="text-3xl font-bold text-slate-900 mt-2">-</p>
        </div>
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
            <p class="text-sm font-medium text-slate-500">Date Range</p>
            <p class="text-lg font-bold text-slate-900 mt-2">
                <span id="start-date-display">-</span> - <span id="end-date-display">-</span>
            </p>
        </div>
    </section>

    <!-- Monthly Loss Chart -->
    <section class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 mb-8">
        <h2 class="text-lg font-bold text-slate-800 mb-4">📊 Monthly Loss Trends</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase">Month</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-slate-500 uppercase">Loss Value</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-slate-500 uppercase">Status</th>
                </tr>
                </thead>
                <tbody id="monthly-losses-body" class="divide-y divide-slate-200">
                <tr>
                    <td colspan="3" class="px-4 py-8 text-center text-slate-500">
                        <div class="flex justify-center items-center space-x-3">
                            <div class="w-5 h-5 border-2 border-emerald-500 border-t-transparent rounded-full animate-spin"></div>
                            <span>Loading monthly data...</span>
                        </div>
                    </td>
                </tr>
                </tbody>
            </table>
        </div>
    </section>

    <!-- Loss by Product -->
    <section class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
        <h2 class="text-lg font-bold text-slate-800 mb-4">💊 Loss by Product</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase">Product</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-slate-500 uppercase">Quantity Lost</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-slate-500 uppercase">Total Loss</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-slate-500 uppercase">% of Total</th>
                </tr>
                </thead>
                <tbody id="loss-by-product-body" class="divide-y divide-slate-200">
                <tr>
                    <td colspan="4" class="px-4 py-8 text-center text-slate-500">
                        <div class="flex justify-center items-center space-x-3">
                            <div class="w-5 h-5 border-2 border-emerald-500 border-t-transparent rounded-full animate-spin"></div>
                            <span>Loading product data...</span>
                        </div>
                    </td>
                </tr>
                </tbody>
                <tfoot id="loss-by-product-footer" class="bg-slate-50">
                <tr>
                    <td class="px-4 py-3 text-sm font-bold text-slate-700">TOTAL</td>
                    <td class="px-4 py-3 text-sm text-right font-bold text-slate-700">-</td>
                    <td class="px-4 py-3 text-sm text-right font-bold text-red-700">-</td>
                    <td class="px-4 py-3 text-sm text-right font-bold text-slate-700">-</td>
                </tr>
                </tfoot>
            </table>
        </div>
    </section>

    <!-- Back to Dashboard -->
    <div class="mt-8">
        <a href="index.php?route=dashboard" class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg transition-colors">
            ← Back to Dashboard
        </a>
    </div>
</main>

<!-- Loading Overlay -->
<div id="loading-overlay" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 flex items-center space-x-4 shadow-xl">
        <div class="w-8 h-8 border-4 border-emerald-500 border-t-transparent rounded-full animate-spin"></div>
        <span class="text-slate-700 font-medium">Loading...</span>
    </div>
</div>

<script src="../../PharmaFEFO-v2/public/js/reports.js"></script>
