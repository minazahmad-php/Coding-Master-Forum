<?php
/**
 * File Upload Form View
 */

$this->layout('layouts/app', ['title' => $title ?? 'File Upload']);
?>

<div class="upload-page">
    <div class="container">
        <div class="upload-header">
            <h1>File Upload</h1>
            <p>Upload files to share with the community</p>
        </div>

        <div class="upload-content">
            <div class="upload-form-container">
                <form id="upload-form" class="upload-form" enctype="multipart/form-data">
                    <?= View::csrfField() ?>
                    
                    <div class="form-group">
                        <label for="category" class="form-label">File Category</label>
                        <select id="category" name="category" class="form-select" required>
                            <option value="">Select a category</option>
                            <?php foreach ($categories as $key => $label): ?>
                                <option value="<?= View::escape($key) ?>"><?= View::escape($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="file" class="form-label">Choose File</label>
                        <div class="file-input-container">
                            <input type="file" id="file" name="file" class="file-input" required>
                            <label for="file" class="file-input-label">
                                <i class="fas fa-cloud-upload-alt"></i>
                                <span class="file-input-text">Choose file or drag & drop</span>
                                <span class="file-input-info">Maximum file size: 10MB</span>
                            </label>
                        </div>
                        <div class="file-preview" id="file-preview"></div>
                    </div>

                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="generate_thumbnail" value="true" checked>
                            <span class="checkbox-text">Generate thumbnail for images</span>
                        </label>
                    </div>

                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="resize_image" value="true" checked>
                            <span class="checkbox-text">Resize large images</span>
                        </label>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary" id="upload-btn">
                            <i class="fas fa-upload"></i>
                            Upload File
                        </button>
                        <button type="button" class="btn btn-outline" id="clear-btn">
                            <i class="fas fa-times"></i>
                            Clear
                        </button>
                    </div>
                </form>
            </div>

            <div class="upload-progress" id="upload-progress" style="display: none;">
                <div class="progress-bar">
                    <div class="progress-fill" id="progress-fill"></div>
                </div>
                <div class="progress-text" id="progress-text">Uploading...</div>
            </div>

            <div class="upload-result" id="upload-result" style="display: none;">
                <div class="result-content" id="result-content"></div>
            </div>
        </div>

        <div class="upload-info">
            <h3>Upload Guidelines</h3>
            <div class="info-grid">
                <div class="info-item">
                    <h4>Images</h4>
                    <ul>
                        <li>JPG, PNG, GIF, WebP, SVG</li>
                        <li>Maximum size: 10MB</li>
                        <li>Recommended: 1920x1080 or smaller</li>
                    </ul>
                </div>
                <div class="info-item">
                    <h4>Documents</h4>
                    <ul>
                        <li>PDF, DOC, DOCX, TXT, RTF</li>
                        <li>Maximum size: 10MB</li>
                        <li>Text files preferred</li>
                    </ul>
                </div>
                <div class="info-item">
                    <h4>Videos</h4>
                    <ul>
                        <li>MP4, WebM, OGG, AVI</li>
                        <li>Maximum size: 50MB</li>
                        <li>HD quality recommended</li>
                    </ul>
                </div>
                <div class="info-item">
                    <h4>Audio</h4>
                    <ul>
                        <li>MP3, WAV, OGG, M4A</li>
                        <li>Maximum size: 20MB</li>
                        <li>High quality preferred</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.upload-page {
    padding: 2rem 0;
}

.upload-header {
    text-align: center;
    margin-bottom: 3rem;
}

.upload-header h1 {
    font-size: 2.5rem;
    font-weight: 700;
    color: var(--text-primary);
    margin-bottom: 1rem;
}

.upload-header p {
    font-size: 1.125rem;
    color: var(--text-secondary);
}

.upload-content {
    max-width: 600px;
    margin: 0 auto;
}

.upload-form-container {
    background: var(--bg-primary);
    border: 1px solid var(--border-color);
    border-radius: var(--radius-lg);
    padding: 2rem;
    box-shadow: var(--shadow-sm);
    margin-bottom: 2rem;
}

.upload-form {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.form-label {
    font-weight: 500;
    color: var(--text-primary);
}

.form-select {
    padding: 0.75rem 1rem;
    border: 2px solid var(--border-color);
    border-radius: var(--radius-md);
    font-size: 1rem;
    background: var(--bg-primary);
    color: var(--text-primary);
    transition: border-color var(--transition-fast);
}

.form-select:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.file-input-container {
    position: relative;
}

.file-input {
    position: absolute;
    opacity: 0;
    width: 100%;
    height: 100%;
    cursor: pointer;
}

.file-input-label {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 3rem 2rem;
    border: 2px dashed var(--border-color);
    border-radius: var(--radius-lg);
    background: var(--bg-secondary);
    cursor: pointer;
    transition: all var(--transition-fast);
    text-align: center;
}

.file-input-label:hover {
    border-color: var(--primary-color);
    background: var(--primary-color-light);
}

.file-input-label i {
    font-size: 3rem;
    color: var(--primary-color);
    margin-bottom: 1rem;
}

.file-input-text {
    font-size: 1.125rem;
    font-weight: 500;
    color: var(--text-primary);
    margin-bottom: 0.5rem;
}

.file-input-info {
    font-size: 0.875rem;
    color: var(--text-muted);
}

.file-preview {
    margin-top: 1rem;
    padding: 1rem;
    background: var(--bg-secondary);
    border-radius: var(--radius-md);
    display: none;
}

.file-preview.show {
    display: block;
}

.file-preview-content {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.file-preview-icon {
    width: 48px;
    height: 48px;
    background: var(--primary-color);
    color: white;
    border-radius: var(--radius-md);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
}

.file-preview-info {
    flex: 1;
}

.file-preview-name {
    font-weight: 500;
    color: var(--text-primary);
    margin-bottom: 0.25rem;
}

.file-preview-size {
    font-size: 0.875rem;
    color: var(--text-muted);
}

.file-preview-remove {
    background: var(--error-color);
    color: white;
    border: none;
    border-radius: var(--radius-sm);
    padding: 0.5rem;
    cursor: pointer;
    transition: background-color var(--transition-fast);
}

.file-preview-remove:hover {
    background: var(--error-color-dark);
}

.checkbox-label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    cursor: pointer;
}

.checkbox-label input[type="checkbox"] {
    width: 18px;
    height: 18px;
    accent-color: var(--primary-color);
}

.checkbox-text {
    color: var(--text-secondary);
    font-size: 0.9rem;
}

.form-actions {
    display: flex;
    gap: 1rem;
    justify-content: center;
}

.btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.875rem 1.5rem;
    border: none;
    border-radius: var(--radius-md);
    font-size: 1rem;
    font-weight: 500;
    text-decoration: none;
    cursor: pointer;
    transition: all var(--transition-fast);
}

.btn-primary {
    background: var(--primary-color);
    color: white;
}

.btn-primary:hover {
    background: var(--primary-color-dark);
}

.btn-outline {
    background: transparent;
    color: var(--text-primary);
    border: 2px solid var(--border-color);
}

.btn-outline:hover {
    background: var(--bg-secondary);
    border-color: var(--primary-color);
}

.upload-progress {
    background: var(--bg-primary);
    border: 1px solid var(--border-color);
    border-radius: var(--radius-lg);
    padding: 2rem;
    margin-bottom: 2rem;
}

.progress-bar {
    width: 100%;
    height: 8px;
    background: var(--bg-secondary);
    border-radius: 4px;
    overflow: hidden;
    margin-bottom: 1rem;
}

.progress-fill {
    height: 100%;
    background: var(--primary-color);
    width: 0%;
    transition: width 0.3s ease;
}

.progress-text {
    text-align: center;
    color: var(--text-secondary);
    font-weight: 500;
}

.upload-result {
    background: var(--bg-primary);
    border: 1px solid var(--border-color);
    border-radius: var(--radius-lg);
    padding: 2rem;
    margin-bottom: 2rem;
}

.result-success {
    border-color: var(--success-color);
    background: var(--success-color-light);
}

.result-error {
    border-color: var(--error-color);
    background: var(--error-color-light);
}

.result-content {
    text-align: center;
}

.result-icon {
    font-size: 3rem;
    margin-bottom: 1rem;
}

.result-success .result-icon {
    color: var(--success-color);
}

.result-error .result-icon {
    color: var(--error-color);
}

.result-title {
    font-size: 1.25rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
}

.result-success .result-title {
    color: var(--success-color);
}

.result-error .result-title {
    color: var(--error-color);
}

.result-message {
    color: var(--text-secondary);
    margin-bottom: 1rem;
}

.result-actions {
    display: flex;
    gap: 1rem;
    justify-content: center;
}

.upload-info {
    max-width: 800px;
    margin: 0 auto;
}

.upload-info h3 {
    text-align: center;
    font-size: 1.5rem;
    font-weight: 600;
    color: var(--text-primary);
    margin-bottom: 2rem;
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.5rem;
}

.info-item {
    background: var(--bg-primary);
    border: 1px solid var(--border-color);
    border-radius: var(--radius-lg);
    padding: 1.5rem;
}

.info-item h4 {
    font-size: 1.125rem;
    font-weight: 600;
    color: var(--text-primary);
    margin-bottom: 1rem;
}

.info-item ul {
    margin: 0;
    padding-left: 1.25rem;
    color: var(--text-secondary);
}

.info-item li {
    margin-bottom: 0.5rem;
    font-size: 0.875rem;
}

/* Responsive Design */
@media (max-width: 768px) {
    .upload-page {
        padding: 1rem 0;
    }
    
    .upload-header h1 {
        font-size: 2rem;
    }
    
    .upload-form-container {
        padding: 1.5rem;
    }
    
    .file-input-label {
        padding: 2rem 1rem;
    }
    
    .file-input-label i {
        font-size: 2rem;
    }
    
    .form-actions {
        flex-direction: column;
    }
    
    .info-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const uploadForm = document.getElementById('upload-form');
    const fileInput = document.getElementById('file');
    const filePreview = document.getElementById('file-preview');
    const uploadProgress = document.getElementById('upload-progress');
    const progressFill = document.getElementById('progress-fill');
    const progressText = document.getElementById('progress-text');
    const uploadResult = document.getElementById('upload-result');
    const resultContent = document.getElementById('result-content');
    const uploadBtn = document.getElementById('upload-btn');
    const clearBtn = document.getElementById('clear-btn');

    // File input change handler
    fileInput.addEventListener('change', function() {
        const file = this.files[0];
        if (file) {
            showFilePreview(file);
        } else {
            hideFilePreview();
        }
    });

    // Drag and drop handlers
    const fileInputLabel = document.querySelector('.file-input-label');
    
    fileInputLabel.addEventListener('dragover', function(e) {
        e.preventDefault();
        this.classList.add('drag-over');
    });
    
    fileInputLabel.addEventListener('dragleave', function(e) {
        e.preventDefault();
        this.classList.remove('drag-over');
    });
    
    fileInputLabel.addEventListener('drop', function(e) {
        e.preventDefault();
        this.classList.remove('drag-over');
        
        const files = e.dataTransfer.files;
        if (files.length > 0) {
            fileInput.files = files;
            showFilePreview(files[0]);
        }
    });

    // Form submission
    uploadForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const file = fileInput.files[0];
        
        if (!file) {
            showError('Please select a file to upload');
            return;
        }
        
        uploadFile(formData);
    });

    // Clear button
    clearBtn.addEventListener('click', function() {
        fileInput.value = '';
        hideFilePreview();
        hideUploadProgress();
        hideUploadResult();
    });

    function showFilePreview(file) {
        const previewContent = `
            <div class="file-preview-content">
                <div class="file-preview-icon">
                    <i class="fas fa-file"></i>
                </div>
                <div class="file-preview-info">
                    <div class="file-preview-name">${file.name}</div>
                    <div class="file-preview-size">${formatFileSize(file.size)}</div>
                </div>
                <button type="button" class="file-preview-remove" onclick="removeFile()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;
        
        filePreview.innerHTML = previewContent;
        filePreview.classList.add('show');
    }

    function hideFilePreview() {
        filePreview.classList.remove('show');
    }

    function removeFile() {
        fileInput.value = '';
        hideFilePreview();
    }

    function uploadFile(formData) {
        uploadBtn.disabled = true;
        uploadBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Uploading...';
        
        showUploadProgress();
        hideUploadResult();

        const xhr = new XMLHttpRequest();
        
        xhr.upload.addEventListener('progress', function(e) {
            if (e.lengthComputable) {
                const percentComplete = (e.loaded / e.total) * 100;
                progressFill.style.width = percentComplete + '%';
                progressText.textContent = `Uploading... ${Math.round(percentComplete)}%`;
            }
        });
        
        xhr.addEventListener('load', function() {
            uploadBtn.disabled = false;
            uploadBtn.innerHTML = '<i class="fas fa-upload"></i> Upload File';
            
            hideUploadProgress();
            
            try {
                const response = JSON.parse(xhr.responseText);
                
                if (response.success) {
                    showSuccess(response);
                } else {
                    showError(response.error || 'Upload failed');
                }
            } catch (e) {
                showError('Invalid response from server');
            }
        });
        
        xhr.addEventListener('error', function() {
            uploadBtn.disabled = false;
            uploadBtn.innerHTML = '<i class="fas fa-upload"></i> Upload File';
            
            hideUploadProgress();
            showError('Upload failed. Please try again.');
        });
        
        xhr.open('POST', '/upload');
        xhr.send(formData);
    }

    function showUploadProgress() {
        uploadProgress.style.display = 'block';
        progressFill.style.width = '0%';
        progressText.textContent = 'Uploading...';
    }

    function hideUploadProgress() {
        uploadProgress.style.display = 'none';
    }

    function showSuccess(response) {
        const content = `
            <div class="result-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="result-title">Upload Successful!</div>
            <div class="result-message">Your file has been uploaded successfully.</div>
            <div class="result-actions">
                <a href="${response.url}" class="btn btn-primary" target="_blank">
                    <i class="fas fa-external-link-alt"></i>
                    View File
                </a>
                <button type="button" class="btn btn-outline" onclick="uploadAnother()">
                    <i class="fas fa-plus"></i>
                    Upload Another
                </button>
            </div>
        `;
        
        resultContent.innerHTML = content;
        uploadResult.className = 'upload-result result-success';
        uploadResult.style.display = 'block';
    }

    function showError(message) {
        const content = `
            <div class="result-icon">
                <i class="fas fa-exclamation-circle"></i>
            </div>
            <div class="result-title">Upload Failed</div>
            <div class="result-message">${message}</div>
            <div class="result-actions">
                <button type="button" class="btn btn-primary" onclick="retryUpload()">
                    <i class="fas fa-redo"></i>
                    Try Again
                </button>
            </div>
        `;
        
        resultContent.innerHTML = content;
        uploadResult.className = 'upload-result result-error';
        uploadResult.style.display = 'block';
    }

    function hideUploadResult() {
        uploadResult.style.display = 'none';
    }

    function uploadAnother() {
        fileInput.value = '';
        hideFilePreview();
        hideUploadResult();
        uploadForm.scrollIntoView({ behavior: 'smooth' });
    }

    function retryUpload() {
        hideUploadResult();
        uploadForm.scrollIntoView({ behavior: 'smooth' });
    }

    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    // Make functions globally available
    window.removeFile = removeFile;
    window.uploadAnother = uploadAnother;
    window.retryUpload = retryUpload;
});
</script>