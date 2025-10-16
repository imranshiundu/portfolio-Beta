class SettingsManager {
    constructor() {
        this.currentTab = 'personal';
        this.cropper = null;
        this.currentImageType = null;
        
        this.init();
    }

    init() {
        this.bindEvents();
        this.initTabs();
        this.initCharacterCounters();
        this.initImageUploads();
        this.initColorPickers();
    }

    bindEvents() {
        // Tab navigation
        document.querySelectorAll('.nav-item').forEach(item => {
            item.addEventListener('click', (e) => {
                e.preventDefault();
                const tab = item.getAttribute('data-tab');
                this.switchTab(tab);
            });
        });

        // Save all settings
        document.getElementById('saveAllSettings').addEventListener('click', () => {
            this.saveAllSettings();
        });

        // Preview site
        document.getElementById('previewSite').addEventListener('click', () => {
            this.previewSite();
        });

        // Maintenance mode toggle
        document.querySelector('input[name="maintenance_mode"]').addEventListener('change', (e) => {
            this.toggleMaintenanceMessage(e.target.checked);
        });

        // Remove avatar
        document.getElementById('removeAvatar').addEventListener('click', () => {
            this.removeAvatar();
        });

        // Backup file selection
        document.getElementById('backupFile').addEventListener('change', (e) => {
            this.handleBackupFileSelect(e);
        });

        // System tools
        document.getElementById('optimizeDbBtn').addEventListener('click', () => {
            this.optimizeDatabase();
        });

        document.getElementById('fullBackupBtn').addEventListener('click', () => {
            this.createFullBackup();
        });

        document.getElementById('restoreBackupBtn').addEventListener('click', () => {
            this.restoreBackup();
        });

        document.getElementById('viewAllLogs').addEventListener('click', () => {
            this.viewAllLogs();
        });

        // Modal events
        document.getElementById('closeCropModal').addEventListener('click', () => {
            this.closeCropModal();
        });

        document.getElementById('cancelCrop').addEventListener('click', () => {
            this.closeCropModal();
        });

        document.getElementById('applyCrop').addEventListener('click', () => {
            this.applyCrop();
        });
    }

    initTabs() {
        // Set first tab as active
        const firstTab = document.querySelector('.nav-item').getAttribute('data-tab');
        this.switchTab(firstTab);
    }

    switchTab(tabName) {
        // Update navigation
        document.querySelectorAll('.nav-item').forEach(item => {
            item.classList.remove('active');
        });
        document.querySelector(`[data-tab="${tabName}"]`).classList.add('active');

        // Update tabs
        document.querySelectorAll('.settings-tab').forEach(tab => {
            tab.classList.remove('active');
        });
        document.getElementById(`${tabName}Tab`).classList.add('active');

        this.currentTab = tabName;
    }

    initCharacterCounters() {
        const metaTitle = document.getElementById('seo_meta_title');
        const metaDescription = document.getElementById('seo_meta_description');

        if (metaTitle) {
            metaTitle.addEventListener('input', () => {
                document.getElementById('metaTitleCount').textContent = metaTitle.value.length;
            });
            // Trigger initial count
            metaTitle.dispatchEvent(new Event('input'));
        }

        if (metaDescription) {
            metaDescription.addEventListener('input', () => {
                document.getElementById('metaDescriptionCount').textContent = metaDescription.value.length;
            });
            // Trigger initial count
            metaDescription.dispatchEvent(new Event('input'));
        }
    }

    initImageUploads() {
        // Avatar upload
        document.getElementById('avatarUpload').addEventListener('change', (e) => {
            this.handleImageUpload(e, 'avatar');
        });

        // OG image upload
        document.getElementById('ogImageUpload').addEventListener('change', (e) => {
            this.handleImageUpload(e, 'og_image');
        });
    }

    initColorPickers() {
        // Initialize color pickers
        document.querySelectorAll('input[type="color"]').forEach(picker => {
            picker.addEventListener('input', (e) => {
                const hexInput = e.target.parentElement.querySelector('.color-hex-input');
                hexInput.value = e.target.value;
            });
        });
    }

    handleImageUpload(event, type) {
        const file = event.target.files[0];
        if (!file) return;

        // Validate file type
        if (!file.type.startsWith('image/')) {
            this.showMessage('Please select a valid image file.', 'error');
            return;
        }

        // Validate file size (max 5MB)
        if (file.size > 5 * 1024 * 1024) {
            this.showMessage('Image size must be less than 5MB.', 'error');
            return;
        }

        this.currentImageType = type;
        this.openCropModal(file);
    }

    openCropModal(file) {
        const modal = document.getElementById('imageCropModal');
        const cropImage = document.getElementById('cropImage');
        
        const reader = new FileReader();
        reader.onload = (e) => {
            cropImage.src = e.target.result;
            modal.classList.add('active');
            
            // Initialize cropper
            setTimeout(() => {
                this.cropper = new Cropper(cropImage, {
                    aspectRatio: this.currentImageType === 'avatar' ? 1 : 1200/630,
                    viewMode: 1,
                    autoCropArea: 0.8,
                    responsive: true,
                    restore: false,
                    guides: true,
                    center: true,
                    highlight: false,
                    cropBoxMovable: true,
                    cropBoxResizable: true,
                    toggleDragModeOnDblclick: false,
                });
            }, 100);
        };
        reader.readAsDataURL(file);
    }

    closeCropModal() {
        const modal = document.getElementById('imageCropModal');
        modal.classList.remove('active');
        
        if (this.cropper) {
            this.cropper.destroy();
            this.cropper = null;
        }
        
        // Clear file inputs
        document.getElementById('avatarUpload').value = '';
        document.getElementById('ogImageUpload').value = '';
    }

    applyCrop() {
        if (!this.cropper) return;

        const canvas = this.cropper.getCroppedCanvas();
        canvas.toBlob((blob) => {
            this.uploadCroppedImage(blob);
        }, 'image/jpeg', 0.8);
    }

    uploadCroppedImage(blob) {
        const formData = new FormData();
        formData.append('action', 'upload_image');
        formData.append('image_type', this.currentImageType);
        formData.append('image', blob, `cropped_${this.currentImageType}.jpg`);

        this.showLoading('Uploading image...');

        fetch('', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            this.hideLoading();
            if (data.success) {
                this.updateImagePreview(data.image_url);
                this.showMessage('Image uploaded successfully!', 'success');
                this.closeCropModal();
            } else {
                this.showMessage(data.message || 'Error uploading image.', 'error');
            }
        })
        .catch(error => {
            this.hideLoading();
            this.showMessage('Error uploading image.', 'error');
            console.error('Upload error:', error);
        });
    }

    updateImagePreview(imageUrl) {
        if (this.currentImageType === 'avatar') {
            document.getElementById('currentAvatar').src = imageUrl + '?t=' + Date.now();
        } else if (this.currentImageType === 'og_image') {
            document.getElementById('currentOgImage').src = imageUrl + '?t=' + Date.now();
        }
    }

    removeAvatar() {
        if (!confirm('Are you sure you want to remove your profile image?')) {
            return;
        }

        const formData = new FormData();
        formData.append('action', 'remove_avatar');

        this.showLoading('Removing image...');

        fetch('', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            this.hideLoading();
            if (data.success) {
                document.getElementById('currentAvatar').src = '../assets/images/default-avatar.jpg';
                this.showMessage('Profile image removed successfully!', 'success');
            } else {
                this.showMessage(data.message || 'Error removing image.', 'error');
            }
        })
        .catch(error => {
            this.hideLoading();
            this.showMessage('Error removing image.', 'error');
            console.error('Remove error:', error);
        });
    }

    toggleMaintenanceMessage(show) {
        const messageDiv = document.getElementById('maintenanceMessage');
        messageDiv.style.display = show ? 'block' : 'none';
    }

    handleBackupFileSelect(event) {
        const file = event.target.files[0];
        const restoreBtn = document.getElementById('restoreBackupBtn');
        
        if (file && (file.name.endsWith('.sql') || file.name.endsWith('.zip'))) {
            restoreBtn.disabled = false;
        } else {
            restoreBtn.disabled = true;
            this.showMessage('Please select a valid SQL or ZIP backup file.', 'error');
        }
    }

    async saveAllSettings() {
        this.showLoading('Saving all settings...');
        
        try {
            const forms = document.querySelectorAll('.settings-form');
            const promises = Array.from(forms).map(form => this.submitForm(form));
            
            await Promise.all(promises);
            this.showMessage('All settings saved successfully!', 'success');
        } catch (error) {
            this.showMessage('Error saving some settings.', 'error');
        } finally {
            this.hideLoading();
        }
    }

    submitForm(form) {
        return new Promise((resolve, reject) => {
            const formData = new FormData(form);
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (response.ok) {
                    resolve();
                } else {
                    reject(new Error('Form submission failed'));
                }
            })
            .catch(reject);
        });
    }

    previewSite() {
        window.open('../index.php', '_blank');
    }

    optimizeDatabase() {
        if (!confirm('This will optimize your database for better performance. Continue?')) {
            return;
        }

        this.showLoading('Optimizing database...');

        fetch('', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=optimize_database'
        })
        .then(response => response.json())
        .then(data => {
            this.hideLoading();
            if (data.success) {
                this.showMessage('Database optimized successfully!', 'success');
            } else {
                this.showMessage(data.message || 'Error optimizing database.', 'error');
            }
        })
        .catch(error => {
            this.hideLoading();
            this.showMessage('Error optimizing database.', 'error');
            console.error('Optimize error:', error);
        });
    }

    createFullBackup() {
        if (!confirm('This will create a full backup including database and files. Continue?')) {
            return;
        }

        this.showLoading('Creating full backup...');

        fetch('', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=full_backup'
        })
        .then(response => response.json())
        .then(data => {
            this.hideLoading();
            if (data.success) {
                this.showMessage('Full backup created successfully!', 'success');
                // Refresh backup list
                this.refreshBackupList();
            } else {
                this.showMessage(data.message || 'Error creating backup.', 'error');
            }
        })
        .catch(error => {
            this.hideLoading();
            this.showMessage('Error creating backup.', 'error');
            console.error('Backup error:', error);
        });
    }

    restoreBackup() {
        const fileInput = document.getElementById('backupFile');
        const file = fileInput.files[0];
        
        if (!file) {
            this.showMessage('Please select a backup file first.', 'error');
            return;
        }

        if (!confirm('WARNING: This will overwrite your current data. Make sure you have a backup. Continue?')) {
            return;
        }

        const formData = new FormData();
        formData.append('action', 'restore_backup');
        formData.append('backup_file', file);

        this.showLoading('Restoring backup...');

        fetch('', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            this.hideLoading();
            if (data.success) {
                this.showMessage('Backup restored successfully!', 'success');
                fileInput.value = '';
                document.getElementById('restoreBackupBtn').disabled = true;
            } else {
                this.showMessage(data.message || 'Error restoring backup.', 'error');
            }
        })
        .catch(error => {
            this.hideLoading();
            this.showMessage('Error restoring backup.', 'error');
            console.error('Restore error:', error);
        });
    }

    viewAllLogs() {
        this.showMessage('Security logs feature coming soon!', 'info');
    }

    refreshBackupList() {
        // This would typically make an AJAX call to refresh the backup list
        console.log('Refreshing backup list...');
    }

    showMessage(message, type = 'info') {
        // Create message element
        const messageEl = document.createElement('div');
        messageEl.className = `alert alert-${type}`;
        messageEl.innerHTML = `
            <div class="alert-icon">
                <i class="fas fa-${this.getMessageIcon(type)}"></i>
            </div>
            <div class="alert-content">
                <p>${message}</p>
            </div>
            <button class="alert-close" onclick="this.parentElement.remove()">
                <i class="fas fa-times"></i>
            </button>
        `;

        // Insert after header
        const header = document.querySelector('.admin-header');
        header.parentNode.insertBefore(messageEl, header.nextSibling);

        // Auto remove after 5 seconds
        setTimeout(() => {
            if (messageEl.parentNode) {
                messageEl.remove();
            }
        }, 5000);
    }

    getMessageIcon(type) {
        const icons = {
            success: 'check-circle',
            error: 'exclamation-circle',
            warning: 'exclamation-triangle',
            info: 'info-circle'
        };
        return icons[type] || 'info-circle';
    }

    showLoading(message) {
        // Create loading overlay
        const loadingEl = document.createElement('div');
        loadingEl.id = 'loadingOverlay';
        loadingEl.className = 'loading-overlay';
        loadingEl.innerHTML = `
            <div class="loading-spinner"></div>
            <div class="loading-message">${message}</div>
        `;

        document.body.appendChild(loadingEl);
    }

    hideLoading() {
        const loadingEl = document.getElementById('loadingOverlay');
        if (loadingEl) {
            loadingEl.remove();
        }
    }
}

// Initialize settings manager when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    new SettingsManager();
});

// Add loading overlay styles
const loadingStyles = `
.loading-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.7);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    z-index: 9999;
    color: white;
}

.loading-spinner {
    width: 40px;
    height: 40px;
    border: 4px solid rgba(255, 255, 255, 0.3);
    border-top: 4px solid white;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin-bottom: 1rem;
}

.loading-message {
    font-size: 1rem;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
`;

const styleSheet = document.createElement('style');
styleSheet.textContent = loadingStyles;
document.head.appendChild(styleSheet);