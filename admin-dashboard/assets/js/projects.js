/**
 * Projects Management JavaScript
 * Handles projects page functionality
 */

class ProjectsManager {
    constructor() {
        this.currentPage = 1;
        this.filters = {};
        this.selectedProjects = new Set();
        this.init();
    }

    init() {
        this.bindEvents();
        this.loadProjects();
        this.updateStats();
    }

    bindEvents() {
        // Filter events - FIXED: Use correct element IDs from your HTML
        document.getElementById('statusFilter')?.addEventListener('change', (e) => {
            this.filters.status = e.target.value;
            this.applyFilters();
        });

        document.getElementById('categoryFilter')?.addEventListener('change', (e) => {
            this.filters.category = e.target.value;
            this.applyFilters();
        });

        document.getElementById('featuredFilter')?.addEventListener('change', (e) => {
            this.filters.featured = e.target.value === 'featured';
            this.applyFilters();
        });

        // FIXED: Use correct ID 'projectsSearch' from your HTML
        document.getElementById('projectsSearch')?.addEventListener('input', (e) => {
            this.filters.search = e.target.value;
            this.applyFilters();
        });

        // Clear filters
        document.getElementById('clearFilters')?.addEventListener('click', () => {
            this.clearFilters();
        });

        // Bulk actions - FIXED: Use correct IDs from your HTML
        document.getElementById('selectAllPosts')?.addEventListener('change', (e) => {
            this.toggleSelectAll(e.target.checked);
        });

        document.getElementById('applyBulkAction')?.addEventListener('click', () => {
            this.executeBulkAction();
        });

        document.getElementById('cancelBulkAction')?.addEventListener('click', () => {
            this.cancelBulkAction();
        });

        // Pagination
        document.getElementById('prevPage')?.addEventListener('click', () => {
            this.previousPage();
        });

        document.getElementById('nextPage')?.addEventListener('click', () => {
            this.nextPage();
        });

        // Delete project modals
        document.addEventListener('click', (e) => {
            if (e.target.closest('.delete-project')) {
                const button = e.target.closest('.delete-project');
                this.showDeleteModal(
                    button.getAttribute('data-project-id'),
                    button.getAttribute('data-project-title')
                );
            }
        });

        // Modal close events
        document.getElementById('closeDeleteModal')?.addEventListener('click', () => {
            this.hideModal('deleteModal');
        });

        document.getElementById('cancelDelete')?.addEventListener('click', () => {
            this.hideModal('deleteModal');
        });

        // Quick actions
        this.bindQuickActions();
    }

    bindQuickActions() {
        // Feature/unfeature, edit, preview buttons
        document.addEventListener('click', (e) => {
            if (e.target.closest('.btn-feature')) {
                this.toggleFeatured(e.target.closest('.btn-feature').dataset.id);
            }
            
            if (e.target.closest('.btn-edit')) {
                this.editProject(e.target.closest('.btn-edit').dataset.id);
            }
            
            if (e.target.closest('.btn-preview')) {
                this.previewProject(e.target.closest('.btn-preview').dataset.id);
            }
        });
    }

    async loadProjects() {
        // FIXED: Use correct container ID 'projectsGrid' from your HTML
        const container = document.getElementById('projectsGrid');
        if (!container) return;

        container.classList.add('loading');
        
        try {
            const params = new URLSearchParams({
                page: this.currentPage,
                ...this.filters
            });

            const response = await fetch(`/api/projects?${params}`);
            
            // FIXED: Handle API errors properly
            if (!response.ok) {
                throw new Error(`API returned ${response.status}: ${response.statusText}`);
            }
            
            const data = await response.json();

            // FIXED: Use correct data structure
            this.renderProjects(data.projects || data || []);
            this.updatePagination(data);
            
        } catch (error) {
            console.error('Load projects error:', error);
            this.showError('Failed to load projects. Using demo data.');
            // Fallback to empty state
            this.renderProjects([]);
        } finally {
            container.classList.remove('loading');
        }
    }

