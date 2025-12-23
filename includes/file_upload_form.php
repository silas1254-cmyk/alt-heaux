<?php
/**
 * File Upload Form with Variant Support
 * Simplified version - use in admin/products.php
 */

function renderFileUploadForm($product_id) {
    global $conn;
    ?>
    <div class="card mt-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">📁 Upload Product Files</h5>
        </div>
        <div class="card-body">
            <form id="fileUploadForm" enctype="multipart/form-data">
                <input type="hidden" name="product_id" value="<?php echo intval($product_id); ?>">
                
                <div class="mb-3">
                    <label class="form-label">File *</label>
                    <input type="file" class="form-control" id="fileInput" name="file" required>
                    <small class="text-muted">Max 500MB. Allowed: ZIP, PDF, EXE, DMG, RAR, etc.</small>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Display Name *</label>
                            <input type="text" class="form-control" name="display_name" 
                                   placeholder="e.g., Design File, Template v2" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Version</label>
                            <input type="text" class="form-control" name="version" 
                                   placeholder="e.g., 1.0, 2.0">
                        </div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Description</label>
                    <textarea class="form-control" name="description" rows="2" 
                              placeholder="What's included in this file?"></textarea>
                </div>
                
                <!-- Variant Fields (Optional) -->
                <div class="card card-light mb-3">
                    <div class="card-header">
                        <h6 class="mb-0">🎨 Variant Information (Optional)</h6>
                        <small class="text-muted">If file is for specific size or color</small>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <label class="form-label">Size Variant</label>
                                <select class="form-control" name="size_variant">
                                    <option value="">-- All Sizes --</option>
                                    <option value="XS">XS</option>
                                    <option value="S">S (Small)</option>
                                    <option value="M">M (Medium)</option>
                                    <option value="L">L (Large)</option>
                                    <option value="XL">XL</option>
                                    <option value="2XL">2XL</option>
                                    <option value="3XL">3XL</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Color Variant</label>
                                <select class="form-control" name="color_variant">
                                    <option value="">-- All Colors --</option>
                                    <option value="Black">Black</option>
                                    <option value="White">White</option>
                                    <option value="Red">Red</option>
                                    <option value="Blue">Blue</option>
                                    <option value="Green">Green</option>
                                    <option value="Yellow">Yellow</option>
                                    <option value="Purple">Purple</option>
                                    <option value="Pink">Pink</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-upload me-2"></i>Upload File
                </button>
                <button type="reset" class="btn btn-secondary">Clear</button>
            </form>
        </div>
    </div>
    
    <!-- Files List -->
    <div class="card mt-4">
        <div class="card-header bg-info text-white">
            <h5 class="mb-0">📋 Files for This Product</h5>
        </div>
        <div class="card-body">
            <div id="filesList">
                <p class="text-muted">Loading...</p>
            </div>
        </div>
    </div>
    
    <script>
    const productId = <?php echo intval($product_id); ?>;
    
    // Handle form submit
    document.getElementById('fileUploadForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
        fetch('files_api.php?action=upload', {
            method: 'POST',
            body: formData
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                alert('✓ File uploaded!');
                document.getElementById('fileUploadForm').reset();
                loadFiles();
            } else {
                alert('✗ Error: ' + (data.error || data.message));
            }
        })
        .catch(e => {
            console.error(e);
            alert('Upload failed - check console');
        });
    });
    
    // Load files list
    function loadFiles() {
        fetch(`files_api.php?action=list&product_id=${productId}`)
        .then(r => r.json())
        .then(data => {
            const container = document.getElementById('filesList');
            
            if (!data.files || data.files.length === 0) {
                container.innerHTML = '<p class="text-muted">No files</p>';
                return;
            }
            
            let html = '';
            data.files.forEach(f => {
                html += `
                    <div class="list-group-item d-flex justify-content-between">
                        <div>
                            <h6>${f.display_name}</h6>
                            <small class="text-muted">${f.original_filename} • ${f.file_size}</small>
                            ${f.variant_label ? '<br><small class="badge bg-info">' + f.variant_label + '</small>' : ''}
                        </div>
                        <button class="btn btn-sm btn-danger" onclick="deleteFile(${f.id})">Delete</button>
                    </div>
                `;
            });
            
            container.innerHTML = `<div class="list-group">${html}</div>`;
        });
    }
    
    // Delete file
    function deleteFile(fileId) {
        if (!confirm('Delete file?')) return;
        
        fetch('files_api.php?action=delete', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({file_id: fileId})
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                loadFiles();
            } else {
                alert('Error: ' + data.error);
            }
        });
    }
    
    // Load on page load
    loadFiles();
    </script>
    <?php
}
?>
