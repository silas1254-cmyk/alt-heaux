/**
 * ALT HEAUX - Admin Dashboard Utilities
 * Theme toggle, modals, tables, forms, and UI helpers
 */

// ============================================================================
// THEME TOGGLE
// ============================================================================

class ThemeManager {
    constructor() {
        this.theme = localStorage.getItem('admin-theme') || 'dark';
        this.init();
    }

    init() {
        this.applyTheme(this.theme);
        this.createToggleButton();
    }

    createToggleButton() {
        const button = document.createElement('button');
        button.className = 'theme-toggle';
        button.innerHTML = this.theme === 'dark' ? '☀️' : '🌙';
        button.title = this.theme === 'dark' ? 'Switch to Light Mode' : 'Switch to Dark Mode';
        button.addEventListener('click', () => this.toggle());
        document.body.appendChild(button);
    }

    applyTheme(theme) {
        if (theme === 'light') {
            document.body.classList.add('light-theme');
        } else {
            document.body.classList.remove('light-theme');
        }
        this.theme = theme;
        localStorage.setItem('admin-theme', theme);
        this.updateToggleButton();
    }

    toggle() {
        this.applyTheme(this.theme === 'dark' ? 'light' : 'dark');
    }

    updateToggleButton() {
        const button = document.querySelector('.theme-toggle');
        if (button) {
            button.innerHTML = this.theme === 'dark' ? '☀️' : '🌙';
            button.title = this.theme === 'dark' ? 'Switch to Light Mode' : 'Switch to Dark Mode';
        }
    }
}

// ============================================================================
// MOBILE SIDEBAR TOGGLE
// ============================================================================

class SidebarManager {
    constructor() {
        this.sidebar = document.querySelector('.sidebar');
        this.init();
    }

    init() {
        this.createToggleButton();
        this.attachListeners();
    }

    createToggleButton() {
        const button = document.createElement('button');
        button.className = 'sidebar-toggle';
        button.innerHTML = '<i class="fas fa-bars"></i>';
        button.title = 'Toggle Sidebar';
        document.body.prepend(button);
        this.toggleButton = button;
    }

    attachListeners() {
        this.toggleButton.addEventListener('click', () => this.toggle());

        // Close sidebar when link is clicked
        if (this.sidebar) {
            this.sidebar.querySelectorAll('.nav-link').forEach(link => {
                link.addEventListener('click', () => this.close());
            });
        }

        // Close sidebar when clicking outside
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.sidebar') && !e.target.closest('.sidebar-toggle')) {
                this.close();
            }
        });
    }

    toggle() {
        if (this.sidebar) {
            this.sidebar.classList.toggle('active');
        }
    }

    close() {
        if (this.sidebar) {
            this.sidebar.classList.remove('active');
        }
    }
}

// ============================================================================
// MODAL MANAGER
// ============================================================================

class ModalManager {
    static create(options) {
        const {
            title = 'Confirm',
            message = 'Are you sure?',
            confirmText = 'Confirm',
            cancelText = 'Cancel',
            onConfirm = () => {},
            onCancel = () => {},
            isDestructive = false
        } = options;

        const modal = document.createElement('div');
        modal.className = 'modal fade confirm-modal';
        modal.id = 'confirmModal_' + Date.now();
        modal.tabIndex = -1;

        const confirmBtnClass = isDestructive ? 'btn-danger' : 'btn-primary';

        modal.innerHTML = `
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">${title}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <i class="fas fa-${isDestructive ? 'exclamation-triangle' : 'question-circle'}"></i>
                        <p>${message}</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">${cancelText}</button>
                        <button type="button" class="btn ${confirmBtnClass} confirm-btn">${confirmText}</button>
                    </div>
                </div>
            </div>
        `;

        document.body.appendChild(modal);

        const bsModal = new bootstrap.Modal(modal);
        const confirmBtn = modal.querySelector('.confirm-btn');

        confirmBtn.addEventListener('click', () => {
            onConfirm();
            bsModal.hide();
        });

        modal.addEventListener('hidden.bs.modal', () => {
            modal.remove();
            onCancel();
        });

        bsModal.show();
        return modal;
    }

    static confirm(message, onConfirm, onCancel = () => {}) {
        return this.create({
            title: 'Confirm Action',
            message,
            confirmText: 'Yes',
            cancelText: 'No',
            onConfirm,
            onCancel,
            isDestructive: true
        });
    }

    static alert(title, message) {
        return this.create({
            title,
            message,
            confirmText: 'OK',
            cancelText: '',
            onConfirm: () => {}
        });
    }
}

// ============================================================================
// SPINNER MANAGER
// ============================================================================

class SpinnerManager {
    static show(message = 'Loading...') {
        let overlay = document.querySelector('.spinner-overlay');
        if (!overlay) {
            overlay = document.createElement('div');
            overlay.className = 'spinner-overlay';
            overlay.innerHTML = `
                <div>
                    <div class="spinner"></div>
                    <div class="spinner-text">${message}</div>
                </div>
            `;
            document.body.appendChild(overlay);
        }
        overlay.classList.add('show');
        return overlay;
    }

    static hide() {
        const overlay = document.querySelector('.spinner-overlay');
        if (overlay) {
            overlay.classList.remove('show');
        }
    }

    static wrap(promise, message = 'Loading...') {
        this.show(message);
        return promise.finally(() => this.hide());
    }
}

