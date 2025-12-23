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

// ============================================================================
// TOAST NOTIFICATION SYSTEM
// ============================================================================

class ToastManager {
    constructor() {
        this.container = document.querySelector('.toast-container');
        if (!this.container) {
            this.container = document.createElement('div');
            this.container.className = 'toast-container';
            document.body.appendChild(this.container);
        }
    }

    show(message, type = 'info', duration = 3000) {
        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        
        const icons = {
            success: 'fa-check-circle',
            error: 'fa-exclamation-circle',
            warning: 'fa-exclamation-triangle',
            info: 'fa-info-circle'
        };

        toast.innerHTML = `
            <i class="fas ${icons[type]} toast-icon"></i>
            <div class="toast-content">${message}</div>
            <button class="toast-close" onclick="this.parentElement.remove()">
                <i class="fas fa-times"></i>
            </button>
        `;

        this.container.appendChild(toast);

        if (duration > 0) {
            setTimeout(() => {
                toast.classList.add('removing');
                setTimeout(() => toast.remove(), 300);
            }, duration);
        }

        return toast;
    }

    success(message, duration = 3000) {
        return this.show(message, 'success', duration);
    }

    error(message, duration = 5000) {
        return this.show(message, 'error', duration);
    }

    warning(message, duration = 4000) {
        return this.show(message, 'warning', duration);
    }

    info(message, duration = 3000) {
        return this.show(message, 'info', duration);
    }
}

window.ToastManager = new ToastManager();

// ============================================================================
// KEYBOARD SHORTCUTS
// ============================================================================

class KeyboardShortcuts {
    constructor() {
        this.shortcuts = {
            'ctrl+s': { description: 'Save form', action: () => this.saveForm() },
            'ctrl+shift+k': { description: 'Show shortcuts', action: () => this.showShortcuts() },
            'esc': { description: 'Close modals', action: () => this.closeModals() }
        };
        this.attach();
    }

    attach() {
        document.addEventListener('keydown', (e) => {
            const key = this.getKeyCombo(e);
            if (this.shortcuts[key]) {
                e.preventDefault();
                this.shortcuts[key].action();
            }
        });
    }

    getKeyCombo(e) {
        const parts = [];
        if (e.ctrlKey) parts.push('ctrl');
        if (e.shiftKey) parts.push('shift');
        if (e.altKey) parts.push('alt');
        
        if (e.key.length === 1) {
            parts.push(e.key.toLowerCase());
        } else if (e.key === 'Escape') {
            return 'esc';
        }
        
        return parts.join('+');
    }

    saveForm() {
        const form = document.querySelector('form');
        if (form) {
            form.submit();
            window.ToastManager.success('Form saved!');
        }
    }

    closeModals() {
        document.querySelectorAll('.modal.show').forEach(modal => {
            const bsModal = bootstrap.Modal.getInstance(modal);
            if (bsModal) bsModal.hide();
        });
    }

    showShortcuts() {
        const modal = document.createElement('div');
        modal.className = 'modal fade keyboard-shortcuts-modal';
        modal.tabIndex = -1;
        
        let shortcutsHtml = '';
        for (const [key, data] of Object.entries(this.shortcuts)) {
            shortcutsHtml += `
                <div class="shortcut-item">
                    <span>${data.description}</span>
                    <span class="key">${key.toUpperCase()}</span>
                </div>
            `;
        }

        modal.innerHTML = `
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Keyboard Shortcuts</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        ${shortcutsHtml}
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        `;

        document.body.appendChild(modal);
        const bsModal = new bootstrap.Modal(modal);
        bsModal.show();

        modal.addEventListener('hidden.bs.modal', () => modal.remove());
    }
}

window.KeyboardShortcuts = new KeyboardShortcuts();

// ============================================================================
// SIDEBAR COLLAPSE
// ============================================================================

class SidebarCollapseManager {
    constructor() {
        this.sidebar = document.querySelector('.sidebar');
        this.init();
    }

    init() {
        const isCollapsed = localStorage.getItem('sidebar-collapsed') === 'true';
        if (isCollapsed) {
            this.collapse();
        }
        
        this.addCollapseButton();
    }

    addCollapseButton() {
        if (!this.sidebar) return;

        const btn = document.createElement('button');
        btn.className = 'sidebar-toggle-collapse';
        btn.innerHTML = '<i class="fas fa-angle-left"></i> Collapse';
        btn.addEventListener('click', () => this.toggle());

        const logoutSection = this.sidebar.querySelector('.logout-section');
        if (logoutSection) {
            logoutSection.insertBefore(btn, logoutSection.firstChild);
        }
    }

