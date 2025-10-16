/**
 * blog.js - Handles blog page functionality
 * Includes article loading, filtering, search, reading progress, and interactions
 */

class BlogManager {
    constructor() {
        this.articles = [];
        this.filteredArticles = [];
        this.currentFilter = 'all';
        this.currentSearch = '';
        this.currentSort = 'newest';
        this.isLoading = false;
        this.readingProgress = 0;
        
        this.init();
    }

    init() {
        this.loadArticlesData();
        this.setupEventListeners();
        this.setupIntersectionObserver();
        this.initializeReadingProgress();
        this.initializeArticleModal();
        this.setupSearchFunctionality();
    }

    /**
     * Load articles data from API
     */
    async loadArticlesData() {
        this.showLoadingState();
        
        try {
            const data = await window.apiClient.getBlogPosts();
            this.articles = data.articles || [];
            this.filteredArticles = [...this.articles];
            
            this.renderArticles();
            this.updateBlogStats();
            this.hideLoadingState();
            
        } catch (error) {
            console.error('Error loading articles:', error);
            this.showErrorState();
        }
    }

    /**
     * Render articles to the DOM
     */
    renderArticles() {
        const articlesGrid = document.getElementById('articlesGrid');
        if (!articlesGrid) return;

        if (this.filteredArticles.length === 0) {
            articlesGrid.innerHTML = this.getNoArticlesHTML();
            return;
        }

        articlesGrid.innerHTML = this.filteredArticles
            .map(article => this.createArticleCardHTML(article))
            .join('');

        // Re-initialize animations for new elements
        this.initializeArticleAnimations();
    }

    /**
     * Create HTML for an article card
     */
    createArticleCardHTML(article) {
        const readingTime = this.calculateReadingTime(article.content);
        const publishedDate = new Date(article.publishedAt).toLocaleDateString();
        const excerpt = article.excerpt || this.truncateText(article.content, 150);

        return `
            <article class="article-card" data-article-id="${article.id}" data-categories="${article.categories.join(',')}">
                <div class="article-image">
                    <img src="${article.image}" alt="${article.title}" loading="lazy">
                    <div class="article-overlay">
                        <div class="article-actions">
                            <button class="btn btn-primary btn-sm read-article" data-article="${article.id}">
                                <i class="fas fa-book-open"></i> Read Article
                            </button>
                            ${article.demoUrl ? `
                                <a href="${article.demoUrl}" class="btn btn-secondary btn-sm" target="_blank" rel="noopener">
                                    <i class="fas fa-external-link-alt"></i> View Demo
                                </a>
                            ` : ''}
                        </div>
                    </div>
                    ${article.featured ? '<span class="article-badge">Featured</span>' : ''}
                    ${article.isNew ? '<span class="article-badge new">New</span>' : ''}
                </div>
                
                <div class="article-content">
                    <div class="article-meta">
                        <span class="article-date">
                            <i class="fas fa-calendar"></i>
                            ${publishedDate}
                        </span>
                        <span class="article-reading-time">
                            <i class="fas fa-clock"></i>
                            ${readingTime} min read
                        </span>
                        <span class="article-comments">
                            <i class="fas fa-comments"></i>
                            ${article.commentCount || 0}
                        </span>
                    </div>
                    
                    <div class="article-categories">
                        ${article.categories.map(cat => 
                            `<span class="article-category" data-category="${cat}">${cat}</span>`
                        ).join('')}
                    </div>
                    
                    <h3 class="article-title">
                        <a href="#" class="article-title-link" data-article="${article.id}">${article.title}</a>
                    </h3>
                    
                    <p class="article-excerpt">${excerpt}</p>
                    
                    <div class="article-tags">
                        ${article.tags.slice(0, 5).map(tag => 
                            `<span class="article-tag">${tag}</span>`
                        ).join('')}
                        ${article.tags.length > 5 ? 
                            `<span class="tag-more">+${article.tags.length - 5}</span>` : ''
                        }
                    </div>
                    
                    <div class="article-footer">
                        <div class="article-author">
                            <div class="author-avatar">
                                <img src="${article.author?.avatar || '/images/default-avatar.jpg'}" alt="${article.author?.name || 'Author'}">
                            </div>
                            <span class="author-name">${article.author?.name || 'Imran Shiundu'}</span>
                        </div>
                        
                        <div class="article-engagement">
                            <button class="engagement-btn like-btn" data-article="${article.id}" aria-label="Like article">
                                <i class="far fa-heart"></i>
                                <span class="engagement-count">${article.likes || 0}</span>
                            </button>
                            <button class="engagement-btn bookmark-btn" data-article="${article.id}" aria-label="Bookmark article">
                                <i class="far fa-bookmark"></i>
                            </button>
                            <button class="engagement-btn share-btn" data-article="${article.id}" aria-label="Share article">
                                <i class="fas fa-share-alt"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </article>
        `;
    }

