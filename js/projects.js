/**
 * projects.js - Handles projects page functionality
 * Includes project filtering, search, animations, and dynamic content
 */

class ProjectsManager {
    constructor() {
        this.projects = [];
        this.filteredProjects = [];
        this.currentFilter = 'all';
        this.currentSearch = '';
        this.isLoading = false;
        
        this.init();
    }

    init() {
        this.loadProjectsData();
        this.setupEventListeners();
        this.setupIntersectionObserver();
        this.initializeProjectModal();
    }

    /**
     * Load projects data from API
     */
    async loadProjectsData() {
        this.showLoadingState();
        
        try {
            const data = await window.apiClient.getProjects();
            this.projects = data.projects || [];
            this.filteredProjects = [...this.projects];
            
            this.renderProjects();
            this.updateProjectStats();
            this.hideLoadingState();
            
        } catch (error) {
            console.error('Error loading projects:', error);
            this.showErrorState();
        }
    }

    /**
     * Render projects to the DOM
     */
    renderProjects() {
        const projectsGrid = document.getElementById('projectsGrid');
        if (!projectsGrid) return;

        if (this.filteredProjects.length === 0) {
            projectsGrid.innerHTML = this.getNoProjectsHTML();
            return;
        }

        projectsGrid.innerHTML = this.filteredProjects
            .map(project => this.createProjectCardHTML(project))
            .join('');

        // Re-initialize animations for new elements
        this.initializeProjectAnimations();
    }

    /**
     * Create HTML for a project card
     */
    createProjectCardHTML(project) {
        return `
            <div class="project-card" data-project-id="${project.id}" data-categories="${project.categories.join(',')}">
                <div class="project-image">
                    <img src="${project.image}" alt="${project.title}" loading="lazy">
                    <div class="project-overlay">
                        <div class="project-actions">
                            <button class="btn btn-primary btn-sm view-project" data-project="${project.id}">
                                <i class="fas fa-eye"></i> View Details
                            </button>
                            ${project.demoUrl ? `
                                <a href="${project.demoUrl}" class="btn btn-secondary btn-sm" target="_blank" rel="noopener">
                                    <i class="fas fa-external-link-alt"></i> Live Demo
                                </a>
                            ` : ''}
                        </div>
                    </div>
                    ${project.featured ? '<span class="project-badge">Featured</span>' : ''}
                </div>
                
                <div class="project-content">
                    <div class="project-categories">
                        ${project.categories.map(cat => 
                            `<span class="project-category" data-category="${cat}">${cat}</span>`
                        ).join('')}
                    </div>
                    
                    <h3 class="project-title">${project.title}</h3>
                    <p class="project-description">${project.description}</p>
                    
                    <div class="project-tech">
                        ${project.technologies.slice(0, 4).map(tech => 
                            `<span class="tech-tag">${tech}</span>`
                        ).join('')}
                        ${project.technologies.length > 4 ? 
                            `<span class="tech-tag-more">+${project.technologies.length - 4}</span>` : ''
                        }
                    </div>
                    
                    <div class="project-meta">
                        <span class="project-date">
                            <i class="fas fa-calendar"></i>
                            ${new Date(project.date).toLocaleDateString()}
                        </span>
                        ${project.githubUrl ? `
                            <a href="${project.githubUrl}" class="project-github" target="_blank" rel="noopener">
                                <i class="fab fa-github"></i>
                            </a>
                        ` : ''}
                    </div>
                </div>
            </div>
        `;
    }

    /**
     * Filter projects by category
     */
    filterProjects(category) {
        this.currentFilter = category;
        
        if (category === 'all') {
            this.filteredProjects = [...this.projects];
        } else {
            this.filteredProjects = this.projects.filter(project => 
                project.categories.includes(category)
            );
        }
        
        // Apply search filter if exists
        if (this.currentSearch) {
            this.searchProjects(this.currentSearch);
        } else {
            this.renderProjects();
            this.updateActiveFilter(category);
        }
    }