    toggle() {
        if (this.sidebar.classList.contains('collapsed')) {
            this.expand();
        } else {
            this.collapse();
        }
    }

    collapse() {
        this.sidebar.classList.add('collapsed');
        localStorage.setItem('sidebar-collapsed', 'true');
    }

    expand() {
        this.sidebar.classList.remove('collapsed');
        localStorage.setItem('sidebar-collapsed', 'false');
    }
}

// Initialize sidebar collapse after page loads
document.addEventListener('DOMContentLoaded', () => {
    if (document.querySelector('.sidebar')) {
        new SidebarCollapseManager();
    }
});

// ============================================================================
// BULK ACTIONS
// ============================================================================

class BulkActionsManager {
    constructor(tableSelector = 'table') {
        this.table = document.querySelector(tableSelector);
        this.selectedIds = new Set();
        this.init();
    }

    init() {
        if (!this.table) return;

        // Add checkboxes to header and rows
        this.addCheckboxes();
        this.attachListeners();
    }

    addCheckboxes() {
        const header = this.table.querySelector('thead');
        if (!header) return;

        // Add master checkbox to header
        const masterCheckbox = document.createElement('th');
        masterCheckbox.innerHTML = '<input type="checkbox" class="form-check-input master-checkbox">';
        header.querySelector('tr').insertBefore(masterCheckbox, header.querySelector('tr').firstChild);

        // Add checkboxes to rows
        this.table.querySelectorAll('tbody tr').forEach(row => {
            const checkbox = document.createElement('td');
            checkbox.innerHTML = '<input type="checkbox" class="form-check-input row-checkbox">';
            row.insertBefore(checkbox, row.firstChild);
        });
    }

    attachListeners() {
        const masterCheckbox = this.table.querySelector('.master-checkbox');
        const rowCheckboxes = this.table.querySelectorAll('.row-checkbox');

        if (masterCheckbox) {
            masterCheckbox.addEventListener('change', (e) => {
                rowCheckboxes.forEach(cb => {
                    cb.checked = e.target.checked;
                    this.updateSelectedIds();
                });
            });
        }

        rowCheckboxes.forEach(cb => {
            cb.addEventListener('change', () => {
                this.updateSelectedIds();
            });
        });
    }

    updateSelectedIds() {
        this.selectedIds.clear();
        this.table.querySelectorAll('tbody .row-checkbox:checked').forEach(cb => {
            const row = cb.closest('tr');
            const id = row.dataset.id || row.querySelector('[data-id]')?.dataset.id;
            if (id) this.selectedIds.add(id);
        });

        this.updateBulkActionsBar();
    }

    updateBulkActionsBar() {
        let bar = document.querySelector('.bulk-actions-bar');
        
        if (this.selectedIds.size > 0) {
            if (!bar) {
                bar = document.createElement('div');
                bar.className = 'bulk-actions-bar';
                bar.innerHTML = `
                    <div class="bulk-actions-info">
                        <span class="count">${this.selectedIds.size} selected</span>
                    </div>
                    <div class="bulk-actions-buttons">
                        <button class="btn btn-danger btn-sm" onclick="location.reload()">Delete Selected</button>
                    </div>
                `;
                this.table.parentElement.insertBefore(bar, this.table);
            } else {
                bar.querySelector('.count').textContent = `${this.selectedIds.size} selected`;
            }
        } else if (bar) {
            bar.remove();
        }
    }

    getSelected() {
        return Array.from(this.selectedIds);
    }
}

window.BulkActionsManager = BulkActionsManager;

// ============================================================================
// ENHANCED INITIALIZATION
// ============================================================================

document.addEventListener('DOMContentLoaded', () => {
    const isLoginPage = document.body.classList.contains('login-page') || 
                       window.location.pathname.includes('admin/login.php') ||
                       document.querySelector('.login-container') !== null;
    
    if (!isLoginPage) {
        // Enhanced initialization
        const themeManager = new ThemeManager();
        const sidebarManager = new SidebarManager();
        
        // Add keyboard shortcuts hint to buttons if needed
        document.querySelectorAll('form button[type="submit"]').forEach(btn => {
            if (!btn.querySelector('.keyboard-hint')) {
                const hint = document.createElement('span');
                hint.className = 'keyboard-hint';
                hint.textContent = 'Ctrl+S';
                btn.appendChild(hint);
            }
        });
    }
});