    /**
     * Filter articles by category
     */
    filterArticles(category) {
        this.currentFilter = category;
        
        if (category === 'all') {
            this.filteredArticles = [...this.articles];
        } else {
            this.filteredArticles = this.articles.filter(article => 
                article.categories.includes(category)
            );
        }
        
        // Apply search and sort filters
        this.applyFilters();
    }

    /**
     * Search through articles
     */
    searchArticles(query) {
        this.currentSearch = query.toLowerCase().trim();
        this.applyFilters();
    }

    /**
     * Sort articles
     */
    sortArticles(sortBy) {
        this.currentSort = sortBy;
        this.applyFilters();
    }

    /**
     * Apply all active filters
     */
    applyFilters() {
        let filtered = [...this.articles];
        
        // Apply category filter
        if (this.currentFilter !== 'all') {
            filtered = filtered.filter(article => 
                article.categories.includes(this.currentFilter)
            );
        }
        
        // Apply search filter
        if (this.currentSearch) {
            filtered = filtered.filter(article => 
                article.title.toLowerCase().includes(this.currentSearch) ||
                article.content.toLowerCase().includes(this.currentSearch) ||
                article.excerpt.toLowerCase().includes(this.currentSearch) ||
                article.tags.some(tag => tag.toLowerCase().includes(this.currentSearch)) ||
                article.categories.some(cat => cat.toLowerCase().includes(this.currentSearch))
            );
        }
        
        // Apply sorting
        filtered = this.sortArticlesList(filtered, this.currentSort);
        
        this.filteredArticles = filtered;
        this.renderArticles();
        this.updateSearchResultsCount();
    }

    /**
     * Sort articles based on criteria
     */
    sortArticlesList(articles, sortBy) {
        switch (sortBy) {
            case 'newest':
                return articles.sort((a, b) => new Date(b.publishedAt) - new Date(a.publishedAt));
                
            case 'oldest':
                return articles.sort((a, b) => new Date(a.publishedAt) - new Date(b.publishedAt));
                
            case 'most-popular':
                return articles.sort((a, b) => (b.views || 0) - (a.views || 0));
                
            case 'most-liked':
                return articles.sort((a, b) => (b.likes || 0) - (a.likes || 0));
                
            case 'reading-time':
                return articles.sort((a, b) => this.calculateReadingTime(b.content) - this.calculateReadingTime(a.content));
                
            default:
                return articles;
        }
    }

    /**
     * Calculate reading time for article content
     */
    calculateReadingTime(content) {
        const wordsPerMinute = 200;
        const wordCount = content.split(/\s+/).length;
        return Math.ceil(wordCount / wordsPerMinute);
    }

