// main.js - Core JavaScript Functionality
class PortfolioApp {
    constructor() {
        this.currentTheme = localStorage.getItem('theme') || 'light';
        this.apiBaseUrl = 'https://api.imranshiundu.eu'; // Update with your backend URL
        this.isMobile = window.innerWidth < 768;
        this.scrollPosition = 0;
        
        this.init();
    }

    // Initialize the application
    init() {
        this.setupEventListeners();
        this.applyTheme(this.currentTheme);
        this.initializeNavigation();
        this.initializeAnimations();
        this.loadSiteContent();
        this.setupPerformanceMonitoring();
        
        console.log('Portfolio App Initialized');
    }

    // ===== THEME MANAGEMENT =====
    setupEventListeners() {
        // Theme toggle
        const themeToggle = document.getElementById('themeToggle');
        if (themeToggle) {
            themeToggle.addEventListener('click', () => this.toggleTheme());
        }

        // Mobile navigation
        const navToggle = document.getElementById('navToggle');
        if (navToggle) {
            navToggle.addEventListener('click', () => this.toggleMobileNavigation());
        }

        // Smooth scrolling for anchor links
        document.addEventListener('click', (e) => {
            if (e.target.matches('a[href^="#"]') && !e.target.matches('a[href="#"]')) {
                e.preventDefault();
                this.smoothScroll(e.target.getAttribute('href'));
            }
        });

        // Close mobile menu when clicking on links
        document.addEventListener('click', (e) => {
            if (e.target.matches('.nav-link') && this.isMobile) {
                this.closeMobileNavigation();
            }
        });

        // Window resize handling
        window.addEventListener('resize', this.debounce(() => {
            this.isMobile = window.innerWidth < 768;
            if (!this.isMobile) {
                this.closeMobileNavigation();
            }
        }, 250));

        // Scroll events
        window.addEventListener('scroll', this.throttle(() => {
            this.handleScroll();
        }, 100));

        // Load more content when near bottom
        window.addEventListener('scroll', this.throttle(() => {
            this.checkInfiniteScroll();
        }, 250));
    }

    toggleTheme() {
        this.currentTheme = this.currentTheme === 'light' ? 'dark' : 'light';
        this.applyTheme(this.currentTheme);
        localStorage.setItem('theme', this.currentTheme);
    }

    applyTheme(theme) {
        document.documentElement.setAttribute('data-theme', theme);
        document.body.classList.toggle('dark-mode', theme === 'dark');
        
        // Update theme toggle icon
        const themeToggle = document.getElementById('themeToggle');
        if (themeToggle) {
            const moonIcon = themeToggle.querySelector('.fa-moon');
            const sunIcon = themeToggle.querySelector('.fa-sun');
            
            if (theme === 'dark') {
                moonIcon.style.display = 'none';
                sunIcon.style.display = 'block';
            } else {
                moonIcon.style.display = 'block';
                sunIcon.style.display = 'none';
            }
        }
    }

    // ===== NAVIGATION =====
    initializeNavigation() {
        this.updateActiveNavigation();
        this.setupScrollSpy();
    }

    toggleMobileNavigation() {
        const navMenu = document.querySelector('.nav-menu');
        const navToggle = document.getElementById('navToggle');
        
        if (navMenu && navToggle) {
            navMenu.classList.toggle('active');
            navToggle.classList.toggle('active');
            navToggle.setAttribute('aria-expanded', 
                navToggle.getAttribute('aria-expanded') === 'true' ? 'false' : 'true'
            );
        }
    }

    closeMobileNavigation() {
        const navMenu = document.querySelector('.nav-menu');
        const navToggle = document.getElementById('navToggle');
        
        if (navMenu && navToggle) {
            navMenu.classList.remove('active');
            navToggle.classList.remove('active');
            navToggle.setAttribute('aria-expanded', 'false');
        }
    }

    smoothScroll(targetId) {
        const targetElement = document.querySelector(targetId);
        if (targetElement) {
            const offsetTop = targetElement.getBoundingClientRect().top + window.pageYOffset - 80;
            
            window.scrollTo({
                top: offsetTop,
                behavior: 'smooth'
            });
        }
    }