    renderProjects(projects) {
        // FIXED: Use correct container ID 'projectsGrid'
        const container = document.getElementById('projectsGrid');
        if (!container) return;

        if (!projects || projects.length === 0) {
            container.innerHTML = this.getEmptyState();
            return;
        }

        container.innerHTML = projects.map(project => this.getProjectCard(project)).join('');
    }

    getProjectCard(project) {
        // FIXED: Use correct status values and structure from your PHP
        const featuredBadge = project.featured ? 
            '<span class="featured-badge"><i class="fas fa-star"></i></span>' : '';
        
        // FIXED: Match status values from your PHP (completed, in-progress, planned, archived)
        const statusClass = `status-${(project.status || 'completed').replace('-', '')}`;
        const statusText = this.formatStatus(project.status);

        const technologies = (project.technologies || []).slice(0, 3).map(tech => 
            `<span class="tech-tag">${tech}</span>`
        ).join('');

        const techCount = project.technologies?.length || 0;
        const moreTech = techCount > 3 ? `<span class="tech-more">+${techCount - 3} more</span>` : '';

        // FIXED: Use correct image placeholder structure
        const featuredImage = project.featured_image ? 
            `<img src="${project.featured_image}" alt="${project.title || 'Project'}" onerror="this.style.display='none'">` : 
            '<div class="image-placeholder"><i class="fas fa-image"></i></div>';

        return `
            <div class="project-card" data-project-id="${project.id}">
                <div class="project-card-header">
                    <input type="checkbox" 
                           name="project_ids[]" 
                           value="${project.id}" 
                           class="project-checkbox">
                    <div class="project-badges">
                        ${featuredBadge}
                        <span class="status-badge ${statusClass}">${statusText}</span>
                    </div>
                </div>
                
                <div class="project-image">
                    ${featuredImage}
                    <div class="project-overlay">
                        <div class="project-actions">
                            <a href="projects.php?action=view&id=${project.id}" 
                               class="btn btn-icon" 
                               title="Preview">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="projects.php?action=edit&id=${project.id}" 
                               class="btn btn-icon" 
                               title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            <button type="button" 
                                    class="btn btn-icon btn-danger delete-project" 
                                    data-project-id="${project.id}"
                                    data-project-title="${this.escapeHtml(project.title || 'Untitled Project')}"
                                    title="Delete">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="project-content">
                    <h3 class="project-title">
                        <a href="projects.php?action=edit&id=${project.id}">
                            ${this.escapeHtml(project.title || 'Untitled Project')}
                        </a>
                    </h3>
                    <p class="project-description">
                        ${this.escapeHtml((project.description || '').substring(0, 100))}...
                    </p>
                    
                    <div class="project-technologies">
                        ${technologies}
                        ${moreTech}
                    </div>
                    
                    <div class="project-meta">
                        <span class="project-category">
                            <i class="fas fa-folder"></i>
                            ${this.formatCategory(project.category)}
                        </span>
                        <span class="project-date">
                            <i class="fas fa-calendar"></i>
                            ${this.formatDate(project.project_date || project.created_at)}
                        </span>
                    </div>
                </div>
            </div>
        `;
    }

    getEmptyState() {
        return `
            <div class="empty-state">
                <i class="fas fa-project-diagram"></i>
                <p>No projects found</p>
                <a href="projects.php?action=create" class="btn btn-primary">
                    <i class="fas fa-plus"></i>
                    Create Your First Project
                </a>
            </div>
        `;
    }

    async applyFilters() {
        this.currentPage = 1;
        await this.loadProjects();
    }

    clearFilters() {
        this.filters = {};
        
        // Reset filter inputs
        document.getElementById('statusFilter').value = '';
        document.getElementById('categoryFilter').value = '';
        document.getElementById('featuredFilter').value = '';
        document.getElementById('projectsSearch').value = '';
        
        this.applyFilters();
    }

    toggleSelectAll(selected) {
        const checkboxes = document.querySelectorAll('.project-checkbox');
        checkboxes.forEach(checkbox => {
            checkbox.checked = selected;
            this.updateProjectSelection(checkbox);
        });
        
        this.updateBulkActions();
    }