    /**
     * Truncate text to specified length
     */
    truncateText(text, maxLength) {
        if (text.length <= maxLength) return text;
        return text.substr(0, maxLength).trim() + '...';
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
     * Update active sort button state
     */
    updateActiveSort(activeSort) {
        document.querySelectorAll('.sort-btn').forEach(btn => {
            btn.classList.toggle('active', btn.dataset.sort === activeSort);
        });
    }

    /**
     * Update search results count
     */
    updateSearchResultsCount() {
        const resultsCount = document.getElementById('searchResultsCount');
        const searchTerm = document.getElementById('searchTerm');
        
        if (resultsCount) {
            resultsCount.textContent = this.filteredArticles.length;
        }
        
        if (searchTerm && this.currentSearch) {
            searchTerm.textContent = `"${this.currentSearch}"`;
        }
    }

    /**
     * Update blog statistics
     */
    updateBlogStats() {
        const stats = {
            total: this.articles.length,
            published: this.articles.filter(a => a.status === 'published').length,
            categories: [...new Set(this.articles.flatMap(a => a.categories))].length,
            totalWords: this.articles.reduce((sum, article) => sum + article.content.split(/\s+/).length, 0)
        };

        // Animate stats counting
        this.animateValue('totalArticles', 0, stats.total, 2000);
        this.animateValue('publishedArticles', 0, stats.published, 2000);
        this.animateValue('articleCategories', 0, stats.categories, 2000);
        this.animateValue('totalWords', 0, stats.totalWords, 2500);
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
     * Initialize article modal for full reading
     */
    initializeArticleModal() {
        this.articleModal = document.getElementById('articleModal');
        if (!this.articleModal) return;

        // Close modal handlers
        this.articleModal.addEventListener('click', (e) => {
            if (e.target === this.articleModal || e.target.closest('.modal-close')) {
                this.closeArticleModal();
            }
        });

        // Keyboard handlers
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.articleModal.classList.contains('active')) {
                this.closeArticleModal();
            }
        });

