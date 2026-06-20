/**
 * PharmaFEFO - Dashboard JavaScript
 * Calls API endpoints to fill the view
 */
console.log("✅ Dashboard script loaded");

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
            <tr class="${rowClass} transition-colors batch-row" data-batch-id="${batch.id}" data-product-id="${batch.product.id}">
                <td class="px-6 py-4 font-semibold text-slate-900">
                    ${escapeHtml(batch.product.name)}
                </td>
                <td class="px-6 py-4">
                    <span class="lot-number font-mono bg-slate-100 text-slate-700 px-2 py-0.5 rounded border border-slate-200 text-xs">
                        ${escapeHtml(batch.lot_number)}
                    </span>
                </td>
                <td class="quantity-cell px-6 py-4 font-medium text-slate-900">
                    ${batch.quantity} units
                </td>
                <td class="expiration-cell px-6 py-4 ${dateClass}">
                    ${escapeHtml(batch.expiration_formatted)}
                    ${daysLeft <= 30 ? `<span class="ml-2 text-xs text-red-600">(${daysLeft} days left)</span>` : ''}
                    ${daysLeft <= 90 && daysLeft > 30 ? `<span class="ml-2 text-xs text-amber-600">(${daysLeft} days left)</span>` : ''}
                </td>
                <td class="status-cell px-6 py-4">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold ${badgeClass} border">
                        ${statusLabel}
                    </span>
                </td>
                <td class="action-cell px-6 py-4 text-right whitespace-nowrap">
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
    const isOutOfStock = batch.quantity <= 0;

    if (isOutOfStock) {
        return `<span class="text-xs text-gray-400 italic">Out of stock</span>`;
    }

    if (daysLeft <= 30 && (userRole === 'pharmacist' || userRole === 'admin')) {
        return `
            <button onclick="markAsExpired(${batch.id})" 
                    class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-white bg-red-600 hover:bg-red-700 rounded-lg transition-colors">
                🗑️ Mark Expired
            </button>
            <button onclick="dispenseMedicine(${batch.product.id})" 
                    data-product-id="${batch.product.id}"
                    class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg transition-colors ml-2">
                💊 Dispense
            </button>
        `;
    } else {
        return `
            <button onclick="dispenseMedicine(${batch.product.id})" 
                    data-product-id="${batch.product.id}"
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
        info: 'bg-blue-500',
        warning: 'bg-amber-500'
    };

    const toast = document.createElement('div');
    toast.className = `${colors[type] || 'bg-gray-500'} text-white px-4 py-3 rounded-lg shadow-lg mb-2 max-w-md`;
    toast.textContent = message;

    container.appendChild(toast);
    setTimeout(() => toast.remove(), 4000);
}

// ========== US 3.1: FEFO Dispensing ==========
window.dispenseMedicine = async function(productId) {
    try {
        // Show loading state on the button
        const buttons = document.querySelectorAll(`[data-product-id="${productId}"]`);
        buttons.forEach(btn => {
            btn.disabled = true;
            btn.textContent = '⏳ Processing...';
            btn.classList.add('opacity-50', 'cursor-not-allowed');
        });

        // Send request to API - automatically dispense 1 unit
        const response = await fetch(`${API_BASE}&action=dispense`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                product_id: productId,
                quantity: 1  // Always dispense 1 unit
            })
        });

        const result = await response.json();

        // Handle response
        if (response.status === 401) {
            showToast('error', 'Session expired. Please login again.');
            window.location.href = '/index.php?route=login';
            return;
        }

        if (response.status === 403) {
            showToast('error', 'Access denied. You need Preparer role to dispense.');
            return;
        }

        if (response.status === 404 && result.out_of_stock) {
            showToast('error', 'No stock available for this product.');
            await fetchBatches();
            await fetchStats();
            return;
        }

        if (!response.ok) {
            throw new Error(result.error || `HTTP ${response.status}`);
        }

        if (result.success) {
            // Show success message
            showToast('success', `✅ Dispensed 1 unit of ${result.batch.lot_number}`);

            // US 3.1: Update UI without page reload
            updateBatchQuantity(result);

            // Refresh stats
            await fetchStats();
        } else {
            showToast('error', result.error || 'Failed to dispense medication');
        }
    } catch (error) {
        console.error('Dispense error:', error);
        showToast('error', 'Network error. Please check your connection and try again.');
    } finally {
        // Restore buttons
        const buttons = document.querySelectorAll(`[data-product-id="${productId}"]`);
        buttons.forEach(btn => {
            btn.disabled = false;
            btn.textContent = '💊 Dispense';
            btn.classList.remove('opacity-50', 'cursor-not-allowed');
        });
    }
};

function updateBatchQuantity(result) {
    const batch = result.batch;

    // Find the row containing this batch
    const rows = document.querySelectorAll('#batch-table-body tr');

    let rowFound = false;

    rows.forEach(row => {
        // Find the lot number in this row
        const lotElement = row.querySelector('.lot-number');
        if (lotElement && lotElement.textContent === batch.lot_number) {
            rowFound = true;

            // Update quantity cell
            const qtyCell = row.querySelector('.quantity-cell');
            if (qtyCell) {
                qtyCell.textContent = batch.quantity + ' units';
            }

            // If out of stock, gray out or remove the row
            if (result.out_of_stock) {
                // US 3.1: Row disappears or grays out
                row.style.opacity = '0.5';
                row.style.backgroundColor = '#f3f4f6';
                row.classList.add('line-through');

                // Add "Out of Stock" badge
                const statusCell = row.querySelector('.status-cell');
                if (statusCell) {
                    statusCell.innerHTML = `
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-gray-100 text-gray-800 border border-gray-200">
                            Out of Stock
                        </span>
                    `;
                }

                // Disable action button
                const actionCell = row.querySelector('.action-cell');
                if (actionCell) {
                    actionCell.innerHTML = `
                        <span class="text-xs text-gray-400 italic">Out of stock</span>
                    `;
                }

                showToast('info', `Batch ${batch.lot_number} is now out of stock.`);

                // Remove row after 3 seconds
                setTimeout(() => {
                    row.style.transition = 'opacity 0.5s ease';
                    row.style.opacity = '0';
                    setTimeout(() => {
                        row.remove();
                        // If no rows left, show empty message
                        const tbody = document.getElementById('batch-table-body');
                        if (tbody && tbody.children.length === 0) {
                            tbody.innerHTML = `
                                <tr>
                                    <td colspan="6" class="px-6 py-8 text-center text-slate-500">
                                        No batches available. Please receive new stock.
                                    </td>
                                </tr>
                            `;
                        }
                    }, 500);
                }, 2000);
            } else if (result.remaining_batch) {
                // Update with new batch info (FEFO rule - next batch)
                const lotElement = row.querySelector('.lot-number');
                if (lotElement) {
                    lotElement.textContent = result.remaining_batch.lot_number;
                }

                // Update expiration date
                const expCell = row.querySelector('.expiration-cell');
                if (expCell) {
                    const daysLeft = result.remaining_batch.days_until_expiration;
                    const dateClass = daysLeft <= 30 ? 'text-red-700 font-medium' :
                        (daysLeft <= 90 ? 'text-amber-700 font-medium' : 'text-slate-600');
                    expCell.className = `px-6 py-4 ${dateClass}`;
                    expCell.innerHTML = result.remaining_batch.expiration_date +
                        (daysLeft <= 30 ? `<span class="ml-2 text-xs text-red-600">(${daysLeft} days left)</span>` : '') +
                        (daysLeft <= 90 && daysLeft > 30 ? `<span class="ml-2 text-xs text-amber-600">(${daysLeft} days left)</span>` : '');
                }

                // Update status badge
                const statusCell = row.querySelector('.status-cell');
                if (statusCell) {
                    const daysLeft = result.remaining_batch.days_until_expiration;
                    const badgeClass = daysLeft <= 30 ? 'bg-red-100 text-red-800 border-red-200' :
                        (daysLeft <= 90 ? 'bg-amber-100 text-amber-800 border-amber-200' : 'bg-emerald-100 text-emerald-800 border-emerald-200');
                    const statusLabel = daysLeft <= 30 ? 'Critical (< 30 days)' :
                        (daysLeft <= 90 ? 'Warning (< 90 days)' : 'Healthy (> 90 days)');
                    statusCell.innerHTML = `
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold ${badgeClass} border">
                            ${statusLabel}
                        </span>
                    `;
                }
            }
        }
    });

    // If row not found, refresh the table
    if (!rowFound) {
        console.log('Batch row not found, refreshing table...');
        fetchBatches();
    }
}

// ========== US 4.1: Mark Expired ==========

window.markAsExpired = function(batchId) {
    showToast('info', 'Mark expired feature coming soon...');
};

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