    updateProjectSelection(checkbox) {
        const projectId = checkbox.value;
        
        if (checkbox.checked) {
            this.selectedProjects.add(projectId);
        } else {
            this.selectedProjects.delete(projectId);
        }
        
        this.updateBulkActions();
    }

    updateBulkActions() {
        const bulkBar = document.getElementById('bulkActionsBar');
        const selectedCount = document.getElementById('selectedCount');
        const selectAll = document.getElementById('selectAllPosts');
        
        if (!bulkBar || !selectedCount) return;

        if (this.selectedProjects.size > 0) {
            bulkBar.style.display = 'flex';
            selectedCount.textContent = this.selectedProjects.size;
            
            if (selectAll) {
                selectAll.checked = this.selectedProjects.size === document.querySelectorAll('.project-checkbox').length;
                selectAll.indeterminate = this.selectedProjects.size > 0 && 
                    this.selectedProjects.size < document.querySelectorAll('.project-checkbox').length;
            }
        } else {
            bulkBar.style.display = 'none';
            if (selectAll) {
                selectAll.checked = false;
                selectAll.indeterminate = false;
            }
        }
    }

    async executeBulkAction() {
        const actionSelect = document.getElementById('bulkAction');
        const action = actionSelect?.value;

        if (!action) {
            this.showError('Please select an action.');
            return;
        }

        if (this.selectedProjects.size === 0) {
            this.showError('No projects selected.');
            return;
        }

        if (action === 'delete' && !confirm(`Are you sure you want to delete ${this.selectedProjects.size} project(s)? This action cannot be undone.`)) {
            return;
        }

        this.showLoading(`Applying ${action} to ${this.selectedProjects.size} project(s)...`);

        try {
            const response = await fetch('/api/projects/bulk-action', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: action,
                    project_ids: Array.from(this.selectedProjects)
                })
            });

            if (response.ok) {
                this.showSuccess(`Successfully ${action}ed ${this.selectedProjects.size} project(s).`);
                this.selectedProjects.clear();
                this.updateBulkActions();
                this.loadProjects();
            } else {
                throw new Error('Failed to execute bulk action');
            }
        } catch (error) {
            this.showError('Failed to execute bulk action: ' + error.message);
        } finally {
            this.hideLoading();
        }
    }

    cancelBulkAction() {
        this.selectedProjects.clear();
        document.querySelectorAll('.project-checkbox').forEach(checkbox => {
            checkbox.checked = false;
        });
        const selectAll = document.getElementById('selectAllPosts');
        if (selectAll) {
            selectAll.checked = false;
            selectAll.indeterminate = false;
        }
        this.updateBulkActions();
    }

    async toggleFeatured(projectId) {
        try {
            const response = await fetch(`/api/projects/${projectId}/feature`, {
                method: 'PUT'
            });

            if (response.ok) {
                this.showSuccess('Project featured status updated');
                this.loadProjects();
            } else {
                throw new Error('Failed to update featured status');
            }
        } catch (error) {
            this.showError('Failed to update featured status: ' + error.message);
        }
    }

    showDeleteModal(projectId, projectTitle) {
        document.getElementById('deleteProjectTitle').textContent = projectTitle;
        document.getElementById('deleteProjectId').value = projectId;
        this.showModal('deleteModal');
    }

    async deleteProject(projectId) {
        if (!confirm('Are you sure you want to delete this project? This action cannot be undone.')) {
            return;
        }

        try {
            const response = await fetch(`/api/projects/${projectId}`, {
                method: 'DELETE'
            });

            if (response.ok) {
                this.showSuccess('Project deleted successfully');
                this.loadProjects();
            } else {
                throw new Error('Failed to delete project');
            }
        } catch (error) {
            this.showError('Failed to delete project: ' + error.message);
        }
    }

    editProject(projectId) {
        window.location.href = `projects.php?action=edit&id=${projectId}`;
    }

    previewProject(projectId) {
        window.open(`projects.php?action=view&id=${projectId}`, '_blank');
    }

    previousPage() {
        if (this.currentPage > 1) {
            this.currentPage--;
            this.loadProjects();
        }
    }

    nextPage() {
        this.currentPage++;
        this.loadProjects();
    }

    updatePagination(data) {
        const prevBtn = document.getElementById('prevPage');
        const nextBtn = document.getElementById('nextPage');
        const pageInfo = document.getElementById('pageInfo');

        if (prevBtn) {
            prevBtn.disabled = this.currentPage === 1;
        }

        if (nextBtn) {
            nextBtn.disabled = this.currentPage === (data.total_pages || 1);
        }

        if (pageInfo) {
            pageInfo.textContent = `Page ${this.currentPage} of ${data.total_pages || 1}`;
        }
    }

    async updateStats() {
        try {
            const response = await fetch('/api/projects/stats');
            const stats = await response.json();

            this.updateStatCard('totalProjects', stats.total || 0);
            this.updateStatCard('featuredProjects', stats.featured || 0);
            this.updateStatCard('publishedProjects', stats.published || 0);
        } catch (error) {
            console.error('Failed to load stats:', error);
        }
    }

    updateStatCard(elementId, value) {
        const element = document.getElementById(elementId);
        if (element) {
            element.textContent = value;
        }
    }

    showModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.style.display = 'flex';
            setTimeout(() => modal.classList.add('active'), 10);
        }
    }

    hideModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.remove('active');
            setTimeout(() => modal.style.display = 'none', 300);
        }
    }

    showLoading(message = 'Loading...') {
        let loadingEl = document.getElementById('loadingOverlay');
        if (!loadingEl) {
            loadingEl = document.createElement('div');
            loadingEl.id = 'loadingOverlay';
            loadingEl.className = 'loading-overlay';
            loadingEl.innerHTML = `
                <div class="loading-spinner"></div>
                <div class="loading-message">${message}</div>
            `;
            document.body.appendChild(loadingEl);
        }
        loadingEl.style.display = 'flex';
    }

    hideLoading() {
        const loadingEl = document.getElementById('loadingOverlay');
        if (loadingEl) {
            loadingEl.style.display = 'none';
        }
    }

    showSuccess(message) {
        this.showNotification(message, 'success');
    }

    showError(message) {
        this.showNotification(message, 'error');
    }

    showNotification(message, type) {
        // Use existing alert system from your PHP
        const notification = document.createElement('div');
        notification.className = `alert alert-${type}`;
        notification.innerHTML = `
            <div class="alert-icon">
                <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
            </div>
            <div class="alert-content">
                <p>${message}</p>
            </div>
            <button class="alert-close" onclick="this.parentElement.remove()">
                <i class="fas fa-times"></i>
            </button>
        `;

        const header = document.querySelector('.admin-header');
        if (header) {
            header.parentNode.insertBefore(notification, header.nextSibling);
        }

        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, 5000);
    }

    // ADDED: Utility formatting methods
    formatStatus(status) {
        const statusMap = {
            'completed': 'Completed',
            'in-progress': 'In Progress',
            'planned': 'Planned',
            'archived': 'Archived'
        };
        return statusMap[status] || 'Completed';
    }

    formatCategory(category) {
        const categoryMap = {
            'frontend': 'Frontend',
            'backend': 'Backend',
            'fullstack': 'Full Stack',
            'mobile': 'Mobile',
            'other': 'Other'
        };
        return categoryMap[category] || 'Other';
    }

    formatDate(dateString) {
        if (!dateString) return 'N/A';
        try {
            const date = new Date(dateString);
            return date.toLocaleDateString('en-US', { 
                year: 'numeric', 
                month: 'short' 
            });
        } catch (e) {
            return 'N/A';
        }
    }

    escapeHtml(unsafe) {
        if (!unsafe) return '';
        return unsafe
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    window.projectsManager = new ProjectsManager();
});

// Utility functions for global access
function createNewProject() {
    window.location.href = 'projects.php?action=create';
}

function refreshProjects() {
    if (window.projectsManager) {
        window.projectsManager.loadProjects();
        window.projectsManager.updateStats();
    }
}

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    if (e.ctrlKey || e.metaKey) {
        switch(e.key) {
            case 'n':
                e.preventDefault();
                createNewProject();
                break;
            case 'r':
                e.preventDefault();
                refreshProjects();
                break;
        }
    }
});