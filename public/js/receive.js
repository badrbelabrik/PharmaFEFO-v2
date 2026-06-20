const BASE_URL = window.location.origin + '/PharmaFEFO-v2/public/index.php?route=api';

document.addEventListener('DOMContentLoaded', function() {
    console.log('✅ Receive page loaded');

    // Load products
    loadProducts();

    // Handle form submission
    const form = document.getElementById('receive-form');
    if (form) {
        form.addEventListener('submit', handleSubmit);
    }
});

/**
 * Load products from API
 */
async function loadProducts() {
    const select = document.getElementById('product_id');

    try {
        console.log('📦 Loading products from:', BASE_URL + '&action=products');

        const response = await fetch(BASE_URL + '&action=products');

        console.log('Response status:', response.status);

        if (!response.ok) {
            throw new Error('HTTP ' + response.status);
        }

        const result = await response.json();
        console.log('Products data:', result);

        if (result.success && result.data.length > 0) {
            select.innerHTML = '<option value="">-- Choose Medication --</option>';

            result.data.forEach(function(product) {
                const option = document.createElement('option');
                option.value = product.id;
                option.textContent = product.name + ' (SN: ' + product.serial_number + ')';
                select.appendChild(option);
            });

            console.log('✅ ' + result.data.length + ' products loaded');
        } else {
            select.innerHTML = '<option value="">-- No products available --</option>';
        }
    } catch (error) {
        console.error('❌ Error loading products:', error);
        select.innerHTML = '<option value="">-- Error loading products --</option>';
        showMessage('error', 'Failed to load medications. Please refresh the page.');
    }
}

async function handleSubmit(event) {
    event.preventDefault();
    console.log('📤 Form submitted');

    const form = event.target;
    const data = {
        product_id: document.getElementById('product_id').value,
        lot_number: document.getElementById('lot_number').value,
        expiration_date: document.getElementById('expiration_date').value,
        quantity: document.getElementById('quantity').value,
        purchase_price: document.getElementById('purchase_price').value
    };

    console.log('Form data:', data);

    // Validate
    if (!data.product_id) {
        showMessage('error', 'Please select a medication.');
        return;
    }

    if (!data.lot_number) {
        showMessage('error', 'Please enter a lot number.');
        return;
    }

    if (!data.expiration_date) {
        showMessage('error', 'Please select an expiration date.');
        return;
    }

    const expDate = new Date(data.expiration_date);
    const today = new Date();
    today.setHours(0, 0, 0, 0);

    if (expDate <= today) {
        showMessage('error', 'Expiration date must be in the future.');
        return;
    }

    if (parseInt(data.quantity) <= 0) {
        showMessage('error', 'Quantity must be greater than 0.');
        return;
    }

    if (parseFloat(data.purchase_price) < 0) {
        showMessage('error', 'Purchase price cannot be negative.');
        return;
    }

    // Show loading
    const submitBtn = document.getElementById('submit-btn');
    const originalText = submitBtn.textContent;
    submitBtn.disabled = true;
    submitBtn.textContent = '⏳ Processing...';
    submitBtn.classList.add('opacity-50', 'cursor-not-allowed');

    try {
        console.log('🚀 Sending request to:', BASE_URL + '&action=receive');

        const response = await fetch(BASE_URL + '&action=receive', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        });

        console.log('Response status:', response.status);

        const result = await response.json();
        console.log('Response data:', result);

        if (response.status === 401) {
            showMessage('error', 'Session expired. Please login again.');
            setTimeout(function() {
                window.location.href = '/index.php?route=login';
            }, 2000);
            return;
        }

        if (response.status === 403) {
            showMessage('error', 'Access denied. Preparer role required.');
            return;
        }

        if (!response.ok) {
            throw new Error(result.error || 'HTTP ' + response.status);
        }

        if (result.success) {
            // ✅ Success - Stay on same page
            showMessage('success', result.message);

            // ✅ Reset the form
            form.reset();

            // ✅ Re-enable the submit button
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
            submitBtn.classList.remove('opacity-50', 'cursor-not-allowed');

            // ✅ Scroll to the message
            const messageContainer = document.getElementById('message-container');
            if (messageContainer) {
                messageContainer.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }

            // ✅ Optional: Reload products dropdown (if you want to keep it fresh)
            // loadProducts();

        } else {
            if (result.errors) {
                showMessage('error', result.errors.join(', '));
            } else {
                showMessage('error', result.error || 'Failed to receive stock');
            }

            // Restore button on error
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
            submitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
        }
    } catch (error) {
        console.error('❌ Error:', error);
        showMessage('error', 'Network error: ' + error.message);

        // Restore button on error
        submitBtn.disabled = false;
        submitBtn.textContent = originalText;
        submitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
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
        error: 'bg-red-50 border-red-500 text-red-700'
    };

    const icons = {
        success: '✅',
        error: '❌'
    };

    container.innerHTML = `
        <div class="mb-4 p-4 border-l-4 rounded-r-lg ${colors[type] || colors.error}">
            <div class="flex items-center">
                <span class="text-xl mr-2">${icons[type] || 'ℹ️'}</span>
                <span>${escapeHtml(message)}</span>
            </div>
        </div>
    `;

    container.scrollIntoView({ behavior: 'smooth', block: 'center' });
}

/**
 * Escape HTML
 */
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}