    /**
     * Search through projects
     */
    searchProjects(query) {
        this.currentSearch = query.toLowerCase().trim();
        
        let filtered = [...this.projects];
        
        // Apply category filter first
        if (this.currentFilter !== 'all') {
            filtered = filtered.filter(project => 
                project.categories.includes(this.currentFilter)
            );
        }
        
        // Then apply search filter
        if (this.currentSearch) {
            filtered = filtered.filter(project => 
                project.title.toLowerCase().includes(this.currentSearch) ||
                project.description.toLowerCase().includes(this.currentSearch) ||
                project.technologies.some(tech => tech.toLowerCase().includes(this.currentSearch)) ||
                project.categories.some(cat => cat.toLowerCase().includes(this.currentSearch))
            );
        }
        
        this.filteredProjects = filtered;
        this.renderProjects();
        this.updateSearchResultsCount();
    }

    /**
     * Update active filter button state
     */
    updateActiveFilter(activeCategory) {
        document.querySelectorAll('.filter-btn').forEach(btn => {
            btn.classList.toggle('active', btn.dataset.filter === activeCategory);
        });
    }

    /**
     * Update search results count
     */
    updateSearchResultsCount() {
        const resultsCount = document.getElementById('searchResultsCount');
        const searchTerm = document.getElementById('searchTerm');
        
        if (resultsCount) {
            resultsCount.textContent = this.filteredProjects.length;
        }
        
        if (searchTerm && this.currentSearch) {
            searchTerm.textContent = `"${this.currentSearch}"`;
        }
    }

    /**
     * Update project statistics
     */
    updateProjectStats() {
        const stats = {
            total: this.projects.length,
            completed: this.projects.filter(p => p.status === 'completed').length,
            inProgress: this.projects.filter(p => p.status === 'in-progress').length,
            technologies: [...new Set(this.projects.flatMap(p => p.technologies))].length
        };

        // Animate stats counting
        this.animateValue('totalProjects', 0, stats.total, 2000);
        this.animateValue('completedProjects', 0, stats.completed, 2000);
        this.animateValue('inProgressProjects', 0, stats.inProgress, 2000);
        this.animateValue('technologiesUsed', 0, stats.technologies, 2000);
    }

    /**
     * Animate number counting
     */
    animateValue(elementId, start, end, duration) {
        const element = document.getElementById(elementId);
        if (!element) return;

        let startTimestamp = null;
        const step = (timestamp) => {
            if (!startTimestamp) startTimestamp = timestamp;
            const progress = Math.min((timestamp - startTimestamp) / duration, 1);
            const value = Math.floor(progress * (end - start) + start);
            element.textContent = value.toLocaleString();
            
            if (progress < 1) {
                window.requestAnimationFrame(step);
            }
        };
        window.requestAnimationFrame(step);
    }

