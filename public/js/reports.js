console.log("this is the correct file")

const API_BASE = 'http://localhost/PharmaFEFO-v2/public/index.php?route=api';

// ========== Load Financial Report Data ==========
async function loadFinancialReport() {
    try {
        showLoading();

        // Get date range from inputs (if they exist)
        const startDate = document.getElementById('start_date')?.value || getDefaultStartDate();
        const endDate = document.getElementById('end_date')?.value || getDefaultEndDate();

        console.log('📊 Loading financial report:', startDate, 'to', endDate);

        const response = await fetch(`${API_BASE}&action=loss-report&start_date=${startDate}&end_date=${endDate}`);

        if (!response.ok) {
            if (response.status === 401) {
                window.location.href = '/index.php?route=login';
                return;
            }
            if (response.status === 403) {
                showError('Access denied. Admin role required.');
                return;
            }
            throw new Error(`HTTP ${response.status}`);
        }

        const result = await response.json();
        console.log('📊 Report data:', result);

        if (result.success) {
            renderReport(result.data);
        } else {
            showError(result.error || 'Failed to load report data');
        }
    } catch (error) {
        console.error('❌ Report fetch error:', error);
        showError('Network error. Please check your connection.');
    } finally {
        hideLoading();
    }
}

// ========== Render Report ==========
function renderReport(data) {
    // Update summary cards
    document.getElementById('total-loss').textContent = '€' + parseFloat(data.total_loss || 0).toFixed(2);
    document.getElementById('expired-count').textContent = data.expired_count || 0;
    document.getElementById('start-date-display').textContent = formatDate(data.start_date);
    document.getElementById('end-date-display').textContent = formatDate(data.end_date);

    // Render monthly losses
    renderMonthlyLosses(data.monthly_losses || []);

    // Render loss by product
    renderLossByProduct(data.loss_by_product || [], data.total_loss || 0);
}

// ========== Render Monthly Losses ==========
function renderMonthlyLosses(monthlyLosses) {
    const container = document.getElementById('monthly-losses-body');
    if (!container) return;

    if (monthlyLosses.length === 0) {
        container.innerHTML = `
            <tr>
                <td colspan="3" class="px-4 py-8 text-center text-slate-500">
                    No monthly data available.
                </td>
            </tr>
        `;
        return;
    }

    container.innerHTML = monthlyLosses.map(month => {
        const hasLoss = month.loss > 0;
        return `
            <tr class="hover:bg-slate-50">
                <td class="px-4 py-3 text-sm text-slate-700">${escapeHtml(month.month)}</td>
                <td class="px-4 py-3 text-sm text-right font-semibold ${hasLoss ? 'text-red-600' : 'text-emerald-600'}">
                    ${hasLoss ? '€' + parseFloat(month.loss).toFixed(2) : '€0.00'}
                </td>
                <td class="px-4 py-3 text-right">
                    ${hasLoss
            ? '<span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">🔴 Loss</span>'
            : '<span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800">🟢 No Loss</span>'
        }
                </td>
            </tr>
        `;
    }).join('');
}

// ========== Render Loss by Product ==========
function renderLossByProduct(products, totalLoss) {
    const container = document.getElementById('loss-by-product-body');
    if (!container) return;

    if (products.length === 0) {
        container.innerHTML = `
            <tr>
                <td colspan="4" class="px-4 py-8 text-center text-slate-500">
                    No expired products found.
                </td>
            </tr>
        `;
        return;
    }

    let totalQuantity = products.reduce((sum, p) => sum + p.quantity, 0);

    container.innerHTML = products.map(product => {
        const percentage = totalLoss > 0 ? ((product.loss / totalLoss) * 100) : 0;
        return `
            <tr class="hover:bg-slate-50">
                <td class="px-4 py-3 text-sm font-semibold text-slate-700">${escapeHtml(product.name)}</td>
                <td class="px-4 py-3 text-sm text-right text-slate-600">${product.quantity} units</td>
                <td class="px-4 py-3 text-sm text-right font-semibold text-red-600">€${parseFloat(product.loss).toFixed(2)}</td>
                <td class="px-4 py-3 text-sm text-right text-slate-600">${percentage.toFixed(1)}%</td>
            </tr>
        `;
    }).join('');

    // Update footer
    const footer = document.getElementById('loss-by-product-footer');
    if (footer) {
        footer.innerHTML = `
            <td class="px-4 py-3 text-sm font-bold text-slate-700">TOTAL</td>
            <td class="px-4 py-3 text-sm text-right font-bold text-slate-700">${totalQuantity} units</td>
            <td class="px-4 py-3 text-sm text-right font-bold text-red-700">€${parseFloat(totalLoss).toFixed(2)}</td>
            <td class="px-4 py-3 text-sm text-right font-bold text-slate-700">100%</td>
        `;
    }
}

// ========== Utility Functions ==========
function getDefaultStartDate() {
    const date = new Date();
    date.setMonth(date.getMonth() - 11);
    date.setDate(1);
    return date.toISOString().split('T')[0];
}

function getDefaultEndDate() {
    return new Date().toISOString().split('T')[0];
}

function formatDate(dateString) {
    if (!dateString) return '-';
    const date = new Date(dateString + 'T00:00:00');
    return date.toLocaleDateString('fr-FR');
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function showLoading() {
    const loader = document.getElementById('loading-overlay');
    if (loader) loader.classList.remove('hidden');
}

function hideLoading() {
    const loader = document.getElementById('loading-overlay');
    if (loader) loader.classList.add('hidden');
}

function showError(message) {
    const container = document.getElementById('message-container');
    if (!container) return;

    container.innerHTML = `
        <div class="mb-6 p-4 bg-red-50 border-l-4 border-red-500 text-red-700 rounded-r-lg">
            <div class="flex items-center">
                <span class="text-xl mr-2">❌</span>
                <span>${escapeHtml(message)}</span>
            </div>
        </div>
    `;
}

// ========== Initialization ==========
document.addEventListener('DOMContentLoaded', () => {
    console.log('📊 Reports page loaded');

    // Load report data
    loadFinancialReport();

    // Auto-refresh every 60 seconds
    setInterval(loadFinancialReport, 60000);
});