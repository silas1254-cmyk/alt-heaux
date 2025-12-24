/**
 * ALT HEAUX - Admin Dashboard
 * Clean utilities only
 */

document.addEventListener('DOMContentLoaded', function() {
    initBootstrapFeatures();
});

function initBootstrapFeatures() {
    // Initialize Bootstrap toasts if present
    const toastElements = document.querySelectorAll('.toast');
    toastElements.forEach(el => {
        new bootstrap.Toast(el).show();
    });

    // Initialize tooltips
    const tooltipElements = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    tooltipElements.forEach(el => {
        new bootstrap.Tooltip(el);
    });
}

function showAlert(message, type = 'info') {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    const container = document.querySelector('.main-content');
    if (container) {
        container.insertBefore(alertDiv, container.firstChild);
    }
}

function confirmDelete(message = 'Are you sure you want to delete this?') {
    return confirm(message);
}