    /**
     * Initialize project modal for detailed views
     */
    initializeProjectModal() {
        this.modal = document.getElementById('projectModal');
        if (!this.modal) return;

        // Close modal handlers
        this.modal.addEventListener('click', (e) => {
            if (e.target === this.modal || e.target.closest('.modal-close')) {
                this.closeProjectModal();
            }
        });

        // Keyboard handlers
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.modal.classList.contains('active')) {
                this.closeProjectModal();
            }
        });
    }

    /**
     * Open project modal with details
     */
    openProjectModal(projectId) {
        const project = this.projects.find(p => p.id === projectId);
        if (!project || !this.modal) return;

        const modalContent = this.modal.querySelector('.modal-content');
        modalContent.innerHTML = this.createProjectModalHTML(project);

        this.modal.classList.add('active');
        document.body.style.overflow = 'hidden';

        // Load project images
        this.loadProjectImages(project);
    }

    /**
     * Close project modal
     */
    closeProjectModal() {
        if (this.modal) {
            this.modal.classList.remove('active');
            document.body.style.overflow = '';
        }
    }

    /**
     * Create modal HTML for project details
     */
    createProjectModalHTML(project) {
        return `
            <div class="modal-header">
                <h2 class="modal-title">${project.title}</h2>
                <button class="modal-close" aria-label="Close modal">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div class="modal-body">
                <div class="project-gallery">
                    <div class="main-image">
                        <img src="${project.image}" alt="${project.title}" id="mainProjectImage">
                    </div>
                    ${project.gallery && project.gallery.length > 0 ? `
                        <div class="image-thumbnails">
                            ${project.gallery.map((img, index) => `
                                <img src="${img}" alt="${project.title} - Image ${index + 1}" 
                                     class="thumbnail ${index === 0 ? 'active' : ''}"
                                     data-image="${img}">
                            `).join('')}
                        </div>
                    ` : ''}
                </div>
                
                <div class="project-details">
                    <div class="detail-section">
                        <h3>Project Overview</h3>
                        <p>${project.fullDescription || project.description}</p>
                    </div>
                    
                    <div class="detail-grid">
                        <div class="detail-item">
                            <h4>Technologies</h4>
                            <div class="tech-list">
                                ${project.technologies.map(tech => 
                                    `<span class="tech-tag">${tech}</span>`
                                ).join('')}
                            </div>
                        </div>
                        
                        <div class="detail-item">
                            <h4>Categories</h4>
                            <div class="category-list">
                                ${project.categories.map(cat => 
                                    `<span class="project-category">${cat}</span>`
                                ).join('')}
                            </div>
                        </div>
                        
                        <div class="detail-item">
                            <h4>Timeline</h4>
                            <p>${project.timeline || 'Not specified'}</p>
                        </div>
                        
                        <div class="detail-item">
                            <h4>Status</h4>
                            <span class="project-status ${project.status}">${project.status}</span>
                        </div>
                    </div>
                    
                    ${project.features && project.features.length > 0 ? `
                        <div class="detail-section">
                            <h4>Key Features</h4>
                            <ul class="features-list">
                                ${project.features.map(feature => 
                                    `<li>${feature}</li>`
                                ).join('')}
                            </ul>
                        </div>
                    ` : ''}
                    
                    ${project.challenges && project.challenges.length > 0 ? `
                        <div class="detail-section">
                            <h4>Challenges & Solutions</h4>
                            <div class="challenges-list">
                                ${project.challenges.map(challenge => `
                                    <div class="challenge-item">
                                        <strong>${challenge.problem}</strong>
                                        <p>${challenge.solution}</p>
                                    </div>
                                `).join('')}
                            </div>
                        </div>
                    ` : ''}
                </div>
            </div>
            
            <div class="modal-footer">
                <div class="project-links">
                    ${project.demoUrl ? `
                        <a href="${project.demoUrl}" class="btn btn-primary" target="_blank" rel="noopener">
                            <i class="fas fa-external-link-alt"></i> Live Demo
                        </a>
                    ` : ''}
                    
                    ${project.githubUrl ? `
                        <a href="${project.githubUrl}" class="btn btn-secondary" target="_blank" rel="noopener">
                            <i class="fab fa-github"></i> View Code
                        </a>
                    ` : ''}
                    
                    <button class="btn btn-outline close-modal">
                        <i class="fas fa-times"></i> Close
                    </button>
                </div>
            </div>
        `;
    }

    /**
     * Load project images with error handling
     */
    loadProjectImages(project) {
        const mainImage = document.getElementById('mainProjectImage');
        if (mainImage) {
            mainImage.onerror = () => {
                mainImage.src = '/images/placeholder-project.jpg';
            };
        }

        // Set up thumbnail click handlers
        document.querySelectorAll('.thumbnail').forEach(thumb => {
            thumb.addEventListener('click', () => {
                const newSrc = thumb.dataset.image;
                if (mainImage && newSrc) {
                    mainImage.src = newSrc;
                    document.querySelectorAll('.thumbnail').forEach(t => t.classList.remove('active'));
                    thumb.classList.add('active');
                }
            });
        });
    }

    /**
     * Setup event listeners
     */
    setupEventListeners() {
        // Filter buttons
        document.addEventListener('click', (e) => {
            if (e.target.closest('.filter-btn')) {
                const filterBtn = e.target.closest('.filter-btn');
                const filter = filterBtn.dataset.filter;
                this.filterProjects(filter);
            }
            
            // View project details
            if (e.target.closest('.view-project')) {
                const projectId = e.target.closest('.view-project').dataset.project;
                this.openProjectModal(projectId);
            }
        });

        // Search input
        const searchInput = document.getElementById('projectSearch');
        if (searchInput) {
            let searchTimeout;
            searchInput.addEventListener('input', (e) => {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    this.searchProjects(e.target.value);
                }, 300);
            });
        }

        // Clear search
        const clearSearch = document.getElementById('clearSearch');
        if (clearSearch) {
            clearSearch.addEventListener('click', () => {
                if (searchInput) searchInput.value = '';
                this.currentSearch = '';
                this.filterProjects(this.currentFilter);
            });
        }

        // Project card interactions
        document.addEventListener('mouseenter', (e) => {
            if (e.target.closest('.project-card')) {
                const card = e.target.closest('.project-card');
                this.animateProjectCard(card, 'enter');
            }
        }, true);

        document.addEventListener('mouseleave', (e) => {
            if (e.target.closest('.project-card')) {
                const card = e.target.closest('.project-card');
                this.animateProjectCard(card, 'leave');
            }
        }, true);
    }

    /**
     * Animate project cards on interaction
     */
    animateProjectCard(card, action) {
        if (action === 'enter') {
            card.style.transform = 'translateY(-8px)';
            card.style.boxShadow = '0 20px 40px rgba(0,0,0,0.15)';
        } else {
            card.style.transform = 'translateY(0)';
            card.style.boxShadow = '';
        }
    }

    /**
     * Setup intersection observer for animations
     */
    setupIntersectionObserver() {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animate-in');
                    observer.unobserve(entry.target);
                }
            });
        }, {
            threshold: 0.1,
            rootMargin: '50px'
        });

        // Observe project cards when they are rendered
        this.observeProjectCards(observer);
    }

    /**
     * Observe project cards for animation
     */
    observeProjectCards(observer) {
        // This will be called after projects are rendered
        const cards = document.querySelectorAll('.project-card');
        cards.forEach(card => {
            card.classList.add('will-animate');
            observer.observe(card);
        });
    }

    /**
     * Initialize project animations
     */
    initializeProjectAnimations() {
        const cards = document.querySelectorAll('.project-card');
        cards.forEach((card, index) => {
            card.style.animationDelay = `${index * 0.1}s`;
        });
    }

    /**
     * Show loading state
     */
    showLoadingState() {
        this.isLoading = true;
        const grid = document.getElementById('projectsGrid');
        if (grid) {
            grid.innerHTML = `
                <div class="loading-skeleton project-card-skeleton"></div>
                <div class="loading-skeleton project-card-skeleton"></div>
                <div class="loading-skeleton project-card-skeleton"></div>
                <div class="loading-skeleton project-card-skeleton"></div>
            `;
        }
    }

    /**
     * Hide loading state
     */
    hideLoadingState() {
        this.isLoading = false;
        document.querySelectorAll('.project-card-skeleton').forEach(skeleton => {
            skeleton.remove();
        });
    }

    /**
     * Show error state
     */
    showErrorState() {
        const grid = document.getElementById('projectsGrid');
        if (grid) {
            grid.innerHTML = `
                <div class="error-state">
                    <i class="fas fa-exclamation-triangle"></i>
                    <h3>Unable to Load Projects</h3>
                    <p>Please check your connection and try again.</p>
                    <button class="btn btn-primary" onclick="projectsManager.loadProjectsData()">
                        <i class="fas fa-redo"></i> Retry
                    </button>
                </div>
            `;
        }
    }

    /**
     * Get HTML for no projects state
     */
    getNoProjectsHTML() {
        return `
            <div class="no-projects">
                <i class="fas fa-search"></i>
                <h3>No Projects Found</h3>
                <p>Try adjusting your search or filter criteria.</p>
                <button class="btn btn-primary" onclick="projectsManager.filterProjects('all')">
                    Show All Projects
                </button>
            </div>
        `;
    }
}

// Initialize Projects Manager when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.projectsManager = new ProjectsManager();
});

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = ProjectsManager;
}