// ============================================================================
// FORM VALIDATION
// ============================================================================

class FormValidator {
    static validate(form) {
        const inputs = form.querySelectorAll('input, textarea, select');
        let isValid = true;

        inputs.forEach(input => {
            if (input.hasAttribute('required') && !input.value.trim()) {
                this.markInvalid(input, 'This field is required');
                isValid = false;
            } else if (input.type === 'email' && input.value && !this.isValidEmail(input.value)) {
                this.markInvalid(input, 'Please enter a valid email');
                isValid = false;
            } else if (input.type === 'number' && input.value && isNaN(input.value)) {
                this.markInvalid(input, 'Please enter a valid number');
                isValid = false;
            } else {
                this.markValid(input);
            }
        });

        return isValid;
    }

    static markInvalid(input, message) {
        input.classList.add('is-invalid');
        let feedback = input.nextElementSibling;
        if (!feedback || !feedback.classList.contains('invalid-feedback')) {
            feedback = document.createElement('div');
            feedback.className = 'invalid-feedback';
            input.parentNode.insertBefore(feedback, input.nextSibling);
        }
        feedback.textContent = message;
        feedback.classList.add('show');
    }

    static markValid(input) {
        input.classList.remove('is-invalid');
        const feedback = input.nextElementSibling;
        if (feedback && feedback.classList.contains('invalid-feedback')) {
            feedback.classList.remove('show');
        }
    }

    static isValidEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    }
}

// ============================================================================
// TABLE UTILITIES
// ============================================================================

class TableManager {
    constructor(tableSelector) {
        this.table = document.querySelector(tableSelector);
        this.tbody = this.table?.querySelector('tbody');
        this.thead = this.table?.querySelector('thead');
        this.init();
    }

    init() {
        if (!this.table) return;
        this.makeHeadersSortable();
        this.attachRowActions();
    }

    makeHeadersSortable() {
        if (!this.thead) return;

        this.thead.querySelectorAll('th').forEach((th, index) => {
            if (!th.classList.contains('actions')) {
                th.classList.add('sortable');
                th.addEventListener('click', () => this.sort(index, th));
            }
        });
    }

    sort(columnIndex, headerCell) {
        if (!this.tbody) return;

        const rows = Array.from(this.tbody.querySelectorAll('tr'));
        const isAsc = headerCell.classList.contains('sort-asc');

        rows.sort((a, b) => {
            const aVal = a.children[columnIndex]?.textContent.trim();
            const bVal = b.children[columnIndex]?.textContent.trim();

            const aNum = parseFloat(aVal);
            const bNum = parseFloat(bVal);

            if (!isNaN(aNum) && !isNaN(bNum)) {
                return isAsc ? bNum - aNum : aNum - bNum;
            }

            return isAsc ? bVal.localeCompare(aVal) : aVal.localeCompare(bVal);
        });

        // Update header classes
        this.thead.querySelectorAll('th').forEach(th => {
            th.classList.remove('sort-asc', 'sort-desc');
        });
        headerCell.classList.add(isAsc ? 'sort-desc' : 'sort-asc');

        // Reorder rows
        rows.forEach(row => this.tbody.appendChild(row));
    }

    filter(searchText) {
        if (!this.tbody) return;

        const rows = this.tbody.querySelectorAll('tr');
        const searchLower = searchText.toLowerCase();

        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(searchLower) ? '' : 'none';
        });
    }

    attachRowActions() {
        if (!this.tbody) return;

        this.tbody.querySelectorAll('[data-action]').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                const action = btn.dataset.action;
                const id = btn.dataset.id;

                if (action === 'delete') {
                    ModalManager.confirm(
                        'Are you sure you want to delete this item? This action cannot be undone.',
                        () => {
                            SpinnerManager.show('Deleting...');
                            // Form submission will be handled by the button's form
                            btn.closest('form')?.submit();
                        }
                    );
                }
            });
        });
    }
}

// ============================================================================
// INITIALIZATION
// ============================================================================

document.addEventListener('DOMContentLoaded', () => {
    // Don't initialize theme/sidebar on login page
    const isLoginPage = document.body.classList.contains('login-page') || 
                       window.location.pathname.includes('admin/login.php') ||
                       document.querySelector('.login-container') !== null;
    
    if (!isLoginPage) {
        // Initialize theme manager
        const themeManager = new ThemeManager();

        // Initialize sidebar on mobile
        const sidebarManager = new SidebarManager();
    }

    // Attach form validation to all forms
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', (e) => {
            if (!FormValidator.validate(form)) {
                e.preventDefault();
            }
        });
    });

    // Initialize all tables with sorting
    document.querySelectorAll('table').forEach(table => {
        new TableManager(`#${table.id || 'table_' + Date.now()}`);
    });

    // Add data-label attributes to table cells for mobile view
    document.querySelectorAll('table').forEach(table => {
        const headers = Array.from(table.querySelectorAll('thead th')).map(th => th.textContent.trim());
        table.querySelectorAll('tbody td').forEach((td, index) => {
            const cellIndex = index % headers.length;
            if (headers[cellIndex]) {
                td.setAttribute('data-label', headers[cellIndex]);
            }
        });
    });
});

// Expose classes to window for external use
window.ModalManager = ModalManager;
window.SpinnerManager = SpinnerManager;
window.FormValidator = FormValidator;
window.TableManager = TableManager;
