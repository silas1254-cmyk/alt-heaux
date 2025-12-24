/**
 * Admin File Manager for Products
 * Handles file uploads, deletion, and metadata updates
 */

class ProductFileManager {
    constructor(productId) {
        this.productId = productId;
        // Use absolute path from document root - works regardless of where script is called from
        // Extract base path from current page location
        const pathParts = window.location.pathname.split('/');
        let basePath = '';
        // Find 'admin' in path and use everything up to and including it
        const adminIndex = pathParts.indexOf('admin');
        if (adminIndex !== -1) {
            basePath = pathParts.slice(0, adminIndex + 1).join('/');
        } else {
            basePath = '/admin';
        }
        this.apiEndpoint = basePath + '/files_api.php';
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.loadFiles();
    }

    setupEventListeners() {
        // File upload form
        const uploadForm = document.getElementById('fileUploadForm');
        if (uploadForm) {
            uploadForm.addEventListener('submit', (e) => this.handleUpload(e));
        }

        // File input change for preview
        const fileInput = document.getElementById('fileInput');
        if (fileInput) {
            fileInput.addEventListener('change', (e) => this.updateFileInfo(e));
        }
    }

    /**
     * Load files for the product
     */
    loadFiles() {
        fetch(`${this.apiEndpoint}?action=list&product_id=${this.productId}`)
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    this.displayFiles(data.files);
                } else {
                    this.showError('Failed to load files: ' + data.error);
                }
            })
            .catch(err => {
                console.error('Error loading files:', err);
                this.showError('Error loading files');
            });
    }

    /**
     * Display files in the list
     */
    displayFiles(files) {
        const filesList = document.getElementById('filesList');
        if (!filesList) return;

        if (files.length === 0) {
            filesList.innerHTML = `
                <div class="empty-state">
                    <p>No files uploaded for this product yet.</p>
                    <p class="text-muted">Upload files above to make them available for download after purchase.</p>
                </div>
            `;
            return;
        }

        let html = '';
        files.forEach(file => {
            html += `
                <div class="file-item" data-file-id="${file.id}">
                    <div class="file-header">
                        <div class="file-info">
                            <div class="file-name">
                                <i class="bi bi-file-earmark"></i>
                                ${this.escapeHtml(file.display_name)}
                                ${file.version ? `<span class="version-badge">v${this.escapeHtml(file.version)}</span>` : ''}
                            </div>
                            <div class="file-meta">
                                <span class="file-size">${file.file_size}</span>
                                <span class="separator">•</span>
                                <span class="upload-date">${file.upload_date}</span>
                                <span class="separator">•</span>
                                <span class="download-count">${file.download_count} downloads</span>
                            </div>
                        </div>
                        <div class="file-actions">
                            <button class="btn btn-sm btn-outline-primary" title="Edit" onclick="fileManager.editFile(${file.id})">
                                <i class="bi bi-gear"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger" title="Delete" onclick="fileManager.deleteFile(${file.id})">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </div>
                    ${file.description ? `
                        <div class="file-description">
                            ${this.escapeHtml(file.description)}
                        </div>
                    ` : ''}
                    <div class="file-original-name">
                        <strong>Original:</strong> ${this.escapeHtml(file.original_filename)}
                    </div>
                </div>
            `;
        });

        filesList.innerHTML = html;
    }

    /**
     * Handle file upload
     */
    async handleUpload(e) {
        e.preventDefault();

        const displayName = document.getElementById('displayName');
        const fileInput = document.getElementById('fileInput');
        const version = document.getElementById('version');
        const description = document.getElementById('description');
        const uploadBtn = e.target.querySelector('button[type="submit"]');

        if (!displayName.value || !fileInput.files[0]) {
            this.showError('Display name and file are required');
            return;
        }

        const formData = new FormData();
        formData.append('action', 'upload');
        formData.append('product_id', this.productId);
        formData.append('display_name', displayName.value);
        formData.append('version', version.value);
        formData.append('description', description.value);
        formData.append('file', fileInput.files[0]);

        const originalText = uploadBtn.textContent;
        uploadBtn.disabled = true;
        uploadBtn.textContent = 'Uploading...';

        try {
            const response = await fetch(this.apiEndpoint, {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                this.showSuccess('File uploaded successfully!');
                
                // Reset form
                e.target.reset();
                document.getElementById('fileInfo').style.display = 'none';
                
                // Reload files
                this.loadFiles();
            } else {
                this.showError('Upload failed: ' + data.error);
            }
        } catch (err) {
            console.error('Upload error:', err);
            this.showError('Upload failed: ' + err.message);
        } finally {
            uploadBtn.disabled = false;
            uploadBtn.textContent = originalText;
        }
    }

    /**
     * Delete file
     */
    deleteFile(fileId) {
        if (!confirm('Are you sure you want to delete this file?')) {
            return;
        }

        const formData = new FormData();
        formData.append('action', 'delete');
        formData.append('file_id', fileId);

        fetch(this.apiEndpoint, {
            method: 'POST',
            body: formData
        })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    this.showSuccess('File deleted successfully!');
                    this.loadFiles();
                } else {
                    this.showError('Delete failed: ' + data.error);
                }
            })
            .catch(err => {
                console.error('Error:', err);
                this.showError('Delete failed: ' + err.message);
            });
    }

    /**
     * Edit file metadata
     */
    editFile(fileId) {
        const fileItem = document.querySelector(`[data-file-id="${fileId}"]`);
        if (!fileItem) return;

        const displayName = fileItem.querySelector('.file-name')?.textContent?.trim() || '';
        const version = fileItem.querySelector('.version-badge')?.textContent?.replace('v', '')?.trim() || '';
        const description = fileItem.querySelector('.file-description')?.textContent?.trim() || '';

        const newDisplayName = prompt('Display name:', displayName);
        if (newDisplayName === null) return;

        const newVersion = prompt('Version (optional):', version);
        if (newVersion === null) return;

        const newDescription = prompt('Description (optional):', description);
        if (newDescription === null) return;

        const formData = new FormData();
        formData.append('action', 'update_metadata');
        formData.append('file_id', fileId);
        formData.append('display_name', newDisplayName);
        formData.append('version', newVersion);
        formData.append('description', newDescription);

        fetch(this.apiEndpoint, {
            method: 'POST',
            body: formData
        })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    this.showSuccess('File metadata updated!');
                    this.loadFiles();
                } else {
                    this.showError('Update failed: ' + data.error);
                }
            })
            .catch(err => {
                console.error('Error:', err);
                this.showError('Update failed: ' + err.message);
            });
    }

    /**
     * Update file info display
     */
    updateFileInfo(e) {
        const file = e.target.files[0];
        if (!file) {
            document.getElementById('fileInfo').style.display = 'none';
            return;
        }

        const fileInfo = document.getElementById('fileInfo');
        const fileSize = this.formatBytes(file.size);
        const fileType = file.name.split('.').pop().toUpperCase();

        fileInfo.innerHTML = `
            <div class="file-info-details">
                <strong>Selected File:</strong>
                <div class="info-row">
                    <span>Name:</span>
                    <span class="mono">${this.escapeHtml(file.name)}</span>
                </div>
                <div class="info-row">
                    <span>Size:</span>
                    <span>${fileSize}</span>
                </div>
                <div class="info-row">
                    <span>Type:</span>
                    <span>${fileType}</span>
                </div>
                ${file.size > 500 * 1024 * 1024 ? `
                    <div class="warning">
                        ⚠️ File exceeds 500MB limit
                    </div>
                ` : ''}
            </div>
        `;
        fileInfo.style.display = 'block';
    }

    /**
     * Format bytes for display
     */
    formatBytes(bytes) {
        const units = ['B', 'KB', 'MB', 'GB'];
        let size = bytes;
        let unitIndex = 0;

        while (size >= 1024 && unitIndex < units.length - 1) {
            size /= 1024;
            unitIndex++;
        }

        return Math.round(size * 100) / 100 + ' ' + units[unitIndex];
    }

    /**
     * Escape HTML to prevent XSS
     */
    escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, m => map[m]);
    }

    /**
     * Show error message
     */
    showError(message) {
        const alert = document.createElement('div');
        alert.className = 'alert alert-error';
        alert.textContent = message;
        document.body.insertBefore(alert, document.body.firstChild);
        
        setTimeout(() => alert.remove(), 5000);
    }

    /**
     * Show success message
     */
    showSuccess(message) {
        const alert = document.createElement('div');
        alert.className = 'alert alert-success';
        alert.textContent = message;
        document.body.insertBefore(alert, document.body.firstChild);
        
        setTimeout(() => alert.remove(), 5000);
    }
}
