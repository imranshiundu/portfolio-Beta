// api.js - API Integration & Data Management
class PortfolioAPI {
    constructor() {
        this.baseURL = 'https://api.imranshiundu.eu/api'; // Update with your actual backend URL
        this.cache = new Map();
        this.cacheTimeout = 5 * 60 * 1000; // 5 minutes cache
        this.retryAttempts = 3;
        this.retryDelay = 1000;
        
        this.init();
    }

    init() {
        this.setupInterceptors();
        console.log('Portfolio API Initialized');
    }

    // ===== REQUEST MANAGEMENT =====
    async request(endpoint, options = {}) {
        const url = `${this.baseURL}${endpoint}`;
        const cacheKey = this.generateCacheKey(url, options);
        
        // Check cache first
        if (this.isCacheValid(cacheKey)) {
            return this.getFromCache(cacheKey);
        }

        // Make API request with retry logic
        for (let attempt = 1; attempt <= this.retryAttempts; attempt++) {
            try {
                const response = await fetch(url, {
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        ...options.headers
                    },
                    ...options
                });

                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }

                const data = await response.json();
                
                // Cache successful response
                this.setCache(cacheKey, data);
                
                return data;

            } catch (error) {
                console.error(`API Request failed (attempt ${attempt}/${this.retryAttempts}):`, error);
                
                if (attempt === this.retryAttempts) {
                    throw new Error(`Failed to fetch ${endpoint}: ${error.message}`);
                }
                
                // Wait before retry
                await this.delay(this.retryDelay * attempt);
            }
        }
    }

    // ===== CACHE MANAGEMENT =====
    generateCacheKey(url, options) {
        return `${url}-${JSON.stringify(options)}`;
    }

    isCacheValid(key) {
        const cached = this.cache.get(key);
        if (!cached) return false;
        
        return Date.now() - cached.timestamp < this.cacheTimeout;
    }

    getFromCache(key) {
        console.log('Serving from cache:', key);
        return this.cache.get(key).data;
    }

    setCache(key, data) {
        this.cache.set(key, {
            data,
            timestamp: Date.now()
        });
    }

    clearCache(pattern = null) {
        if (pattern) {
            for (const key of this.cache.keys()) {
                if (key.includes(pattern)) {
                    this.cache.delete(key);
                }
            }
        } else {
            this.cache.clear();
        }
    }

    // ===== SITE CONTENT ENDPOINTS =====
    
    // Hero Section
    async getHeroContent() {
        return await this.request('/site-content/hero');
    }

    async updateHeroContent(data) {
        return await this.request('/site-content/hero', {
            method: 'PUT',
            body: JSON.stringify(data)
        });
    }

    // About Section
    async getAboutContent() {
        return await this.request('/site-content/about');
    }

    async updateAboutContent(data) {
        return await this.request('/site-content/about', {
            method: 'PUT',
            body: JSON.stringify(data)
        });
    }

    // Skills
    async getSkills() {
        return await this.request('/skills');
    }

    async createSkill(skillData) {
        return await this.request('/skills', {
            method: 'POST',
            body: JSON.stringify(skillData)
        });
    }

    async updateSkill(skillId, skillData) {
        return await this.request(`/skills/${skillId}`, {
            method: 'PUT',
            body: JSON.stringify(skillData)
        });
    }

    async deleteSkill(skillId) {
        return await this.request(`/skills/${skillId}`, {
            method: 'DELETE'
        });
    }

    // Experience Timeline
    async getExperience() {
        return await this.request('/experience');
    }

    async createExperience(experienceData) {
        return await this.request('/experience', {
            method: 'POST',
            body: JSON.stringify(experienceData)
        });
    }

    async updateExperience(experienceId, experienceData) {
        return await this.request(`/experience/${experienceId}`, {
            method: 'PUT',
            body: JSON.stringify(experienceData)
        });
    }

    async deleteExperience(experienceId) {
        return await this.request(`/experience/${experienceId}`, {
            method: 'DELETE'
        });
    }

    // ===== PROJECTS ENDPOINTS =====
    async getProjects(params = {}) {
        const queryString = new URLSearchParams(params).toString();
        const endpoint = queryString ? `/projects?${queryString}` : '/projects';
        return await this.request(endpoint);
    }

    async getFeaturedProjects() {
        return await this.request('/projects/featured');
    }

    async getProject(projectId) {
        return await this.request(`/projects/${projectId}`);
    }

    async createProject(projectData) {
        const formData = this.createFormData(projectData);
        
        return await this.request('/projects', {
            method: 'POST',
            body: formData,
            headers: {} // Let browser set Content-Type for FormData
        });
    }

    async updateProject(projectId, projectData) {
        const formData = this.createFormData(projectData);
        
        return await this.request(`/projects/${projectId}`, {
            method: 'PUT',
            body: formData,
            headers: {}
        });
    }

    async deleteProject(projectId) {
        return await this.request(`/projects/${projectId}`, {
            method: 'DELETE'
        });
    }

    async getProjectStats() {
        return await this.request('/projects/stats');
    }

    async getProjectTechnologies() {
        return await this.request('/projects/technologies');
    }

    async getProjectCategories() {
        return await this.request('/projects/categories');
    }

    // ===== BLOG ENDPOINTS =====
    async getBlogPosts(params = {}) {
        const queryString = new URLSearchParams(params).toString();
        const endpoint = queryString ? `/blog/posts?${queryString}` : '/blog/posts';
        return await this.request(endpoint);
    }

    async getFeaturedPost() {
        return await this.request('/blog/featured');
    }

    async getBlogPost(postId) {
        return await this.request(`/blog/posts/${postId}`);
    }

    async createBlogPost(postData) {
        const formData = this.createFormData(postData);
        
        return await this.request('/blog/posts', {
            method: 'POST',
            body: formData,
            headers: {}
        });
    }

    async updateBlogPost(postId, postData) {
        const formData = this.createFormData(postData);
        
        return await this.request(`/blog/posts/${postId}`, {
            method: 'PUT',
            body: formData,
            headers: {}
        });
    }

    async deleteBlogPost(postId) {
        return await this.request(`/blog/posts/${postId}`, {
            method: 'DELETE'
        });
    }

    async getBlogStats() {
        return await this.request('/blog/stats');
    }

    async getBlogCategories() {
        return await this.request('/blog/categories');
    }

    async getBlogTags() {
        return await this.request('/blog/tags');
    }

    // ===== CONTACT ENDPOINTS =====
    async getContactInfo() {
        return await this.request('/contact/info');
    }

    async updateContactInfo(contactData) {
        return await this.request('/contact/info', {
            method: 'PUT',
            body: JSON.stringify(contactData)
        });
    }

    async submitContactForm(formData) {
        return await this.request('/contact/submit', {
            method: 'POST',
            body: JSON.stringify(formData)
        });
    }

    async getContactSettings() {
        return await this.request('/contact/settings');
    }

    async getContactAvailability() {
        return await this.request('/contact/availability');
    }

    // ===== FOOTER & SOCIAL LINKS =====
    async getFooterContent() {
        return await this.request('/site-content/footer');
    }

    async updateFooterContent(data) {
        return await this.request('/site-content/footer', {
            method: 'PUT',
            body: JSON.stringify(data)
        });
    }

    async getSocialLinks() {
        return await this.request('/social-links');
    }

    async updateSocialLinks(links) {
        return await this.request('/social-links', {
            method: 'PUT',
            body: JSON.stringify(links)
        });
    }

    // ===== FILE UPLOAD HANDLING =====
    async uploadFile(file, onProgress = null) {
        const formData = new FormData();
        formData.append('file', file);

        return new Promise((resolve, reject) => {
            const xhr = new XMLHttpRequest();

            // Progress tracking
            if (onProgress) {
                xhr.upload.addEventListener('progress', (e) => {
                    if (e.lengthComputable) {
                        const percentComplete = (e.loaded / e.total) * 100;
                        onProgress(percentComplete);
                    }
                });
            }

            xhr.addEventListener('load', () => {
                if (xhr.status === 200) {
                    resolve(JSON.parse(xhr.responseText));
                } else {
                    reject(new Error(`Upload failed: ${xhr.statusText}`));
                }
            });

            xhr.addEventListener('error', () => {
                reject(new Error('Upload failed'));
            });

            xhr.open('POST', `${this.baseURL}/upload`);
            xhr.send(formData);
        });
    }

    async deleteFile(fileUrl) {
        return await this.request('/upload', {
            method: 'DELETE',
            body: JSON.stringify({ fileUrl })
        });
    }

    // ===== FORM DATA HELPER =====
    createFormData(data) {
        const formData = new FormData();
        
        for (const key in data) {
            if (data[key] !== null && data[key] !== undefined) {
                if (Array.isArray(data[key])) {
                    // Handle arrays (like technologies, tags)
                    data[key].forEach((item, index) => {
                        if (typeof item === 'object') {
                            // Handle array of objects
                            for (const subKey in item) {
                                formData.append(`${key}[${index}][${subKey}]`, item[subKey]);
                            }
                        } else {
                            // Handle array of primitives
                            formData.append(`${key}[]`, item);
                        }
                    });
                } else if (typeof data[key] === 'object' && !(data[key] instanceof File)) {
                    // Handle nested objects
                    for (const subKey in data[key]) {
                        formData.append(`${key}[${subKey}]`, data[key][subKey]);
                    }
                } else {
                    // Handle primitives and files
                    formData.append(key, data[key]);
                }
            }
        }
        
        return formData;
    }

    // ===== INTERCEPTORS & MIDDLEWARE =====
    setupInterceptors() {
        // Request interceptor
        const originalRequest = this.request;
        this.request = async (endpoint, options = {}) => {
            // Add authentication token if available
            const token = this.getAuthToken();
            if (token) {
                options.headers = {
                    ...options.headers,
                    'Authorization': `Bearer ${token}`
                };
            }

            // Add request timestamp for caching
            options.headers = {
                ...options.headers,
                'X-Request-Timestamp': Date.now()
            };

            return await originalRequest.call(this, endpoint, options);
        };

        // Response interceptor
        this.handleResponse = (response) => {
            // Check for maintenance mode
            if (response.headers.get('X-Maintenance-Mode')) {
                this.handleMaintenanceMode();
                throw new Error('Service undergoing maintenance');
            }

            // Check for rate limiting
            if (response.status === 429) {
                this.handleRateLimit();
                throw new Error('Rate limit exceeded');
            }

            return response;
        };
    }

    // ===== AUTHENTICATION =====
    getAuthToken() {
        return localStorage.getItem('auth_token');
    }

    setAuthToken(token) {
        localStorage.setItem('auth_token', token);
    }

    removeAuthToken() {
        localStorage.removeItem('auth_token');
    }

    async login(credentials) {
        const response = await this.request('/auth/login', {
            method: 'POST',
            body: JSON.stringify(credentials)
        });

        if (response.token) {
            this.setAuthToken(response.token);
            this.clearCache(); // Clear cache on login
        }

        return response;
    }

    async logout() {
        try {
            await this.request('/auth/logout', {
                method: 'POST'
            });
        } catch (error) {
            console.error('Logout error:', error);
        } finally {
            this.removeAuthToken();
            this.clearCache();
        }
    }

    async refreshToken() {
        const token = this.getAuthToken();
        if (!token) return null;

        try {
            const response = await this.request('/auth/refresh', {
                method: 'POST',
                body: JSON.stringify({ token })
            });

            if (response.token) {
                this.setAuthToken(response.token);
            }

            return response;
        } catch (error) {
            this.removeAuthToken();
            throw error;
        }
    }

    // ===== ERROR HANDLING =====
    handleMaintenanceMode() {
        // Show maintenance mode notification
        this.showNotification('Service is currently undergoing maintenance. Please try again later.', 'warning');
    }

    handleRateLimit() {
        // Show rate limit notification
        this.showNotification('Too many requests. Please wait a moment before trying again.', 'warning');
    }

    showNotification(message, type = 'info') {
        // Create and show notification UI
        const notification = document.createElement('div');
        notification.className = `api-notification api-notification-${type}`;
        notification.innerHTML = `
            <div class="notification-content">
                <span class="notification-message">${message}</span>
                <button class="notification-close">&times;</button>
            </div>
        `;

        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: ${type === 'error' ? '#fef2f2' : type === 'warning' ? '#fffbeb' : '#f0f9ff'};
            border: 1px solid ${type === 'error' ? '#fecaca' : type === 'warning' ? '#fed7aa' : '#bae6fd'};
            color: ${type === 'error' ? '#dc2626' : type === 'warning' ? '#ea580c' : '#0369a1'};
            padding: 12px 16px;
            border-radius: 8px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            z-index: 10000;
            max-width: 400px;
        `;

        document.body.appendChild(notification);

        // Auto remove after 5 seconds
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 5000);

        // Close button handler
        notification.querySelector('.notification-close').addEventListener('click', () => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        });
    }

    // ===== UTILITY FUNCTIONS =====
    delay(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }

    // ===== OFFLINE SUPPORT =====
    async getCachedData(endpoint) {
        const cacheKey = this.generateCacheKey(`${this.baseURL}${endpoint}`, {});
        if (this.isCacheValid(cacheKey)) {
            return this.getFromCache(cacheKey);
        }
        return null;
    }

    // Check if API is available
    async checkHealth() {
        try {
            const response = await fetch(`${this.baseURL}/health`, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json'
                }
            });
            return response.ok;
        } catch (error) {
            return false;
        }
    }

    // ===== BATCH REQUESTS =====
    async batchRequests(requests) {
        const results = [];
        
        for (const request of requests) {
            try {
                const result = await this.request(request.endpoint, request.options);
                results.push({ status: 'fulfilled', value: result });
            } catch (error) {
                results.push({ status: 'rejected', reason: error.message });
            }
        }
        
        return results;
    }

    // ===== REAL-TIME UPDATES (WebSocket) =====
    connectWebSocket() {
        if (this.socket) return;

        const protocol = window.location.protocol === 'https:' ? 'wss:' : 'ws:';
        const wsUrl = `${protocol}//${window.location.host.replace('api.', '')}/ws`;
        
        this.socket = new WebSocket(wsUrl);
        
        this.socket.onopen = () => {
            console.log('WebSocket connected');
            this.socket.send(JSON.stringify({ type: 'subscribe', channels: ['content_updates'] }));
        };
        
        this.socket.onmessage = (event) => {
            const data = JSON.parse(event.data);
            this.handleWebSocketMessage(data);
        };
        
        this.socket.onclose = () => {
            console.log('WebSocket disconnected');
            this.socket = null;
            
            // Attempt reconnect after 5 seconds
            setTimeout(() => this.connectWebSocket(), 5000);
        };
    }

    handleWebSocketMessage(data) {
        switch (data.type) {
            case 'content_updated':
                // Clear cache for updated content
                this.clearCache(data.resource);
                // Trigger UI update
                this.emit('contentUpdated', data);
                break;
                
            case 'new_comment':
                this.emit('newComment', data);
                break;
                
            case 'project_updated':
                this.clearCache('projects');
                this.emit('projectUpdated', data);
                break;
        }
    }

    // ===== EVENT EMITTER =====
    events = {};
    
    on(event, callback) {
        if (!this.events[event]) {
            this.events[event] = [];
        }
        this.events[event].push(callback);
    }
    
    off(event, callback) {
        if (!this.events[event]) return;
        
        this.events[event] = this.events[event].filter(cb => cb !== callback);
    }
    
    emit(event, data) {
        if (!this.events[event]) return;
        
        this.events[event].forEach(callback => {
            try {
                callback(data);
            } catch (error) {
                console.error(`Error in event handler for ${event}:`, error);
            }
        });
    }
}

// ===== GLOBAL INSTANCE =====
window.portfolioAPI = new PortfolioAPI();

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = PortfolioAPI;
}