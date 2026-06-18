/**
 * PharmaFEFO - Dashboard JavaScript
 * Calls API endpoints to fill the view
 */
console.log("this is the right script")
const API_BASE = 'http://localhost/PharmaFEFO-v2/public/index.php?route=api';

// State
let currentFilter = 'all';

// ========== US 2.2: Fetch and Display Stats ==========
async function fetchStats() {
    try {
        const response = await fetch(`${API_BASE}&action=stats`);

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }

        const result = await response.json();

        if (result.success) {
            // Fill stats cards
            document.getElementById('total-batches').textContent = result.stats.total_batches;
            document.getElementById('critical-count').textContent = result.stats.critical_count;
            document.getElementById('warning-count').textContent = result.stats.warning_count;
            document.getElementById('healthy-count').textContent = result.stats.healthy_count;

            // US 2.2: Show expiring next month banner
            updateExpiringBanner(result.stats.expiring_next_month);
        }
    } catch (error) {
        console.error('Stats fetch error:', error);
        showToast('error', 'Failed to load dashboard statistics');
    }
}

// ========== US 2.1: Fetch and Display Batches ==========
async function fetchBatches() {
    try {
        const response = await fetch(`${API_BASE}&action=batches&filter=${currentFilter}`);

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }

        const result = await response.json();

        if (result.success) {
            renderBatchTable(result.data);
            updateActiveFilter();
        }
    } catch (error) {
        console.error('Batches fetch error:', error);
        showToast('error', 'Failed to load batches');
    }
}

// ========== Render Batch Table ==========
function renderBatchTable(batches) {
    const tbody = document.getElementById('batch-table-body');

    if (!tbody) return;

    if (batches.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="6" class="px-6 py-8 text-center text-slate-500">
                    No batches found matching the selected filter.
                </td>
            </tr>
        `;
        return;
    }

    const userRole = document.body.dataset.userRole || 'preparer';

    tbody.innerHTML = batches.map(batch => {
        const daysLeft = batch.days_until_expiration || 0;
        const rowClass = getRowClass(daysLeft);
        const dateClass = getDateClass(daysLeft);
        const badgeClass = getBadgeClass(daysLeft);
        const statusLabel = getStatusLabel(daysLeft);

        return `
            <tr class="${rowClass} transition-colors">
                <td class="px-6 py-4 font-semibold text-slate-900">
                    ${escapeHtml(batch.product.name)}
                </td>
                <td class="px-6 py-4">
                    <span class="font-mono bg-slate-100 text-slate-700 px-2 py-0.5 rounded border border-slate-200 text-xs">
                        ${escapeHtml(batch.lot_number)}
                    </span>
                </td>
                <td class="px-6 py-4 font-medium text-slate-900">
                    ${batch.quantity} units
                </td>
                <td class="px-6 py-4 ${dateClass}">
                    ${escapeHtml(batch.expiration_formatted)}
                    ${daysLeft <= 30 ? `<span class="ml-2 text-xs text-red-600">(${daysLeft} days left)</span>` : ''}
                    ${daysLeft <= 90 && daysLeft > 30 ? `<span class="ml-2 text-xs text-amber-600">(${daysLeft} days left)</span>` : ''}
                </td>
                <td class="px-6 py-4">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold ${badgeClass} border">
                        ${statusLabel}
                    </span>
                </td>
                <td class="px-6 py-4 text-right whitespace-nowrap">
                    ${getActionButtons(batch, userRole)}
                </td>
            </tr>
        `;
    }).join('');
}

// ========== US 2.2: Update Expiring Banner ==========
function updateExpiringBanner(count) {
    const banner = document.getElementById('expiring-banner');
    const message = document.getElementById('expiring-message');

    if (!banner || !message) return;

    if (count > 0) {
        banner.classList.remove('hidden');
        message.textContent = `${count} product(s) will expire next month. Please check the alerts tab for more details.`;
    } else {
        banner.classList.add('hidden');
    }
}

// ========== Filter Functions ==========
function applyFilter(filter) {
    if (currentFilter === filter) return;
    currentFilter = filter;
    fetchBatches();
}

function updateActiveFilter() {
    document.querySelectorAll('.filter-btn').forEach(btn => {
        const filterValue = btn.dataset.filter;
        if (filterValue === currentFilter) {
            btn.classList.remove('bg-white', 'text-slate-700', 'border-slate-300');
            btn.classList.add('bg-emerald-600', 'text-white');
        } else {
            btn.classList.remove('bg-emerald-600', 'text-white');
            btn.classList.add('bg-white', 'text-slate-700', 'border-slate-300');
        }
    });
}

// ========== Utility Functions ==========
function getRowClass(daysLeft) {
    if (daysLeft <= 30) return 'bg-red-50/50 hover:bg-red-50';
    if (daysLeft <= 90) return 'bg-amber-50/30 hover:bg-amber-50';
    return 'hover:bg-slate-50';
}

function getDateClass(daysLeft) {
    if (daysLeft <= 30) return 'text-red-700 font-medium';
    if (daysLeft <= 90) return 'text-amber-700 font-medium';
    return 'text-slate-600';
}

function getBadgeClass(daysLeft) {
    if (daysLeft <= 30) return 'bg-red-100 text-red-800 border-red-200';
    if (daysLeft <= 90) return 'bg-amber-100 text-amber-800 border-amber-200';
    return 'bg-emerald-100 text-emerald-800 border-emerald-200';
}

function getStatusLabel(daysLeft) {
    if (daysLeft <= 30) return 'Critical (< 30 days)';
    if (daysLeft <= 90) return 'Warning (< 90 days)';
    return 'Healthy (> 90 days)';
}

function getActionButtons(batch, userRole) {
    const daysLeft = batch.days_until_expiration || 0;

    if (daysLeft <= 30 && (userRole === 'pharmacist' || userRole === 'admin')) {
        return `
            <button onclick="markAsExpired(${batch.id})" 
                    class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-white bg-red-600 hover:bg-red-700 rounded-lg transition-colors">
                🗑️ Mark Expired
            </button>
        `;
    } else {
        return `
            <button onclick="dispenseMedicine(${batch.product.id})" 
                    class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg transition-colors">
                💊 Dispense
            </button>
        `;
    }
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function showToast(type, message) {
    const container = document.getElementById('toast-container');
    if (!container) return;

    const colors = {
        success: 'bg-emerald-500',
        error: 'bg-red-500',
        info: 'bg-blue-500'
    };

    const toast = document.createElement('div');
    toast.className = `${colors[type] || 'bg-gray-500'} text-white px-4 py-3 rounded-lg shadow-lg mb-2 max-w-md`;
    toast.textContent = message;

    container.appendChild(toast);
    setTimeout(() => toast.remove(), 4000);
}

// ========== Initialization ==========
document.addEventListener('DOMContentLoaded', () => {
    // Filter button click handlers
    document.querySelectorAll('.filter-btn').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            const filter = btn.dataset.filter;
            if (filter) applyFilter(filter);
        });
    });

    // Load data
    fetchStats();
    fetchBatches();

    // Auto-refresh every 30 seconds
    setInterval(() => {
        fetchBatches();
        fetchStats();
    }, 30000);
});

// Placeholder functions for EPIC 3 and 4
window.dispenseMedicine = function(productId) {
    showToast('info', 'Dispensing feature coming soon...');
};

window.markAsExpired = function(batchId) {
    showToast('info', 'Mark expired feature coming soon...');
};