    updateActiveNavigation() {
        const sections = document.querySelectorAll('section[id]');
        const navLinks = document.querySelectorAll('.nav-link');
        
        let currentSection = '';
        
        sections.forEach(section => {
            const sectionTop = section.offsetTop - 100;
            const sectionHeight = section.clientHeight;
            
            if (window.scrollY >= sectionTop && window.scrollY < sectionTop + sectionHeight) {
                currentSection = section.getAttribute('id');
            }
        });

        navLinks.forEach(link => {
            link.classList.remove('active');
            if (link.getAttribute('href') === `#${currentSection}` || 
                link.getAttribute('href').includes(currentSection)) {
                link.classList.add('active');
            }
        });
    }

    setupScrollSpy() {
        const observerOptions = {
            root: null,
            rootMargin: '0px',
            threshold: 0.5
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const id = entry.target.getAttribute('id');
                    this.setActiveNavLink(id);
                }
            });
        }, observerOptions);

        // Observe all sections with IDs
        document.querySelectorAll('section[id]').forEach(section => {
            observer.observe(section);
        });
    }

    setActiveNavLink(sectionId) {
        const navLinks = document.querySelectorAll('.nav-link');
        
        navLinks.forEach(link => {
            link.classList.remove('active');
            const href = link.getAttribute('href');
            
            if (href === `#${sectionId}` || href.includes(sectionId)) {
                link.classList.add('active');
            }
        });
    }

    // ===== ANIMATIONS =====
    initializeAnimations() {
        this.setupScrollAnimations();
        this.setupParticleBackground();
        this.initializeCounters();
        this.setupSkillBarAnimations();
    }

    setupScrollAnimations() {
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');
                    
                    // Stagger children animations
                    const staggerItems = entry.target.querySelectorAll('.stagger-item');
                    staggerItems.forEach((item, index) => {
                        setTimeout(() => {
                            item.classList.add('visible');
                        }, index * 100);
                    });
                }
            });
        }, observerOptions);

        // Observe elements for scroll animations
        document.querySelectorAll('.section-entrance, .fade-in-up, .fade-in-left, .fade-in-right').forEach(el => {
            observer.observe(el);
        });
    }

    setupParticleBackground() {
        if (typeof particlesJS !== 'undefined' && document.getElementById('particles-js')) {
            particlesJS('particles-js', {
                particles: {
                    number: {
                        value: 30,
                        density: {
                            enable: true,
                            value_area: 800
                        }
                    },
                    color: {
                        value: this.currentTheme === 'dark' ? '#3b82f6' : '#0D47A1'
                    },
                    shape: {
                        type: 'circle'
                    },
                    opacity: {
                        value: 0.5,
                        random: true
                    },
                    size: {
                        value: 3,
                        random: true
                    },
                    line_linked: {
                        enable: true,
                        distance: 150,
                        color: this.currentTheme === 'dark' ? '#3b82f6' : '#0D47A1',
                        opacity: 0.4,
                        width: 1
                    },
                    move: {
                        enable: true,
                        speed: 2,
                        direction: 'none',
                        random: true,
                        straight: false,
                        out_mode: 'out',
                        bounce: false
                    }
                },
                interactivity: {
                    detect_on: 'canvas',
                    events: {
                        onhover: {
                            enable: true,
                            mode: 'grab'
                        },
                        onclick: {
                            enable: true,
                            mode: 'push'
                        }
                    },
                    modes: {
                        grab: {
                            distance: 140,
                            line_linked: {
                                opacity: 1
                            }
                        },
                        push: {
                            particles_nb: 4
                        }
                    }
                },
                retina_detect: true
            });
        }
    }

    initializeCounters() {
        const counters = document.querySelectorAll('.stat-number[data-count]');
        
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    this.animateCounter(entry.target);
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.5 });

        counters.forEach(counter => observer.observe(counter));
    }

    animateCounter(element) {
        const target = parseInt(element.getAttribute('data-count'));
        const duration = 2000; // 2 seconds
        const step = target / (duration / 16); // 60fps
        let current = 0;

        const timer = setInterval(() => {
            current += step;
            if (current >= target) {
                element.textContent = target;
                clearInterval(timer);
            } else {
                element.textContent = Math.floor(current);
            }
        }, 16);
    }

    setupSkillBarAnimations() {
        const skillBars = document.querySelectorAll('.skill-progress');
        
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const skillBar = entry.target;
                    const level = skillBar.getAttribute('data-level');
                    
                    setTimeout(() => {
                        skillBar.style.width = `${level}%`;
                        skillBar.classList.add('animate');
                    }, 200);
                    
                    observer.unobserve(skillBar);
                }
            });
        }, { threshold: 0.5 });

        skillBars.forEach(bar => observer.observe(bar));
    }

    // ===== API INTEGRATION =====
    async loadSiteContent() {
        try {
            await Promise.all([
                this.loadHeroContent(),
                this.loadAboutContent(),
                this.loadSkills(),
                this.loadExperience(),
                this.loadFeaturedProjects(),
                this.loadFooterContent()
            ]);
        } catch (error) {
            console.error('Error loading site content:', error);
            this.handleContentLoadError();
        }
    }

    async loadHeroContent() {
        try {
            // const response = await fetch(`${this.apiBaseUrl}/api/site-content/hero`);
            // const data = await response.json();
            
            // Mock data - replace with actual API call
            const data = {
                name: "Imran Shiundu",
                title: "Full Stack Developer",
                description: "Building digital solutions that bridge innovation and impact. Specializing in full-stack development, startup architecture, and community-driven technology.",
                stats: {
                    projects: 15,
                    startups: 3,
                    experience: 4
                }
            };

            this.updateHeroContent(data);
        } catch (error) {
            console.error('Error loading hero content:', error);
        }
    }

    updateHeroContent(data) {
        const heroName = document.getElementById('hero-name');
        const heroTitle = document.getElementById('hero-title');
        const heroDescription = document.getElementById('hero-description');
        const projectsCount = document.getElementById('projects-count');
        const startupsCount = document.getElementById('startups-count');
        const experienceYears = document.getElementById('experience-years');

        if (heroName) heroName.textContent = data.name;
        if (heroTitle) heroTitle.textContent = data.title;
        if (heroDescription) heroDescription.textContent = data.description;
        
        // Animate counters
        if (projectsCount) {
            projectsCount.setAttribute('data-count', data.stats.projects);
            this.animateCounter(projectsCount);
        }
        if (startupsCount) {
            startupsCount.setAttribute('data-count', data.stats.startups);
            this.animateCounter(startupsCount);
        }
        if (experienceYears) {
            experienceYears.setAttribute('data-count', data.stats.experience);
            this.animateCounter(experienceYears);
        }
    }

    async loadAboutContent() {
        try {
            // const response = await fetch(`${this.apiBaseUrl}/api/site-content/about`);
            // const data = await response.json();
            
            // Mock data
            const data = {
                title: "Building the Future",
                description: "As a full-stack developer and startup founder, I specialize in transforming complex ideas into scalable, user-friendly digital solutions. My approach combines technical excellence with strategic thinking.",
                education: {
                    title: "kood/Jõhvi Scholar",
                    description: "Currently advancing my skills in software engineering through project-based learning at kood/Jõhvi, Estonia."
                }
            };

            this.updateAboutContent(data);
        } catch (error) {
            console.error('Error loading about content:', error);
        }
    }

    updateAboutContent(data) {
        const aboutTitle = document.getElementById('about-main-title');
        const aboutDescription = document.getElementById('about-description');
        const educationTitle = document.getElementById('education-title');
        const educationDescription = document.getElementById('education-description');

        if (aboutTitle) aboutTitle.textContent = data.title;
        if (aboutDescription) aboutDescription.textContent = data.description;
        if (educationTitle) educationTitle.textContent = data.education.title;
        if (educationDescription) educationDescription.textContent = data.education.description;
    }

    async loadSkills() {
        try {
            // const response = await fetch(`${this.apiBaseUrl}/api/skills`);
            // const skills = await response.json();
            
            // Mock data
            const skills = [
                {
                    category: "Frontend Technologies",
                    skills: [
                        { name: "HTML5/CSS3", level: 95 },
                        { name: "JavaScript ES6+", level: 90 }
                    ]
                },
                {
                    category: "Backend Technologies",
                    skills: [
                        { name: "PHP/Laravel", level: 88 },
                        { name: "Java/SpringBoot", level: 85 }
                    ]
                },
                {
                    category: "Currently Mastering",
                    learning: ["Python", "C++", "Kotlin", "System Design"]
                }
            ];

            this.updateSkills(skills);
        } catch (error) {
            console.error('Error loading skills:', error);
        }
    }

    updateSkills(skills) {
        const skillsContainer = document.getElementById('skills-container');
        if (!skillsContainer) return;

        skillsContainer.innerHTML = skills.map(category => `
            <div class="skills-category">
                <h4 class="skills-title">
                    <i class="fas fa-${this.getSkillCategoryIcon(category.category)}" aria-hidden="true"></i>
                    ${category.category}
                </h4>
                ${category.skills ? category.skills.map(skill => `
                    <div class="skill-item">
                        <span class="skill-name">${skill.name}</span>
                        <div class="skill-bar">
                            <div class="skill-progress" data-level="${skill.level}"></div>
                        </div>
                        <span class="skill-percent">${skill.level}%</span>
                    </div>
                `).join('') : ''}
                ${category.learning ? `
                    <div class="learning-tags">
                        ${category.learning.map(tag => `<span class="learning-tag">${tag}</span>`).join('')}
                    </div>
                ` : ''}
            </div>
        `).join('');

        // Re-initialize skill bar animations
        this.setupSkillBarAnimations();
    }

    getSkillCategoryIcon(category) {
        const icons = {
            'Frontend Technologies': 'palette',
            'Backend Technologies': 'server',
            'Currently Mastering': 'rocket'
        };
        return icons[category] || 'code';
    }

    async loadExperience() {
        try {
            // const response = await fetch(`${this.apiBaseUrl}/api/experience`);
            // const experience = await response.json();
            
            // Mock data
            const experience = [
                {
                    date: "2025 - Present",
                    title: "kood/Jõhvi - Estonia",
                    role: "Software Development & Engineering",
                    description: "Scholarship recipient in a project-based learning environment focused on peer collaboration and real-world problem solving. Mastering Java, PHP, Python, C++, and Kotlin.",
                    icon: "graduation-cap"
                },
                {
                    date: "2024 - Present",
                    title: "Brick4Bits • Wealthlink • Tijona Ltd",
                    role: "CEO & Founder / CTO",
                    description: "Leading multiple startups: connecting startups with investors (Brick4Bits), SACCO management solutions (Wealthlink), and technical strategy leadership (Tijona).",
                    icon: "briefcase"
                }
            ];

            this.updateExperience(experience);
        } catch (error) {
            console.error('Error loading experience:', error);
        }
    }

    updateExperience(experience) {
        const timeline = document.getElementById('experience-timeline');
        if (!timeline) return;

        timeline.innerHTML = experience.map(item => `
            <div class="timeline-item">
                <div class="timeline-marker">
                    <i class="fas fa-${item.icon}" aria-hidden="true"></i>
                </div>
                <div class="timeline-content">
                    <div class="timeline-date">${item.date}</div>
                    <h3 class="timeline-title">${item.title}</h3>
                    <div class="timeline-role">${item.role}</div>
                    <p class="timeline-description">${item.description}</p>
                </div>
            </div>
        `).join('');
    }

    async loadFeaturedProjects() {
        try {
            // const response = await fetch(`${this.apiBaseUrl}/api/projects/featured`);
            // const projects = await response.json();
            
            // Mock data
            const projects = [
                {
                    title: "Melora Music Player",
                    description: "Sophisticated web-based music player with playlist management, audio visualization, and responsive design.",
                    technologies: ["JavaScript", "Web Audio API"],
                    features: ["Audio Visualization", "Playlist Management"],
                    status: "completed"
                }
            ];

            this.updateFeaturedProjects(projects);
        } catch (error) {
            console.error('Error loading featured projects:', error);
        }
    }

    updateFeaturedProjects(projects) {
        const projectsGrid = document.getElementById('featured-projects-grid');
        if (!projectsGrid) return;

        projectsGrid.innerHTML = projects.map(project => `
            <article class="project-card">
                <div class="project-image">
                    <div class="project-overlay">
                        <div class="project-actions">
                            <a href="#" class="project-link" aria-label="Live demo of ${project.title}">
                                <i class="fas fa-external-link-alt" aria-hidden="true"></i>
                            </a>
                            <a href="#" class="project-link" aria-label="View source code for ${project.title}">
                                <i class="fab fa-github" aria-hidden="true"></i>
                            </a>
                        </div>
                    </div>
                    <div class="project-tech">
                        ${project.technologies.map(tech => `<span class="tech-tag">${tech}</span>`).join('')}
                    </div>
                </div>
                <div class="project-content">
                    <h3 class="project-title">${project.title}</h3>
                    <p class="project-description">${project.description}</p>
                    <div class="project-features">
                        ${project.features.map(feature => `
                            <span class="feature">
                                <i class="fas fa-${this.getFeatureIcon(feature)}" aria-hidden="true"></i>
                                ${feature}
                            </span>
                        `).join('')}
                    </div>
                </div>
            </article>
        `).join('');
    }

    getFeatureIcon(feature) {
        const icons = {
            'Audio Visualization': 'wave-square',
            'Playlist Management': 'list',
            'Movie Database': 'database',
            'Category Filtering': 'filter',
            'Financial Tracking': 'chart-line',
            'Member Management': 'users'
        };
        return icons[feature] || 'check';
    }

    async loadFooterContent() {
        try {
            // const response = await fetch(`${this.apiBaseUrl}/api/site-content/footer`);
            // const data = await response.json();
            
            // Mock data
            const data = {
                description: "Full Stack Developer building the digital future, one line at a time.",
                socialLinks: [
                    { platform: 'linkedin', url: 'https://linkedin.com/in/imranshiundu' },
                    { platform: 'github', url: 'https://github.com/imranshiundu' },
                    { platform: 'email', url: 'mailto:imranshiundu@gmail.com' }
                ]
            };

            this.updateFooterContent(data);
        } catch (error) {
            console.error('Error loading footer content:', error);
        }
    }

    updateFooterContent(data) {
        const footerDescription = document.getElementById('footer-description');
        const socialLinks = document.getElementById('social-links');

        if (footerDescription) footerDescription.textContent = data.description;
        
        if (socialLinks) {
            socialLinks.innerHTML = data.socialLinks.map(link => `
                <a href="${link.url}" class="social-link" aria-label="${link.platform}">
                    <i class="fab fa-${link.platform}" aria-hidden="true"></i>
                </a>
            `).join('');
        }
    }

    handleContentLoadError() {
        // Show error state or fallback content
        const loadingElements = document.querySelectorAll('.loading-skeleton');
        loadingElements.forEach(element => {
            element.innerHTML = 'Content unavailable';
            element.classList.remove('loading-skeleton');
        });
    }

    // ===== PERFORMANCE & UTILITIES =====
    setupPerformanceMonitoring() {
        // Monitor Core Web Vitals
        if ('PerformanceObserver' in window) {
            const observer = new PerformanceObserver((list) => {
                list.getEntries().forEach((entry) => {
                    console.log(`${entry.name}: ${entry.value}`);
                });
            });

            observer.observe({ entryTypes: ['largest-contentful-paint', 'first-input', 'layout-shift'] });
        }

        // Preload critical resources
        this.preloadCriticalResources();
    }

    preloadCriticalResources() {
        const criticalResources = [
            '/css/style.css',
            '/js/api.js',
            '/images/hero-bg.jpg'
        ];

        criticalResources.forEach(resource => {
            const link = document.createElement('link');
            link.rel = 'preload';
            link.href = resource;
            link.as = resource.includes('.css') ? 'style' : resource.includes('.js') ? 'script' : 'image';
            document.head.appendChild(link);
        });
    }

    handleScroll() {
        this.scrollPosition = window.scrollY;
        
        // Update navigation
        this.updateActiveNavigation();
        
        // Parallax effects
        this.applyParallax();
        
        // Show/hide back to top button
        this.toggleBackToTop();
    }

    applyParallax() {
        const parallaxElements = document.querySelectorAll('.parallax');
        parallaxElements.forEach(element => {
            const speed = element.getAttribute('data-speed') || 0.5;
            const yPos = -(this.scrollPosition * speed);
            element.style.transform = `translateY(${yPos}px)`;
        });
    }

    toggleBackToTop() {
        // Implementation for back to top button
    }

    checkInfiniteScroll() {
        const scrollPosition = window.scrollY + window.innerHeight;
        const documentHeight = document.documentElement.scrollHeight;
        
        if (scrollPosition >= documentHeight - 500) {
            this.loadMoreContent();
        }
    }

    loadMoreContent() {
        // Implementation for infinite scroll
    }

    // ===== UTILITY FUNCTIONS =====
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
}

// ===== INITIALIZATION =====
document.addEventListener('DOMContentLoaded', () => {
    // Initialize the application
    window.portfolioApp = new PortfolioApp();
    
    // Service Worker Registration (for PWA)
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('/sw.js')
            .then(registration => {
                console.log('SW registered: ', registration);
            })
            .catch(registrationError => {
                console.log('SW registration failed: ', registrationError);
            });
    }
});

// ===== GLOBAL UTILITIES =====
// Export for use in other modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = PortfolioApp;
}