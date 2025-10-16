class BlogManager {
    constructor() {
        this.editor = null;
        this.tagify = null;
        this.selectedPosts = new Set();
        this.currentImageUpload = null;
        
        this.init();
    }

    init() {
        this.bindEvents();
        this.initEditor();
        this.initTagify();
        this.initCharacterCounters();
        this.initBulkActions();
    }

    bindEvents() {
        // Form submission
        const blogForm = document.getElementById('blogForm');
        if (blogForm) {
            blogForm.addEventListener('submit', (e) => this.handleFormSubmit(e));
        }

        // Title slug generation
        const titleInput = document.getElementById('post_title');
        if (titleInput) {
            titleInput.addEventListener('blur', () => this.generateSlug());
        }

        // Featured image upload
        const featuredImageInput = document.getElementById('featured_image');
        if (featuredImageInput) {
            featuredImageInput.addEventListener('change', (e) => this.handleFeaturedImageUpload(e));
        }

        // Remove featured image
        const removeImageBtn = document.getElementById('removeFeaturedImage');
        if (removeImageBtn) {
            removeImageBtn.addEventListener('click', () => this.removeFeaturedImage());
        }

        // Search functionality
        const searchInput = document.getElementById('postsSearch');
        if (searchInput) {
            searchInput.addEventListener('input', (e) => this.handleSearch(e));
        }

        // Filter functionality
        const statusFilter = document.getElementById('statusFilter');
        const categoryFilter = document.getElementById('categoryFilter');
        if (statusFilter) statusFilter.addEventListener('change', () => this.applyFilters());
        if (categoryFilter) categoryFilter.addEventListener('change', () => this.applyFilters());

        // Clear filters
        const clearFiltersBtn = document.getElementById('clearFilters');
        if (clearFiltersBtn) {
            clearFiltersBtn.addEventListener('click', () => this.clearFilters());
        }

        // Delete post buttons
        document.querySelectorAll('.delete-post').forEach(btn => {
            btn.addEventListener('click', (e) => this.showDeleteModal(e));
        });

        // Modal events
        this.bindModalEvents();
    }

    initEditor() {
        const editorElement = document.getElementById('post_editor');
        if (!editorElement) return;

        this.editor = new Quill('#post_editor', {
            modules: {
                toolbar: [
                    [{ 'header': [1, 2, 3, 4, 5, 6, false] }],
                    ['bold', 'italic', 'underline', 'strike'],
                    [{ 'color': [] }, { 'background': [] }],
                    [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                    [{ 'indent': '-1'}, { 'indent': '+1' }],
                    ['blockquote', 'code-block'],
                    ['link', 'image', 'video'],
                    ['clean']
                ]
            },
            theme: 'snow',
            placeholder: 'Start writing your blog post...'
        });

        // Sync editor content with hidden textarea
        this.editor.on('text-change', () => {
            const content = document.getElementById('post_content');
            content.value = this.editor.root.innerHTML;
        });

        // Handle image upload in editor
        this.editor.getModule('toolbar').addHandler('image', () => {
            this.showImageUploadModal();
        });
    }

    initTagify() {
        const tagsInput = document.getElementById('post_tags');
        if (!tagsInput) return;

        this.tagify = new Tagify(tagsInput, {
            whitelist: [],
            maxTags: 10,
            dropdown: {
                maxItems: 20,
                classname: "tags-dropdown",
                enabled: 0,
                closeOnSelect: false
            }
        });

        // Load popular tags
        this.loadPopularTags();
    }

    initCharacterCounters() {
        // Excerpt counter
        const excerptInput = document.getElementById('post_excerpt');
        if (excerptInput) {
            excerptInput.addEventListener('input', () => {
                const count = excerptInput.value.length;
                document.getElementById('excerptCount').textContent = count;
            });
            excerptInput.dispatchEvent(new Event('input'));
        }

        // Meta title counter
        const metaTitleInput = document.getElementById('meta_title');
        if (metaTitleInput) {
            metaTitleInput.addEventListener('input', () => {
                const count = metaTitleInput.value.length;
                document.getElementById('metaTitleCount').textContent = count;
            });
            metaTitleInput.dispatchEvent(new Event('input'));
        }

        // Meta description counter
        const metaDescInput = document.getElementById('meta_description');
        if (metaDescInput) {
            metaDescInput.addEventListener('input', () => {
                const count = metaDescInput.value.length;
                document.getElementById('metaDescriptionCount').textContent = count;
            });
            metaDescInput.dispatchEvent(new Event('input'));
        }
    }

    initBulkActions() {
        // Select all checkbox
        const selectAllCheckbox = document.getElementById('selectAllPosts');
        if (selectAllCheckbox) {
            selectAllCheckbox.addEventListener('change', (e) => this.toggleSelectAll(e));
        }

        // Individual post checkboxes
        document.querySelectorAll('.post-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', () => this.updateBulkActions());
        });

        // Apply bulk action
        const applyBulkBtn = document.getElementById('applyBulkAction');
        if (applyBulkBtn) {
            applyBulkBtn.addEventListener('click', () => this.applyBulkAction());
        }

        // Cancel bulk action
        const cancelBulkBtn = document.getElementById('cancelBulkAction');
        if (cancelBulkBtn) {
            cancelBulkBtn.addEventListener('click', () => this.cancelBulkAction());
        }
    }

    bindModalEvents() {
        // Delete modal
        const deleteModal = document.getElementById('deleteModal');
        const closeDeleteBtn = document.getElementById('closeDeleteModal');
        const cancelDeleteBtn = document.getElementById('cancelDelete');

        if (closeDeleteBtn) {
            closeDeleteBtn.addEventListener('click', () => this.hideModal(deleteModal));
        }
        if (cancelDeleteBtn) {
            cancelDeleteBtn.addEventListener('click', () => this.hideModal(deleteModal));
        }

        // Image upload modal
        const imageModal = document.getElementById('imageUploadModal');
        const closeImageBtn = document.getElementById('closeImageModal');
        const browseImagesBtn = document.getElementById('browseImages');
        const imageUploadInput = document.getElementById('imageUpload');

        if (closeImageBtn) {
            closeImageBtn.addEventListener('click', () => this.hideModal(imageModal));
        }
        if (browseImagesBtn) {
            browseImagesBtn.addEventListener('click', () => imageUploadInput.click());
        }
        if (imageUploadInput) {
            imageUploadInput.addEventListener('change', (e) => this.handleImageUpload(e));
        }

        // Drag and drop for image upload
        const uploadArea = document.getElementById('uploadArea');
        if (uploadArea) {
            uploadArea.addEventListener('dragover', (e) => this.handleDragOver(e));
            uploadArea.addEventListener('dragleave', (e) => this.handleDragLeave(e));
            uploadArea.addEventListener('drop', (e) => this.handleDrop(e));
        }
    }

    async handleFormSubmit(e) {
        e.preventDefault();
        
        const form = e.target;
        const formData = new FormData(form);
        
        // Validate required fields
        if (!this.validateForm(formData)) {
            return;
        }

        this.showLoading('Saving post...');

        try {
            const response = await fetch('', {
                method: 'POST',
                body: formData
            });

            if (response.ok) {
                this.showMessage('Post saved successfully!', 'success');
                // Redirect or update UI as needed
            } else {
                throw new Error('Failed to save post');
            }
        } catch (error) {
            this.showMessage('Error saving post: ' + error.message, 'error');
        } finally {
            this.hideLoading();
        }
    }

    validateForm(formData) {
        let isValid = true;

        // Validate title
        const title = formData.get('post_title');
        if (!title || title.trim().length === 0) {
            this.showError('titleError', 'Title is required');
            isValid = false;
        } else {
            this.hideError('titleError');
        }

        // Validate content
        const content = formData.get('post_content');
        if (!content || content.trim().length === 0 || content === '<p><br></p>') {
            this.showError('contentError', 'Content is required');
            isValid = false;
        } else {
            this.hideError('contentError');
        }

        return isValid;
    }

    generateSlug() {
        const titleInput = document.getElementById('post_title');
        const slugInput = document.getElementById('post_slug');
        
        if (!titleInput || !slugInput || slugInput.value) return;

        const title = titleInput.value.trim();
        if (!title) return;

        const slug = title
            .toLowerCase()
            .replace(/[^a-z0-9 -]/g, '')
            .replace(/\s+/g, '-')
            .replace(/-+/g, '-')
            .trim();

        slugInput.value = slug;
    }

    async handleFeaturedImageUpload(e) {
        const file = e.target.files[0];
        if (!file) return;

        if (!this.validateImageFile(file)) {
            return;
        }

        this.showLoading('Uploading image...');

        try {
            const formData = new FormData();
            formData.append('action', 'upload_featured_image');
            formData.append('featured_image', file);

            const response = await fetch('', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                this.updateFeaturedImagePreview(data.image_url);
                this.showMessage('Image uploaded successfully!', 'success');
            } else {
                throw new Error(data.message || 'Upload failed');
            }
        } catch (error) {
            this.showMessage('Error uploading image: ' + error.message, 'error');
        } finally {
            this.hideLoading();
            e.target.value = ''; // Reset input
        }
    }

    validateImageFile(file) {
        const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
        const maxSize = 5 * 1024 * 1024; // 5MB

        if (!allowedTypes.includes(file.type)) {
            this.showMessage('Please select a valid image file (JPG, PNG, or WebP).', 'error');
            return false;
        }

        if (file.size > maxSize) {
            this.showMessage('Image size must be less than 5MB.', 'error');
            return false;
        }

        return true;
    }

    updateFeaturedImagePreview(imageUrl) {
        const currentImage = document.getElementById('currentFeaturedImage');
        currentImage.innerHTML = `
            <img src="${imageUrl}" alt="Featured Image">
            <button type="button" class="remove-image-btn" id="removeFeaturedImage">
                <i class="fas fa-times"></i>
            </button>
        `;

        // Re-bind remove button event
        document.getElementById('removeFeaturedImage').addEventListener('click', () => this.removeFeaturedImage());
    }

    removeFeaturedImage() {
        const currentImage = document.getElementById('currentFeaturedImage');
        currentImage.innerHTML = `
            <div class="image-placeholder">
                <i class="fas fa-image"></i>
                <span>No image selected</span>
            </div>
        `;

        // You might want to send a request to remove the image from server as well
        this.showMessage('Featured image removed.', 'success');
    }

    async loadPopularTags() {
        try {
            const response = await fetch('?action=get_tags');
            const tags = await response.json();
            
            if (this.tagify && tags) {
                this.tagify.settings.whitelist = tags;
            }
        } catch (error) {
            console.error('Failed to load tags:', error);
        }
    }

    handleSearch(e) {
        const searchTerm = e.target.value.trim();
        // Implement search logic or debounced API call
        clearTimeout(this.searchTimeout);
        this.searchTimeout = setTimeout(() => {
            this.applyFilters();
        }, 500);
    }

    applyFilters() {
        const search = document.getElementById('postsSearch').value;
        const status = document.getElementById('statusFilter').value;
        const category = document.getElementById('categoryFilter').value;

        const params = new URLSearchParams();
        if (search) params.append('search', search);
        if (status) params.append('status', status);
        if (category) params.append('category', category);

        window.location.href = 'blog.php?' + params.toString();
    }

    clearFilters() {
        window.location.href = 'blog.php';
    }

    toggleSelectAll(e) {
        const isChecked = e.target.checked;
        document.querySelectorAll('.post-checkbox').forEach(checkbox => {
            checkbox.checked = isChecked;
        });
        this.updateBulkActions();
    }

    updateBulkActions() {
        const checkboxes = document.querySelectorAll('.post-checkbox:checked');
        const bulkBar = document.getElementById('bulkActionsBar');
        const selectedCount = document.getElementById('selectedCount');

        this.selectedPosts.clear();
        checkboxes.forEach(checkbox => {
            this.selectedPosts.add(checkbox.value);
        });

        if (this.selectedPosts.size > 0) {
            bulkBar.style.display = 'flex';
            selectedCount.textContent = this.selectedPosts.size;
        } else {
            bulkBar.style.display = 'none';
        }
    }

    async applyBulkAction() {
        const action = document.getElementById('bulkAction').value;
        if (!action) {
            this.showMessage('Please select an action.', 'error');
            return;
        }

        if (this.selectedPosts.size === 0) {
            this.showMessage('No posts selected.', 'error');
            return;
        }

        if (action === 'delete' && !confirm(`Are you sure you want to delete ${this.selectedPosts.size} post(s)? This action cannot be undone.`)) {
            return;
        }

        this.showLoading(`Applying ${action} to ${this.selectedPosts.size} post(s)...`);

        try {
            const formData = new FormData();
            formData.append('action', 'bulk_action');
            formData.append('bulk_action', action);
            Array.from(this.selectedPosts).forEach(postId => {
                formData.append('post_ids[]', postId);
            });

            const response = await fetch('', {
                method: 'POST',
                body: formData
            });

            if (response.ok) {
                this.showMessage(`Successfully ${action}ed ${this.selectedPosts.size} post(s).`, 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                throw new Error('Bulk action failed');
            }
        } catch (error) {
            this.showMessage('Error performing bulk action: ' + error.message, 'error');
        } finally {
            this.hideLoading();
        }
    }

    cancelBulkAction() {
        this.selectedPosts.clear();
        document.querySelectorAll('.post-checkbox').forEach(checkbox => {
            checkbox.checked = false;
        });
        document.getElementById('selectAllPosts').checked = false;
        this.updateBulkActions();
    }

    showDeleteModal(e) {
        const postId = e.currentTarget.getAttribute('data-post-id');
        const postTitle = e.currentTarget.getAttribute('data-post-title');

        document.getElementById('deletePostTitle').textContent = postTitle;
        document.getElementById('deletePostId').value = postId;

        this.showModal('deleteModal');
    }

    showImageUploadModal() {
        this.showModal('imageUploadModal');
    }

    showModal(modalId) {
        const modal = document.getElementById(modalId);
        modal.style.display = 'flex';
        setTimeout(() => modal.classList.add('active'), 10);
    }

    hideModal(modal) {
        modal.classList.remove('active');
        setTimeout(() => modal.style.display = 'none', 300);
    }

    handleDragOver(e) {
        e.preventDefault();
        e.currentTarget.classList.add('dragover');
    }

    handleDragLeave(e) {
        e.preventDefault();
        e.currentTarget.classList.remove('dragover');
    }

    handleDrop(e) {
        e.preventDefault();
        e.currentTarget.classList.remove('dragover');
        
        const files = e.dataTransfer.files;
        if (files.length > 0) {
            this.handleImageUpload({ target: { files: files } });
        }
    }

    async handleImageUpload(e) {
        const files = e.target.files;
        if (!files || files.length === 0) return;

        const uploadedImages = document.getElementById('uploadedImages');
        uploadedImages.innerHTML = '';

        for (let file of files) {
            if (!this.validateImageFile(file)) continue;

            const formData = new FormData();
            formData.append('action', 'upload_editor_image');
            formData.append('image', file);

            try {
                const response = await fetch('', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    this.addImageToGallery(data.image_url, uploadedImages);
                }
            } catch (error) {
                console.error('Upload failed:', error);
            }
        }
    }

    addImageToGallery(imageUrl, container) {
        const imageDiv = document.createElement('div');
        imageDiv.className = 'uploaded-image';
        imageDiv.innerHTML = `
            <img src="${imageUrl}" alt="Uploaded image">
            <button type="button" class="select-btn" data-url="${imageUrl}">
                Insert into Post
            </button>
        `;

        imageDiv.querySelector('.select-btn').addEventListener('click', (e) => {
            this.insertImageIntoEditor(e.target.getAttribute('data-url'));
            this.hideModal(document.getElementById('imageUploadModal'));
        });

        container.appendChild(imageDiv);
    }

    insertImageIntoEditor(imageUrl) {
        if (!this.editor) return;

        const range = this.editor.getSelection();
        this.editor.insertEmbed(range.index, 'image', imageUrl);
    }

    showError(elementId, message) {
        const errorElement = document.getElementById(elementId);
        errorElement.textContent = message;
        errorElement.classList.add('show');
    }

    hideError(elementId) {
        const errorElement = document.getElementById(elementId);
        errorElement.classList.remove('show');
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

// Initialize blog manager when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    window.blogManager = new BlogManager();
});