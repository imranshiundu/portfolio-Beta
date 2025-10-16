// admin.js - Comprehensive Admin Dashboard Functionality
class AdminDashboard {
    constructor() {
        this.currentTheme = localStorage.getItem('admin-theme') || 'light';
        this.sidebarState = localStorage.getItem('sidebar-state') || 'open';
        this.apiBaseUrl = window.location.origin + '/admin';
        this.isMobile = window.innerWidth < 768;
        this.currentPage = this.getCurrentPage();
        
        this.init();
    }

    // Initialize the admin dashboard
    init() {
        this.setupEventListeners();
        this.applyTheme(this.currentTheme);
        this.applySidebarState();
        this.initializeComponents();
        this.setupGlobalHandlers();
        this.initializePageSpecificFeatures();
        
        console.log(`Admin Dashboard Initialized - Page: ${this.currentPage}`);
    }

    // ===== CORE FUNCTIONALITY =====
    setupEventListeners() {
        // Sidebar toggle
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebar = document.querySelector('.admin-sidebar');
        
        if (sidebarToggle && sidebar) {
            sidebarToggle.addEventListener('click', () => this.toggleSidebar());
        }

        // Theme toggle
        const themeToggle = document.getElementById('themeToggle');
        if (themeToggle) {
            themeToggle.addEventListener('click', () => this.toggleTheme());
        }

        // Fullscreen toggle
        const fullscreenBtn = document.getElementById('fullscreenBtn');
        if (fullscreenBtn) {
            fullscreenBtn.addEventListener('click', () => this.toggleFullscreen());
        }

        // Notifications panel
        const notificationsBtn = document.getElementById('notificationsBtn');
        const notificationsPanel = document.getElementById('notificationsPanel');
        const closeNotifications = document.getElementById('closeNotifications');
        
        if (notificationsBtn && notificationsPanel) {
            notificationsBtn.addEventListener('click', () => this.toggleNotifications());
        }
        
        if (closeNotifications && notificationsPanel) {
            closeNotifications.addEventListener('click', () => this.closeNotifications());
        }

        // Global search
        const globalSearch = document.getElementById('globalSearch');
        if (globalSearch) {
            globalSearch.addEventListener('input', this.debounce((e) => {
                this.handleGlobalSearch(e.target.value);
            }, 300));
        }

        // Window resize handling
        window.addEventListener('resize', this.debounce(() => {
            this.handleResize();
        }, 250));

        // Click outside to close panels
        document.addEventListener('click', (e) => {
            this.handleOutsideClick(e);
        });

        // Keyboard shortcuts
        document.addEventListener('keydown', (e) => {
            this.handleKeyboardShortcuts(e);
        });
    }

    // ===== SIDEBAR MANAGEMENT =====
    toggleSidebar() {
        const sidebar = document.querySelector('.admin-sidebar');
        const main = document.querySelector('.admin-main');
        
        if (sidebar) {
            sidebar.classList.toggle('collapsed');
            this.sidebarState = sidebar.classList.contains('collapsed') ? 'collapsed' : 'open';
            localStorage.setItem('sidebar-state', this.sidebarState);
            
            // Update main content margin
            if (main) {
                main.style.marginLeft = this.sidebarState === 'collapsed' ? '60px' : '280px';
            }
        }
    }

    applySidebarState() {
        const sidebar = document.querySelector('.admin-sidebar');
        const main = document.querySelector('.admin-main');
        
        if (sidebar && this.sidebarState === 'collapsed') {
            sidebar.classList.add('collapsed');
            if (main) {
                main.style.marginLeft = '60px';
            }
        }
        
        // Auto-collapse on mobile
        if (this.isMobile) {
            this.toggleSidebar();
        }
    }

    // ===== THEME MANAGEMENT =====
    toggleTheme() {
        this.currentTheme = this.currentTheme === 'light' ? 'dark' : 'light';
        this.applyTheme(this.currentTheme);
        localStorage.setItem('admin-theme', this.currentTheme);
    }

    applyTheme(theme) {
        document.body.classList.toggle('dark-mode', theme === 'dark');
        
        // Update theme toggle icon
        const themeToggle = document.getElementById('themeToggle');
        if (themeToggle) {
            const icon = themeToggle.querySelector('i');
            if (icon) {
                icon.className = theme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
            }
        }
        
        // Update charts if they exist
        this.updateChartsTheme();
    }

    updateChartsTheme() {
        // This would update chart colors based on theme
        // Implementation depends on your chart library
    }

    // ===== NOTIFICATIONS =====
    toggleNotifications() {
        const panel = document.getElementById('notificationsPanel');
        if (panel) {
            panel.style.display = panel.style.display === 'block' ? 'none' : 'block';
        }
    }

    closeNotifications() {
        const panel = document.getElementById('notificationsPanel');
        if (panel) {
            panel.style.display = 'none';
        }
    }

    // ===== FULLSCREEN =====
    toggleFullscreen() {
        if (!document.fullscreenElement) {
            document.documentElement.requestFullscreen().catch(err => {
                console.log(`Error attempting to enable fullscreen: ${err.message}`);
            });
        } else {
            if (document.exitFullscreen) {
                document.exitFullscreen();
            }
        }
    }

    // ===== GLOBAL SEARCH =====
    handleGlobalSearch(query) {
        if (query.length < 2) return;
        
        // Implement global search functionality
        console.log('Searching for:', query);
        // This would typically make an API call to search across all content
    }

    // ===== COMPONENT INITIALIZATION =====
    initializeComponents() {
        this.initializeModals();
        this.initializeAlerts();
        this.initializeForms();
        this.initializeTables();
        this.initializeCards();
    }