        // Table of contents navigation
        this.setupTableOfContents();
    }

    /**
     * Open article modal for reading
     */
    openArticleModal(articleId) {
        const article = this.articles.find(a => a.id === articleId);
        if (!article || !this.articleModal) return;

        const modalContent = this.articleModal.querySelector('.modal-content');
        modalContent.innerHTML = this.createArticleModalHTML(article);

        this.articleModal.classList.add('active');
        document.body.style.overflow = 'hidden';

        // Track article view
        this.trackArticleView(articleId);

        // Initialize article interactions
        this.initializeArticleInteractions(articleId);

        // Generate table of contents
        this.generateTableOfContents(article.content);

        // Update reading progress
        this.updateReadingProgress();
    }

    /**
     * Close article modal
     */
    closeArticleModal() {
        if (this.articleModal) {
            this.articleModal.classList.remove('active');
            document.body.style.overflow = '';
            
            // Reset reading progress
            this.readingProgress = 0;
            this.updateReadingProgressBar();
        }
    }

    /**
     * Create modal HTML for full article
     */
    createArticleModalHTML(article) {
        const readingTime = this.calculateReadingTime(article.content);
        const publishedDate = new Date(article.publishedAt).toLocaleDateString();

        return `
            <div class="modal-header article-header">
                <button class="modal-close" aria-label="Close article">
                    <i class="fas fa-times"></i>
                </button>
                
                <div class="article-meta-large">
                    <div class="article-categories">
                        ${article.categories.map(cat => 
                            `<span class="article-category">${cat}</span>`
                        ).join('')}
                    </div>
                    
                    <h1 class="article-title-large">${article.title}</h1>
                    
                    <div class="article-meta-info">
                        <div class="author-info">
                            <div class="author-avatar-large">
                                <img src="${article.author?.avatar || '/images/default-avatar.jpg'}" alt="${article.author?.name || 'Author'}">
                            </div>
                            <div class="author-details">
                                <span class="author-name">${article.author?.name || 'Imran Shiundu'}</span>
                                <span class="publish-date">Published on ${publishedDate}</span>
                            </div>
                        </div>
                        
                        <div class="article-stats">
                            <span class="stat">
                                <i class="fas fa-clock"></i>
                                ${readingTime} min read
                            </span>
                            <span class="stat">
                                <i class="fas fa-eye"></i>
                                ${article.views || 0} views
                            </span>
                            <span class="stat">
                                <i class="far fa-heart"></i>
                                ${article.likes || 0} likes
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="modal-body article-body">
                <div class="article-sidebar">
                    <div class="table-of-contents" id="tableOfContents">
                        <h4>Table of Contents</h4>
                        <nav class="toc-nav" id="tocNav"></nav>
                    </div>
                    
                    <div class="reading-progress-sidebar">
                        <div class="progress-circle">
                            <svg width="60" height="60" viewBox="0 0 60 60">
                                <circle class="progress-bg" cx="30" cy="30" r="28"></circle>
                                <circle class="progress-bar" cx="30" cy="30" r="28" 
                                        stroke-dasharray="176" stroke-dashoffset="176"></circle>
                            </svg>
                            <span class="progress-text" id="progressText">0%</span>
                        </div>
                        <span>Reading Progress</span>
                    </div>
                </div>
                
                <div class="article-content-large">
                    <div class="article-image-large">
                        <img src="${article.image}" alt="${article.title}">
                    </div>
                    
                    <div class="article-text" id="articleText">
                        ${article.content}
                    </div>
                    
                    <div class="article-tags-large">
                        ${article.tags.map(tag => 
                            `<span class="article-tag">${tag}</span>`
                        ).join('')}
                    </div>
                    
                    <div class="article-actions-large">
                        <button class="btn btn-primary like-article-btn" data-article="${article.id}">
                            <i class="far fa-heart"></i>
                            Like Article
                        </button>
                        <button class="btn btn-secondary bookmark-article-btn" data-article="${article.id}">
                            <i class="far fa-bookmark"></i>
                            Bookmark
                        </button>
                        <button class="btn btn-outline share-article-btn" data-article="${article.id}">
                            <i class="fas fa-share-alt"></i>
                            Share
                        </button>
                    </div>
                    
                    <div class="article-comments-section" id="commentsSection">
                        <h3>Comments</h3>
                        <div class="comments-loading">
                            <i class="fas fa-spinner fa-spin"></i>
                            Loading comments...
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="modal-footer article-footer-large">
                <div class="navigation-articles">
                    <button class="btn btn-outline prev-article" id="prevArticle">
                        <i class="fas fa-arrow-left"></i> Previous Article
                    </button>
                    <button class="btn btn-outline next-article" id="nextArticle">
                        Next Article <i class="fas fa-arrow-right"></i>
                    </button>
                </div>
            </div>
        `;
    }

    /**
     * Initialize reading progress tracking
     */
    initializeReadingProgress() {
        this.readingProgress = 0;
        this.updateReadingProgressBar();
    }

    /**
     * Update reading progress
     */
    updateReadingProgress() {
        const articleText = document.getElementById('articleText');
        if (!articleText) return;

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const progress = this.calculateElementProgress(entry.target);
                    this.readingProgress = Math.max(this.readingProgress, progress);
                    this.updateReadingProgressBar();
                }
            });
        }, {
            threshold: [0, 0.25, 0.5, 0.75, 1]
        });

        // Observe all content sections
        const sections = articleText.querySelectorAll('h2, h3, p');
        sections.forEach(section => observer.observe(section));
    }

    /**
     * Calculate element progress in viewport
     */
    calculateElementProgress(element) {
        const rect = element.getBoundingClientRect();
        const windowHeight = window.innerHeight;
        const elementTop = rect.top;
        const elementHeight = rect.height;

        if (elementTop + elementHeight < 0) return 100; // Element passed
        if (elementTop > windowHeight) return 0; // Element not yet reached

        const visible = Math.min(windowHeight, elementTop + elementHeight) - Math.max(0, elementTop);
        return (visible / elementHeight) * 100;
    }

    /**
     * Update reading progress bar
     */
    updateReadingProgressBar() {
        const progressBar = document.querySelector('.progress-bar');
        const progressText = document.getElementById('progressText');
        
        if (progressBar) {
            const circumference = 176; // 2 * π * 28
            const offset = circumference - (this.readingProgress / 100) * circumference;
            progressBar.style.strokeDashoffset = offset;
        }
        
        if (progressText) {
            progressText.textContent = `${Math.round(this.readingProgress)}%`;
        }
    }

    /**
     * Generate table of contents from article content
     */
    generateTableOfContents(content) {
        const tocNav = document.getElementById('tocNav');
        if (!tocNav) return;

        // Extract headings from content (simplified)
        const tempDiv = document.createElement('div');
        tempDiv.innerHTML = content;
        const headings = tempDiv.querySelectorAll('h2, h3');

        if (headings.length === 0) {
            tocNav.innerHTML = '<p>No headings available</p>';
            return;
        }

        const tocHTML = Array.from(headings).map((heading, index) => {
            const level = heading.tagName.toLowerCase();
            const text = heading.textContent;
            const id = `section-${index}`;
            
            heading.id = id; // Add ID to heading for linking
            
            return `
                <a href="#${id}" class="toc-item toc-${level}" data-section="${id}">
                    ${text}
                </a>
            `;
        }).join('');

        tocNav.innerHTML = tocHTML;
    }

    /**
     * Setup table of contents navigation
     */
    setupTableOfContents() {
        document.addEventListener('click', (e) => {
            if (e.target.closest('.toc-item')) {
                e.preventDefault();
                const targetId = e.target.closest('.toc-item').getAttribute('href').substring(1);
                const targetElement = document.getElementById(targetId);
                
                if (targetElement) {
                    targetElement.scrollIntoView({ behavior: 'smooth' });
                }
            }
        });
    }

    /**
     * Track article view
     */
    async trackArticleView(articleId) {
        try {
            await window.apiClient.trackArticleView(articleId);
        } catch (error) {
            console.error('Error tracking article view:', error);
        }
    }

    /**
     * Handle article likes
     */
    async handleArticleLike(articleId) {
        try {
            const result = await window.apiClient.likeArticle(articleId);
            this.updateArticleLikes(articleId, result.likes);
        } catch (error) {
            console.error('Error liking article:', error);
        }
    }

    /**
     * Update article likes count
     */
    updateArticleLikes(articleId, newCount) {
        // Update in articles array
        const article = this.articles.find(a => a.id === articleId);
        if (article) {
            article.likes = newCount;
        }

        // Update in modal if open
        const likeBtn = document.querySelector(`[data-article="${articleId}"] .like-article-btn`);
        if (likeBtn) {
            likeBtn.innerHTML = `<i class="far fa-heart"></i> ${newCount} Likes`;
        }

        // Update in card if visible
        const cardLike = document.querySelector(`.article-card[data-article-id="${articleId}"] .engagement-count`);
        if (cardLike) {
            cardLike.textContent = newCount;
        }
    }

    /**
     * Setup search functionality
     */
    setupSearchFunctionality() {
        const searchInput = document.getElementById('blogSearch');
        if (searchInput) {
            let searchTimeout;
            searchInput.addEventListener('input', (e) => {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    this.searchArticles(e.target.value);
                }, 300);
            });
        }

        // Clear search
        const clearSearch = document.getElementById('clearBlogSearch');
        if (clearSearch) {
            clearSearch.addEventListener('click', () => {
                if (searchInput) searchInput.value = '';
                this.currentSearch = '';
                this.applyFilters();
            });
        }
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
                this.filterArticles(filter);
            }
            
            // Sort buttons
            if (e.target.closest('.sort-btn')) {
                const sortBtn = e.target.closest('.sort-btn');
                const sort = sortBtn.dataset.sort;
                this.sortArticles(sort);
            }
            
            // Read article
            if (e.target.closest('.read-article') || e.target.closest('.article-title-link')) {
                const articleId = e.target.closest('[data-article]').dataset.article;
                this.openArticleModal(articleId);
            }
            
            // Like article
            if (e.target.closest('.like-btn') || e.target.closest('.like-article-btn')) {
                const articleId = e.target.closest('[data-article]').dataset.article;
                this.handleArticleLike(articleId);
            }
            
            // Share article
            if (e.target.closest('.share-btn') || e.target.closest('.share-article-btn')) {
                const articleId = e.target.closest('[data-article]').dataset.article;
                this.shareArticle(articleId);
            }
        });

        // Article card interactions
        document.addEventListener('mouseenter', (e) => {
            if (e.target.closest('.article-card')) {
                const card = e.target.closest('.article-card');
                this.animateArticleCard(card, 'enter');
            }
        }, true);

        document.addEventListener('mouseleave', (e) => {
            if (e.target.closest('.article-card')) {
                const card = e.target.closest('.article-card');
                this.animateArticleCard(card, 'leave');
            }
        }, true);
    }

    /**
     * Share article
     */
    shareArticle(articleId) {
        const article = this.articles.find(a => a.id === articleId);
        if (!article) return;

        const shareUrl = window.location.origin + '/blog/article/' + articleId;
        const shareText = `Check out this article: ${article.title}`;

        if (navigator.share) {
            navigator.share({
                title: article.title,
                text: shareText,
                url: shareUrl,
            });
        } else {
            // Fallback: copy to clipboard
            navigator.clipboard.writeText(shareUrl).then(() => {
                this.showNotification('Link copied to clipboard!');
            });
        }
    }

    /**
     * Show notification
     */
    showNotification(message) {
        const notification = document.createElement('div');
        notification.className = 'notification';
        notification.textContent = message;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.remove();
        }, 3000);
    }

    /**
     * Animate article cards on interaction
     */
    animateArticleCard(card, action) {
        if (action === 'enter') {
            card.style.transform = 'translateY(-5px)';
            card.style.boxShadow = '0 15px 30px rgba(0,0,0,0.1)';
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

        this.observeArticleCards(observer);
    }

    /**
     * Observe article cards for animation
     */
    observeArticleCards(observer) {
        const cards = document.querySelectorAll('.article-card');
        cards.forEach(card => {
            card.classList.add('will-animate');
            observer.observe(card);
        });
    }

    /**
     * Initialize article animations
     */
    initializeArticleAnimations() {
        const cards = document.querySelectorAll('.article-card');
        cards.forEach((card, index) => {
            card.style.animationDelay = `${index * 0.1}s`;
        });
    }

    /**
     * Initialize article interactions in modal
     */
    initializeArticleInteractions(articleId) {
        // Add any specific interactions for the opened article
        this.setupCodeHighlighting();
        this.setupImageZoom();
    }

    /**
     * Setup code syntax highlighting
     */
    setupCodeHighlighting() {
        // This would integrate with a syntax highlighter like Prism.js
        const codeBlocks = document.querySelectorAll('pre code');
        codeBlocks.forEach(block => {
            block.classList.add('language-javascript'); // Default language
        });
    }

    /**
     * Setup image zoom functionality
     */
    setupImageZoom() {
        const images = document.querySelectorAll('.article-content-large img');
        images.forEach(img => {
            img.style.cursor = 'zoom-in';
            img.addEventListener('click', () => {
                img.classList.toggle('zoomed');
            });
        });
    }

    /**
     * Show loading state
     */
    showLoadingState() {
        this.isLoading = true;
        const grid = document.getElementById('articlesGrid');
        if (grid) {
            grid.innerHTML = `
                <div class="loading-skeleton article-card-skeleton"></div>
                <div class="loading-skeleton article-card-skeleton"></div>
                <div class="loading-skeleton article-card-skeleton"></div>
                <div class="loading-skeleton article-card-skeleton"></div>
            `;
        }
    }

    /**
     * Hide loading state
     */
    hideLoadingState() {
        this.isLoading = false;
        document.querySelectorAll('.article-card-skeleton').forEach(skeleton => {
            skeleton.remove();
        });
    }

    /**
     * Show error state
     */
    showErrorState() {
        const grid = document.getElementById('articlesGrid');
        if (grid) {
            grid.innerHTML = `
                <div class="error-state">
                    <i class="fas fa-exclamation-triangle"></i>
                    <h3>Unable to Load Articles</h3>
                    <p>Please check your connection and try again.</p>
                    <button class="btn btn-primary" onclick="blogManager.loadArticlesData()">
                        <i class="fas fa-redo"></i> Retry
                    </button>
                </div>
            `;
        }
    }

    /**
     * Get HTML for no articles state
     */
    getNoArticlesHTML() {
        return `
            <div class="no-articles">
                <i class="fas fa-search"></i>
                <h3>No Articles Found</h3>
                <p>Try adjusting your search or filter criteria.</p>
                <button class="btn btn-primary" onclick="blogManager.filterArticles('all')">
                    Show All Articles
                </button>
            </div>
        `;
    }
}

// Initialize Blog Manager when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.blogManager = new BlogManager();
});

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = BlogManager;
}