    initializeModals() {
        // Close modals when clicking close button or outside
        document.querySelectorAll('.modal-close').forEach(closeBtn => {
            closeBtn.addEventListener('click', function() {
                this.closest('.modal').style.display = 'none';
            });
        });

        // Close modal when clicking outside
        document.querySelectorAll('.modal').forEach(modal => {
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    modal.style.display = 'none';
                }
            });
        });
    }

    initializeAlerts() {
        // Auto-hide success alerts after 5 seconds
        document.querySelectorAll('.alert-success').forEach(alert => {
            setTimeout(() => {
                if (alert.parentElement) {
                    alert.style.opacity = '0';
                    setTimeout(() => alert.remove(), 300);
                }
            }, 5000);
        });

        // Alert close buttons
        document.querySelectorAll('.alert-close').forEach(closeBtn => {
            closeBtn.addEventListener('click', function() {
                this.closest('.alert').style.display = 'none';
            });
        });
    }

    initializeForms() {
        // Form validation
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', (e) => {
                if (!this.validateForm(form)) {
                    e.preventDefault();
                }
            });
        });

        // Character counters
        document.querySelectorAll('[maxlength]').forEach(input => {
            this.setupCharacterCounter(input);
        });
    }

    initializeTables() {
        // Sortable tables
        document.querySelectorAll('.data-table th[data-sort]').forEach(header => {
            header.style.cursor = 'pointer';
            header.addEventListener('click', () => {
                this.sortTable(header);
            });
        });

        // Row selection
        document.querySelectorAll('.data-table tbody tr').forEach(row => {
            row.addEventListener('click', (e) => {
                if (!e.target.matches('input[type="checkbox"]')) {
                    const checkbox = row.querySelector('input[type="checkbox"]');
                    if (checkbox) {
                        checkbox.checked = !checkbox.checked;
                        this.updateBulkActions();
                    }
                }
            });
        });
    }

    initializeCards() {
        // Card interactions
        document.querySelectorAll('.card-header').forEach(header => {
            const card = header.closest('.content-card');
            const toggleBtn = header.querySelector('.card-toggle');
            
            if (toggleBtn && card) {
                toggleBtn.addEventListener('click', () => {
                    const body = card.querySelector('.card-body');
                    if (body) {
                        body.style.display = body.style.display === 'none' ? 'block' : 'none';
                        toggleBtn.querySelector('i').className = 
                            body.style.display === 'none' ? 'fas fa-chevron-down' : 'fas fa-chevron-up';
                    }
                });
            }
        });
    }

    // ===== FORM VALIDATION =====
    validateForm(form) {
        let isValid = true;
        const requiredFields = form.querySelectorAll('[required]');
        
        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                this.showFieldError(field, 'This field is required');
                isValid = false;
            } else {
                this.clearFieldError(field);
            }
            
            // Email validation
            if (field.type === 'email' && field.value) {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(field.value)) {
                    this.showFieldError(field, 'Please enter a valid email address');
                    isValid = false;
                }
            }
            
            // URL validation
            if (field.type === 'url' && field.value) {
                try {
                    new URL(field.value);
                } catch (e) {
                    this.showFieldError(field, 'Please enter a valid URL');
                    isValid = false;
                }
            }
        });
        
        return isValid;
    }

    showFieldError(field, message) {
        this.clearFieldError(field);
        field.classList.add('error');
        
        const errorDiv = document.createElement('div');
        errorDiv.className = 'field-error';
        errorDiv.textContent = message;
        field.parentNode.appendChild(errorDiv);
    }

    clearFieldError(field) {
        field.classList.remove('error');
        const existingError = field.parentNode.querySelector('.field-error');
        if (existingError) {
            existingError.remove();
        }
    }

    setupCharacterCounter(input) {
        const maxLength = parseInt(input.getAttribute('maxlength'));
        const counter = document.createElement('div');
        counter.className = 'character-counter';
        counter.style.fontSize = '0.8rem';
        counter.style.color = '#666';
        counter.style.marginTop = '0.25rem';
        
        input.parentNode.appendChild(counter);
        
        const updateCounter = () => {
            const currentLength = input.value.length;
            counter.textContent = `${currentLength}/${maxLength}`;
            counter.style.color = currentLength > maxLength ? '#e53e3e' : '#666';
        };
        
        input.addEventListener('input', updateCounter);
        updateCounter();
    }

    // ===== TABLE OPERATIONS =====
    sortTable(header) {
        const table = header.closest('table');
        const tbody = table.querySelector('tbody');
        const rows = Array.from(tbody.querySelectorAll('tr'));
        const columnIndex = Array.from(header.parentNode.children).indexOf(header);
        const isAscending = !header.classList.contains('asc');
        
        // Clear other sort indicators
        table.querySelectorAll('th').forEach(th => {
            th.classList.remove('asc', 'desc');
        });
        
        // Set current sort indicator
        header.classList.add(isAscending ? 'asc' : 'desc');
        
        // Sort rows
        rows.sort((a, b) => {
            const aValue = a.children[columnIndex].textContent.trim();
            const bValue = b.children[columnIndex].textContent.trim();
            
            if (isAscending) {
                return aValue.localeCompare(bValue, undefined, { numeric: true });
            } else {
                return bValue.localeCompare(aValue, undefined, { numeric: true });
            }
        });
        
        // Re-append sorted rows
        rows.forEach(row => tbody.appendChild(row));
    }

    // ===== BULK ACTIONS =====
    updateBulkActions() {
        const checkboxes = document.querySelectorAll('input[type="checkbox"]:checked');
        const bulkActionsBar = document.getElementById('bulkActionsBar');
        const selectedCount = document.getElementById('selectedCount');
        
        if (bulkActionsBar && selectedCount) {
            if (checkboxes.length > 0) {
                bulkActionsBar.style.display = 'flex';
                selectedCount.textContent = checkboxes.length;
            } else {
                bulkActionsBar.style.display = 'none';
            }
        }
    }

    // ===== PAGE-SPECIFIC FEATURES =====
    initializePageSpecificFeatures() {
        switch (this.currentPage) {
            case 'dashboard':
                this.initializeDashboard();
                break;
            case 'projects':
                this.initializeProjects();
                break;
            case 'blog':
                this.initializeBlog();
                break;
            case 'settings':
                this.initializeSettings();
                break;
            case 'messages':
                this.initializeMessages();
                break;
            case 'analytics':
                this.initializeAnalytics();
                break;
        }
    }

    // ===== DASHBOARD PAGE =====
    initializeDashboard() {
        // Stats counter animation
        this.animateCounters();
        
        // Chart initialization
        this.initializeDashboardCharts();
        
        // Message handling
        this.initializeMessageHandlers();
        
        // System status updates
        this.initializeSystemStatus();
    }

    animateCounters() {
        const counters = document.querySelectorAll('.stat-number');
        
        counters.forEach(counter => {
            const target = parseInt(counter.textContent);
            const duration = 2000;
            const step = target / (duration / 16);
            let current = 0;
            
            const timer = setInterval(() => {
                current += step;
                if (current >= target) {
                    counter.textContent = target;
                    clearInterval(timer);
                } else {
                    counter.textContent = Math.floor(current);
                }
            }, 16);
        });
    }

    initializeDashboardCharts() {
        // Traffic Chart
        const trafficCtx = document.getElementById('trafficChart');
        if (trafficCtx) {
            const trafficChart = new Chart(trafficCtx, {
                type: 'line',
                data: {
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                    datasets: [{
                        label: 'Visitors',
                        data: [1200, 1900, 1500, 2200, 1800, 2500],
                        borderColor: '#0D47A1',
                        backgroundColor: 'rgba(13, 71, 161, 0.1)',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    plugins: { legend: { display: false } },
                    scales: { y: { beginAtZero: true } }
                }
            });
        }

        // Categories Chart
        const categoriesCtx = document.getElementById('categoriesChart');
        if (categoriesCtx) {
            const categoriesChart = new Chart(categoriesCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Frontend', 'Backend', 'Full Stack', 'Mobile'],
                    datasets: [{
                        data: [8, 5, 12, 3],
                        backgroundColor: ['#0D47A1', '#FF6D00', '#2E7D32', '#6A1B9A']
                    }]
                },
                options: {
                    responsive: true,
                    plugins: { legend: { position: 'bottom' } }
                }
            });
        }
    }

    initializeMessageHandlers() {
        // View message modal
        document.querySelectorAll('.view-message').forEach(btn => {
            btn.addEventListener('click', () => {
                const messageId = btn.getAttribute('data-message-id');
                this.viewMessage(messageId);
            });
        });

        // Mark as read
        document.querySelectorAll('.mark-read').forEach(btn => {
            btn.addEventListener('click', () => {
                const messageId = btn.getAttribute('data-message-id');
                this.markMessageAsRead(messageId);
            });
        });

        // Delete message
        document.querySelectorAll('.delete-message').forEach(btn => {
            btn.addEventListener('click', () => {
                const messageId = btn.getAttribute('data-message-id');
                this.deleteMessage(messageId);
            });
        });

        // Select all messages
        const selectAll = document.getElementById('selectAllMessages');
        if (selectAll) {
            selectAll.addEventListener('change', (e) => {
                const checkboxes = document.querySelectorAll('input[name="message_ids[]"]');
                checkboxes.forEach(checkbox => {
                    checkbox.checked = e.target.checked;
                });
                this.updateBulkActions();
            });
        }
    }

    async viewMessage(messageId) {
        try {
            // Simulate API call
            const response = await fetch(`${this.apiBaseUrl}/api/messages/${messageId}`);
            const message = await response.json();
            
            const modalBody = document.getElementById('messageModalBody');
            if (modalBody) {
                modalBody.innerHTML = `
                    <div class="message-detail">
                        <div class="message-header">
                            <h4>${message.name}</h4>
                            <p class="message-email">${message.email}</p>
                            <p class="message-date">${new Date(message.created_at).toLocaleDateString()}</p>
                        </div>
                        <div class="message-content">
                            <p>${message.message}</p>
                        </div>
                        <div class="message-meta">
                            <span class="project-type">Project Type: ${message.project_type}</span>
                            <span class="message-status status-${message.status}">${message.status}</span>
                        </div>
                    </div>
                `;
            }
            
            document.getElementById('messageModal').style.display = 'block';
        } catch (error) {
            console.error('Error fetching message:', error);
            this.showNotification('Error loading message', 'error');
        }
    }

    async markMessageAsRead(messageId) {
        try {
            const response = await fetch(`${this.apiBaseUrl}/api/messages/${messageId}/read`, {
                method: 'PUT'
            });
            
            if (response.ok) {
                this.showNotification('Message marked as read', 'success');
                // Update UI
                const row = document.querySelector(`[data-message-id="${messageId}"]`).closest('tr');
                row.classList.remove('unread');
                row.querySelector('.status-badge').textContent = 'Read';
                row.querySelector('.status-badge').className = 'status-badge status-read';
            }
        } catch (error) {
            console.error('Error marking message as read:', error);
            this.showNotification('Error updating message', 'error');
        }
    }

    async deleteMessage(messageId) {
        if (confirm('Are you sure you want to delete this message?')) {
            try {
                const response = await fetch(`${this.apiBaseUrl}/api/messages/${messageId}`, {
                    method: 'DELETE'
                });
                
                if (response.ok) {
                    this.showNotification('Message deleted', 'success');
                    document.querySelector(`[data-message-id="${messageId}"]`).closest('tr').remove();
                }
            } catch (error) {
                console.error('Error deleting message:', error);
                this.showNotification('Error deleting message', 'error');
            }
        }
    }

    initializeSystemStatus() {
        // Simulate real-time status updates
        setInterval(() => {
            this.updateSystemStatus();
        }, 30000);
    }

    updateSystemStatus() {
        // This would typically make an API call to get system status
        console.log('Updating system status...');
    }

    // ===== PROJECTS PAGE =====
    initializeProjects() {
        // Technology tag input
        this.initializeTagInputs();
        
        // Image upload handling
        this.initializeImageUploads();
        
        // Feature management
        this.initializeFeatureManager();
        
        // Challenge management
        this.initializeChallengeManager();
        
        // Project reordering
        this.initializeProjectReordering();
        
        // Bulk actions
        this.initializeProjectBulkActions();
    }

    initializeTagInputs() {
        const techInput = document.getElementById('project_technologies');
        if (techInput) {
            const tagify = new Tagify(techInput, {
                whitelist: ['JavaScript', 'PHP', 'Laravel', 'Java', 'Python', 'React', 'Vue', 'Angular', 'Node.js'],
                maxTags: 10,
                dropdown: {
                    maxItems: 20,
                    classname: "tags-look",
                    enabled: 0,
                    closeOnSelect: false
                }
            });
        }
    }

    initializeImageUploads() {
        // Featured image upload
        const featuredImageInput = document.getElementById('featured_image');
        const featuredImagePreview = document.getElementById('currentFeaturedImage');
        
        if (featuredImageInput && featuredImagePreview) {
            featuredImageInput.addEventListener('change', (e) => {
                const file = e.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = (e) => {
                        featuredImagePreview.innerHTML = `
                            <img src="${e.target.result}" alt="Featured Image">
                            <button type="button" class="remove-image-btn" id="removeFeaturedImage">
                                <i class="fas fa-times"></i>
                            </button>
                        `;
                    };
                    reader.readAsDataURL(file);
                }
            });
        }

        // Gallery image upload
        const galleryInput = document.getElementById('gallery_images');
        const galleryPreview = document.getElementById('galleryPreview');
        
        if (galleryInput && galleryPreview) {
            galleryInput.addEventListener('change', (e) => {
                Array.from(e.target.files).forEach(file => {
                    if (file.type.startsWith('image/')) {
                        const reader = new FileReader();
                        reader.onload = (e) => {
                            const galleryItem = document.createElement('div');
                            galleryItem.className = 'gallery-item';
                            galleryItem.innerHTML = `
                                <img src="${e.target.result}" alt="Gallery image">
                                <button type="button" class="remove-gallery-image">
                                    <i class="fas fa-times"></i>
                                </button>
                                <input type="hidden" name="project_gallery[]" value="${e.target.result}">
                            `;
                            galleryPreview.appendChild(galleryItem);
                        };
                        reader.readAsDataURL(file);
                    }
                });
            });
        }
    }

    initializeFeatureManager() {
        const container = document.getElementById('featuresContainer');
        const addBtn = document.getElementById('addFeatureBtn');
        
        if (container && addBtn) {
            addBtn.addEventListener('click', () => {
                const index = container.children.length;
                const featureItem = document.createElement('div');
                featureItem.className = 'feature-item';
                featureItem.setAttribute('data-index', index);
                featureItem.innerHTML = `
                    <input type="text" 
                           name="project_features[]" 
                           class="form-input" 
                           placeholder="Describe a key feature...">
                    <button type="button" class="btn btn-icon btn-danger remove-feature">
                        <i class="fas fa-times"></i>
                    </button>
                `;
                container.appendChild(featureItem);
            });

            // Delegate remove feature events
            container.addEventListener('click', (e) => {
                if (e.target.closest('.remove-feature')) {
                    e.target.closest('.feature-item').remove();
                    this.renumberFeatures();
                }
            });
        }
    }

    renumberFeatures() {
        const container = document.getElementById('featuresContainer');
        if (container) {
            Array.from(container.children).forEach((item, index) => {
                item.setAttribute('data-index', index);
            });
        }
    }

    initializeChallengeManager() {
        const container = document.getElementById('challengesContainer');
        const addBtn = document.getElementById('addChallengeBtn');
        
        if (container && addBtn) {
            addBtn.addEventListener('click', () => {
                const index = container.children.length;
                const challengeItem = document.createElement('div');
                challengeItem.className = 'challenge-item';
                challengeItem.setAttribute('data-index', index);
                challengeItem.innerHTML = `
                    <div class="challenge-row">
                        <div class="form-group">
                            <label class="form-label">Challenge</label>
                            <input type="text" 
                                   name="challenge_problem[]" 
                                   class="form-input" 
                                   placeholder="Describe the challenge...">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Solution</label>
                            <input type="text" 
                                   name="challenge_solution[]" 
                                   class="form-input" 
                                   placeholder="Describe your solution...">
                        </div>
                    </div>
                    <button type="button" class="btn btn-icon btn-danger remove-challenge">
                        <i class="fas fa-times"></i>
                    </button>
                `;
                container.appendChild(challengeItem);
            });

            container.addEventListener('click', (e) => {
                if (e.target.closest('.remove-challenge')) {
                    e.target.closest('.challenge-item').remove();
                    this.renumberChallenges();
                }
            });
        }
    }

    renumberChallenges() {
        const container = document.getElementById('challengesContainer');
        if (container) {
            Array.from(container.children).forEach((item, index) => {
                item.setAttribute('data-index', index);
            });
        }
    }

    initializeProjectReordering() {
        const reorderBtn = document.getElementById('reorderProjectsBtn');
        const reorderModal = document.getElementById('reorderModal');
        
        if (reorderBtn && reorderModal) {
            reorderBtn.addEventListener('click', () => {
                reorderModal.style.display = 'block';
                this.initializeSortableList();
            });
        }

        // Save reorder
        const saveReorderBtn = document.getElementById('saveReorder');
        if (saveReorderBtn) {
            saveReorderBtn.addEventListener('click', () => {
                this.saveProjectOrder();
            });
        }
    }

    initializeSortableList() {
        const reorderList = document.getElementById('reorderList');
        if (reorderList) {
            new Sortable(reorderList, {
                handle: '.reorder-handle',
                animation: 150,
                onUpdate: () => {
                    this.updateReorderPositions();
                }
            });
        }
    }

    updateReorderPositions() {
        const items = document.querySelectorAll('.reorder-item');
        items.forEach((item, index) => {
            const positionElement = item.querySelector('.position-number');
            if (positionElement) {
                positionElement.textContent = index + 1;
            }
        });
    }

    async saveProjectOrder() {
        const items = document.querySelectorAll('.reorder-item');
        const order = Array.from(items).map(item => ({
            id: item.getAttribute('data-project-id'),
            position: Array.from(items).indexOf(item) + 1
        }));

        try {
            const response = await fetch(`${this.apiBaseUrl}/api/projects/reorder`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ project_order: order })
            });

            if (response.ok) {
                this.showNotification('Projects reordered successfully', 'success');
                document.getElementById('reorderModal').style.display = 'none';
                // Refresh the page or update UI as needed
                location.reload();
            }
        } catch (error) {
            console.error('Error reordering projects:', error);
            this.showNotification('Error reordering projects', 'error');
        }
    }

    initializeProjectBulkActions() {
        const bulkAction = document.getElementById('bulkAction');
        const applyBulkAction = document.getElementById('applyBulkAction');
        
        if (bulkAction && applyBulkAction) {
            applyBulkAction.addEventListener('click', () => {
                const selectedProjects = Array.from(document.querySelectorAll('.project-checkbox:checked'))
                    .map(checkbox => checkbox.value);
                
                if (selectedProjects.length === 0) {
                    this.showNotification('Please select at least one project', 'warning');
                    return;
                }

                const action = bulkAction.value;
                if (!action) {
                    this.showNotification('Please select an action', 'warning');
                    return;
                }

                this.executeProjectBulkAction(action, selectedProjects);
            });
        }
    }

    async executeProjectBulkAction(action, projectIds) {
        try {
            const response = await fetch(`${this.apiBaseUrl}/api/projects/bulk-action`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: action,
                    project_ids: projectIds
                })
            });

            if (response.ok) {
                this.showNotification(`Bulk action completed successfully`, 'success');
                document.getElementById('bulkActionsBar').style.display = 'none';
                // Refresh or update UI
                location.reload();
            }
        } catch (error) {
            console.error('Error executing bulk action:', error);
            this.showNotification('Error executing bulk action', 'error');
        }
    }

    // ===== BLOG PAGE =====
    initializeBlog() {
        this.initializeRichTextEditor();
        this.initializeBlogTagInput();
        this.initializeFeaturedImageUpload();
        this.initializeBlogBulkActions();
        this.initializeBlogFilters();
    }

    initializeRichTextEditor() {
        const editorElement = document.getElementById('post_editor');
        const contentInput = document.getElementById('post_content');
        
        if (editorElement && contentInput) {
            const quill = new Quill(editorElement, {
                theme: 'snow',
                modules: {
                    toolbar: [
                        [{ 'header': [1, 2, 3, false] }],
                        ['bold', 'italic', 'underline', 'strike'],
                        [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                        [{ 'indent': '-1'}, { 'indent': '+1' }],
                        ['link', 'image', 'code-block'],
                        ['clean']
                    ]
                },
                placeholder: 'Write your blog post content here...'
            });

            // Set initial content
            quill.root.innerHTML = contentInput.value;

            // Update hidden input on content change
            quill.on('text-change', () => {
                contentInput.value = quill.root.innerHTML;
            });
        }
    }

    initializeBlogTagInput() {
        const tagInput = document.getElementById('post_tags');
        if (tagInput) {
            new Tagify(tagInput, {
                whitelist: ['JavaScript', 'PHP', 'Web Development', 'Tutorial', 'Tips', 'Best Practices'],
                maxTags: 15,
                dropdown: {
                    maxItems: 20,
                    classname: "tags-look",
                    enabled: 0,
                    closeOnSelect: false
                }
            });
        }
    }

    initializeFeaturedImageUpload() {
        const imageInput = document.getElementById('featured_image');
        const imagePreview = document.getElementById('currentFeaturedImage');
        
        if (imageInput && imagePreview) {
            imageInput.addEventListener('change', (e) => {
                const file = e.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = (e) => {
                        imagePreview.innerHTML = `
                            <img src="${e.target.result}" alt="Featured Image">
                            <button type="button" class="remove-image-btn" id="removeFeaturedImage">
                                <i class="fas fa-times"></i>
                            </button>
                        `;
                    };
                    reader.readAsDataURL(file);
                }
            });
        }
    }

    initializeBlogBulkActions() {
        const bulkAction = document.getElementById('bulkAction');
        const applyBulkAction = document.getElementById('applyBulkAction');
        
        if (bulkAction && applyBulkAction) {
            applyBulkAction.addEventListener('click', () => {
                const selectedPosts = Array.from(document.querySelectorAll('.post-checkbox:checked'))
                    .map(checkbox => checkbox.value);
                
                if (selectedPosts.length === 0) {
                    this.showNotification('Please select at least one post', 'warning');
                    return;
                }

                const action = bulkAction.value;
                if (!action) {
                    this.showNotification('Please select an action', 'warning');
                    return;
                }

                this.executeBlogBulkAction(action, selectedPosts);
            });
        }
    }

    async executeBlogBulkAction(action, postIds) {
        try {
            const response = await fetch(`${this.apiBaseUrl}/api/blog/bulk-action`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: action,
                    post_ids: postIds
                })
            });

            if (response.ok) {
                this.showNotification(`Bulk action completed successfully`, 'success');
                document.getElementById('bulkActionsBar').style.display = 'none';
                location.reload();
            }
        } catch (error) {
            console.error('Error executing bulk action:', error);
            this.showNotification('Error executing bulk action', 'error');
        }
    }

    initializeBlogFilters() {
        const searchInput = document.getElementById('postsSearch');
        const statusFilter = document.getElementById('statusFilter');
        const categoryFilter = document.getElementById('categoryFilter');
        
        if (searchInput) {
            searchInput.addEventListener('input', this.debounce(() => {
                this.applyBlogFilters();
            }, 300));
        }
        
        if (statusFilter) {
            statusFilter.addEventListener('change', () => {
                this.applyBlogFilters();
            });
        }
        
        if (categoryFilter) {
            categoryFilter.addEventListener('change', () => {
                this.applyBlogFilters();
            });
        }
    }

    applyBlogFilters() {
        const search = document.getElementById('postsSearch').value.toLowerCase();
        const status = document.getElementById('statusFilter').value;
        const category = document.getElementById('categoryFilter').value;
        
        const rows = document.querySelectorAll('.post-row');
        
        rows.forEach(row => {
            const title = row.querySelector('.post-title').textContent.toLowerCase();
            const rowStatus = row.querySelector('.status-badge').textContent.toLowerCase();
            const rowCategories = row.querySelector('.post-categories').textContent.toLowerCase();
            
            const matchesSearch = title.includes(search);
            const matchesStatus = !status || rowStatus === status;
            const matchesCategory = !category || rowCategories.includes(category);
            
            row.style.display = (matchesSearch && matchesStatus && matchesCategory) ? '' : 'none';
        });
    }

    // ===== SETTINGS PAGE =====
    initializeSettings() {
        this.initializeSettingsTabs();
        this.initializeSettingsForms();
        this.initializeColorPickers();
        this.initializeImageUploadsSettings();
        this.initializeBackupRestore();
    }

    initializeSettingsTabs() {
        const navItems = document.querySelectorAll('.settings-nav .nav-item');
        const tabs = document.querySelectorAll('.settings-tab');
        
        navItems.forEach(item => {
            item.addEventListener('click', (e) => {
                e.preventDefault();
                
                // Remove active class from all items and tabs
                navItems.forEach(navItem => navItem.classList.remove('active'));
                tabs.forEach(tab => tab.classList.remove('active'));
                
                // Add active class to clicked item and corresponding tab
                item.classList.add('active');
                const tabId = item.getAttribute('data-tab') + 'Tab';
                document.getElementById(tabId).classList.add('active');
            });
        });
    }

    initializeSettingsForms() {
        // Auto-save functionality
        document.querySelectorAll('.settings-form').forEach(form => {
            form.addEventListener('change', this.debounce(() => {
                this.autoSaveSettings(form);
            }, 1000));
        });

        // Save all settings button
        const saveAllBtn = document.getElementById('saveAllSettings');
        if (saveAllBtn) {
            saveAllBtn.addEventListener('click', () => {
                this.saveAllSettings();
            });
        }
    }

    async autoSaveSettings(form) {
        const formData = new FormData(form);
        const action = formData.get('action');
        
        try {
            const response = await fetch(`${this.apiBaseUrl}/api/settings/${action}`, {
                method: 'POST',
                body: formData
            });
            
            if (response.ok) {
                this.showNotification('Settings saved automatically', 'success');
            }
        } catch (error) {
            console.error('Error auto-saving settings:', error);
        }
    }

    async saveAllSettings() {
        const forms = document.querySelectorAll('.settings-form');
        let savedCount = 0;
        
        for (const form of forms) {
            const formData = new FormData(form);
            
            try {
                const response = await fetch(`${this.apiBaseUrl}/api/settings/save-all`, {
                    method: 'POST',
                    body: formData
                });
                
                if (response.ok) {
                    savedCount++;
                }
            } catch (error) {
                console.error('Error saving settings:', error);
            }
        }
        
        if (savedCount === forms.length) {
            this.showNotification('All settings saved successfully', 'success');
        } else {
            this.showNotification('Some settings could not be saved', 'warning');
        }
    }

    initializeColorPickers() {
        document.querySelectorAll('input[type="color"]').forEach(picker => {
            const hexInput = picker.parentNode.querySelector('.color-hex-input');
            
            picker.addEventListener('input', (e) => {
                hexInput.value = e.target.value;
            });
            
            hexInput.addEventListener('change', (e) => {
                if (/^#[0-9A-F]{6}$/i.test(e.target.value)) {
                    picker.value = e.target.value;
                }
            });
        });
    }

    initializeImageUploadsSettings() {
        // Profile image upload
        const avatarUpload = document.getElementById('avatarUpload');
        const currentAvatar = document.getElementById('currentAvatar');
        
        if (avatarUpload && currentAvatar) {
            avatarUpload.addEventListener('change', (e) => {
                const file = e.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = (e) => {
                        currentAvatar.src = e.target.result;
                    };
                    reader.readAsDataURL(file);
                }
            });
        }

        // OG image upload
        const ogImageUpload = document.getElementById('ogImageUpload');
        const currentOgImage = document.getElementById('currentOgImage');
        
        if (ogImageUpload && currentOgImage) {
            ogImageUpload.addEventListener('change', (e) => {
                const file = e.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = (e) => {
                        currentOgImage.src = e.target.result;
                    };
                    reader.readAsDataURL(file);
                }
            });
        }
    }

    initializeBackupRestore() {
        const backupFileInput = document.getElementById('backupFile');
        const restoreBackupBtn = document.getElementById('restoreBackupBtn');
        
        if (backupFileInput && restoreBackupBtn) {
            backupFileInput.addEventListener('change', (e) => {
                restoreBackupBtn.disabled = !e.target.files.length;
            });
            
            restoreBackupBtn.addEventListener('click', () => {
                this.restoreBackup();
            });
        }

        const fullBackupBtn = document.getElementById('fullBackupBtn');
        if (fullBackupBtn) {
            fullBackupBtn.addEventListener('click', () => {
                this.createFullBackup();
            });
        }

        const optimizeDbBtn = document.getElementById('optimizeDbBtn');
        if (optimizeDbBtn) {
            optimizeDbBtn.addEventListener('click', () => {
                this.optimizeDatabase();
            });
        }
    }

    async restoreBackup() {
        const fileInput = document.getElementById('backupFile');
        if (!fileInput.files.length) return;
        
        const formData = new FormData();
        formData.append('backup_file', fileInput.files[0]);
        
        try {
            const response = await fetch(`${this.apiBaseUrl}/api/backup/restore`, {
                method: 'POST',
                body: formData
            });
            
            if (response.ok) {
                this.showNotification('Backup restored successfully', 'success');
            }
        } catch (error) {
            console.error('Error restoring backup:', error);
            this.showNotification('Error restoring backup', 'error');
        }
    }

    async createFullBackup() {
        try {
            const response = await fetch(`${this.apiBaseUrl}/api/backup/full`, {
                method: 'POST'
            });
            
            if (response.ok) {
                const blob = await response.blob();
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `full-backup-${new Date().toISOString().split('T')[0]}.zip`;
                document.body.appendChild(a);
                a.click();
                window.URL.revokeObjectURL(url);
                document.body.removeChild(a);
                
                this.showNotification('Full backup created successfully', 'success');
            }
        } catch (error) {
            console.error('Error creating full backup:', error);
            this.showNotification('Error creating full backup', 'error');
        }
    }

    async optimizeDatabase() {
        try {
            const response = await fetch(`${this.apiBaseUrl}/api/database/optimize`, {
                method: 'POST'
            });
            
            if (response.ok) {
                this.showNotification('Database optimized successfully', 'success');
            }
        } catch (error) {
            console.error('Error optimizing database:', error);
            this.showNotification('Error optimizing database', 'error');
        }
    }

    // ===== MESSAGES PAGE =====
    initializeMessages() {
        this.initializeMessageFilters();
        this.initializeMessageBulkActions();
        this.initializeMessageStatusUpdates();
    }

    initializeMessageFilters() {
        // Similar to blog filters implementation
    }

    initializeMessageBulkActions() {
        // Similar to project bulk actions implementation
    }

    initializeMessageStatusUpdates() {
        // Real-time status updates for messages
    }

    // ===== ANALYTICS PAGE =====
    initializeAnalytics() {
        this.initializeAnalyticsCharts();
        this.initializeDateRangePicker();
        this.initializeAnalyticsFilters();
    }

    initializeAnalyticsCharts() {
        // Advanced analytics charts implementation
    }

    initializeDateRangePicker() {
        // Date range picker for analytics
    }

    initializeAnalyticsFilters() {
        // Analytics filter controls
    }

    // ===== UTILITY METHODS =====
    getCurrentPage() {
        const path = window.location.pathname;
        if (path.includes('dashboard')) return 'dashboard';
        if (path.includes('projects')) return 'projects';
        if (path.includes('blog')) return 'blog';
        if (path.includes('settings')) return 'settings';
        if (path.includes('messages')) return 'messages';
        if (path.includes('analytics')) return 'analytics';
        return 'dashboard';
    }

    handleResize() {
        this.isMobile = window.innerWidth < 768;
        
        // Auto-collapse sidebar on mobile
        const sidebar = document.querySelector('.admin-sidebar');
        if (this.isMobile && sidebar && !sidebar.classList.contains('collapsed')) {
            this.toggleSidebar();
        }
    }

    handleOutsideClick(e) {
        // Close notifications panel when clicking outside
        const notificationsPanel = document.getElementById('notificationsPanel');
        const notificationsBtn = document.getElementById('notificationsBtn');
        
        if (notificationsPanel && notificationsPanel.style.display === 'block' &&
            !notificationsPanel.contains(e.target) && 
            !notificationsBtn.contains(e.target)) {
            this.closeNotifications();
        }

        // Close modals when clicking outside
        document.querySelectorAll('.modal').forEach(modal => {
            if (modal.style.display === 'block' && !modal.contains(e.target)) {
                modal.style.display = 'none';
            }
        });
    }

    handleKeyboardShortcuts(e) {
        // Ctrl/Cmd + K for search
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
            e.preventDefault();
            const searchInput = document.getElementById('globalSearch');
            if (searchInput) {
                searchInput.focus();
            }
        }

        // Ctrl/Cmd + / for command palette
        if ((e.ctrlKey || e.metaKey) && e.key === '/') {
            e.preventDefault();
            this.showCommandPalette();
        }

        // Escape key to close modals and panels
        if (e.key === 'Escape') {
            this.closeNotifications();
            document.querySelectorAll('.modal').forEach(modal => {
                modal.style.display = 'none';
            });
        }
    }

    showCommandPalette() {
        // Implement command palette functionality
        console.log('Show command palette');
    }

    showNotification(message, type = 'info') {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.innerHTML = `
            <div class="notification-content">
                <i class="fas fa-${this.getNotificationIcon(type)}"></i>
                <span>${message}</span>
            </div>
            <button class="notification-close">
                <i class="fas fa-times"></i>
            </button>
        `;

        // Add to page
        document.body.appendChild(notification);

        // Animate in
        setTimeout(() => notification.classList.add('show'), 100);

        // Auto-remove after 5 seconds
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => notification.remove(), 300);
        }, 5000);

        // Close button
        notification.querySelector('.notification-close').addEventListener('click', () => {
            notification.classList.remove('show');
            setTimeout(() => notification.remove(), 300);
        });
    }

    getNotificationIcon(type) {
        const icons = {
            success: 'check-circle',
            error: 'exclamation-circle',
            warning: 'exclamation-triangle',
            info: 'info-circle'
        };
        return icons[type] || 'info-circle';
    }

    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    throttle(func, limit) {
        let inThrottle;
        return function(...args) {
            if (!inThrottle) {
                func.apply(this, args);
                inThrottle = true;
                setTimeout(() => inThrottle = false, limit);
            }
        };
    }

    // ===== DATA EXPORT =====
    exportData(type, data) {
        let content, mimeType, filename;
        
        switch (type) {
            case 'csv':
                content = this.convertToCSV(data);
                mimeType = 'text/csv';
                filename = `export-${new Date().toISOString().split('T')[0]}.csv`;
                break;
            case 'json':
                content = JSON.stringify(data, null, 2);
                mimeType = 'application/json';
                filename = `export-${new Date().toISOString().split('T')[0]}.json`;
                break;
            default:
                return;
        }
        
        const blob = new Blob([content], { type: mimeType });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = filename;
        document.body.appendChild(a);
        a.click();
        window.URL.revokeObjectURL(url);
        document.body.removeChild(a);
    }

    convertToCSV(data) {
        if (!data.length) return '';
        
        const headers = Object.keys(data[0]);
        const csvRows = [
            headers.join(','),
            ...data.map(row => headers.map(header => {
                const value = row[header];
                return typeof value === 'string' && value.includes(',') ? `"${value}"` : value;
            }).join(','))
        ];
        
        return csvRows.join('\n');
    }

    // ===== ERROR HANDLING =====
    setupErrorHandling() {
        window.addEventListener('error', (event) => {
            console.error('Global error:', event.error);
            this.trackError(event.error);
        });

        window.addEventListener('unhandledrejection', (event) => {
            console.error('Unhandled promise rejection:', event.reason);
            this.trackError(event.reason);
        });
    }

    trackError(error) {
        // Send error to analytics service
        if (typeof gtag !== 'undefined') {
            gtag('event', 'exception', {
                description: error.message,
                fatal: false
            });
        }
    }

    // ===== PERFORMANCE MONITORING =====
    setupPerformanceMonitoring() {
        if ('PerformanceObserver' in window) {
            const observer = new PerformanceObserver((list) => {
                list.getEntries().forEach((entry) => {
                    console.log(`${entry.name}: ${entry.value}`);
                });
            });

            observer.observe({ entryTypes: ['largest-contentful-paint', 'first-input', 'layout-shift'] });
        }
    }
}

// ===== INITIALIZATION =====
document.addEventListener('DOMContentLoaded', function() {
    // Initialize the admin dashboard
    window.adminDashboard = new AdminDashboard();
    
    // Service Worker Registration for PWA
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('/admin/sw.js')
            .then(registration => {
                console.log('Admin SW registered: ', registration);
            })
            .catch(registrationError => {
                console.log('Admin SW registration failed: ', registrationError);
            });
    }
});

// ===== GLOBAL UTILITIES =====
// Make utility functions available globally
window.AdminUtils = {
    formatDate: (dateString) => {
        return new Date(dateString).toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        });
    },
    
    formatDateTime: (dateString) => {
        return new Date(dateString).toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    },
    
    truncateText: (text, maxLength) => {
        if (text.length <= maxLength) return text;
        return text.substr(0, maxLength) + '...';
    },
    
    generateSlug: (text) => {
        return text
            .toLowerCase()
            .replace(/[^\w ]+/g, '')
            .replace(/ +/g, '-');
    },
    
    copyToClipboard: async (text) => {
        try {
            await navigator.clipboard.writeText(text);
            return true;
        } catch (err) {
            console.error('Failed to copy text: ', err);
            return false;
        }
    }
};

// Export for use in other modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = AdminDashboard;
}