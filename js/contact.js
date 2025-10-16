// contact.js - Contact Form & Live Chat Functionality
class ContactManager {
    constructor() {
        this.currentStep = 1;
        this.formData = {
            basic: {},
            project: {},
            message: {}
        };
        this.isSubmitting = false;
        
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.loadContactContent();
        this.initializeLiveChat();
        this.setupFormValidation();
        console.log('Contact Manager Initialized');
    }

    // ===== CONTENT LOADING =====
    async loadContactContent() {
        try {
            const [contactInfo, contactSettings] = await Promise.all([
                window.portfolioAPI.getContactInfo(),
                window.portfolioAPI.getContactSettings()
            ]);

            this.updateContactContent(contactInfo);
            this.updateFormSettings(contactSettings);
        } catch (error) {
            console.error('Error loading contact content:', error);
            this.handleContentLoadError();
        }
    }

    updateContactContent(data) {
        // Update contact information
        const elements = {
            'contact-subtitle': data.subtitle,
            'contact-description': data.description,
            'contact-email': data.email,
            'contact-phone': data.phone,
            'contact-location': data.location,
            'contact-availability': data.availability
        };

        Object.keys(elements).forEach(id => {
            const element = document.getElementById(id);
            if (element) {
                element.textContent = elements[id];
            }
        });

        // Update social links
        this.updateSocialLinks(data.socialLinks);
    }

    updateSocialLinks(socialLinks) {
        const container = document.getElementById('contact-social-links');
        if (!container) return;

        container.innerHTML = socialLinks.map(link => `
            <a href="${link.url}" class="social-link" aria-label="${link.platform}" target="_blank">
                <i class="fab fa-${link.platform}" aria-hidden="true"></i>
            </a>
        `).join('');
    }

    updateFormSettings(settings) {
        // Update form options based on settings
        this.updateProjectTypeOptions(settings.projectTypes);
        this.updateBudgetOptions(settings.budgetRanges);
    }

    updateProjectTypeOptions(types) {
        const select = document.getElementById('projectType');
        if (!select) return;

        // Clear existing options except the first one
        while (select.children.length > 1) {
            select.removeChild(select.lastChild);
        }

        // Add new options
        types.forEach(type => {
            const option = document.createElement('option');
            option.value = type.value;
            option.textContent = type.label;
            select.appendChild(option);
        });
    }

    updateBudgetOptions(ranges) {
        const select = document.getElementById('budgetRange');
        if (!select) return;

        while (select.children.length > 1) {
            select.removeChild(select.lastChild);
        }

        ranges.forEach(range => {
            const option = document.createElement('option');
            option.value = range.value;
            option.textContent = range.label;
            select.appendChild(option);
        });
    }

    // ===== FORM MANAGEMENT =====
    setupEventListeners() {
        // Form step navigation
        document.addEventListener('click', (e) => {
            if (e.target.matches('.btn-next')) {
                e.preventDefault();
                const nextStep = parseInt(e.target.dataset.next);
                this.goToStep(nextStep);
            }

            if (e.target.matches('.btn-prev')) {
                e.preventDefault();
                const prevStep = parseInt(e.target.dataset.prev);
                this.goToStep(prevStep);
            }
        });

        // Form submission
        const contactForm = document.getElementById('contactForm');
        if (contactForm) {
            contactForm.addEventListener('submit', (e) => this.handleFormSubmit(e));
        }

        // Real-time validation
        this.setupRealTimeValidation();

        // Input changes for review section
        this.setupReviewUpdates();
    }

    setupFormValidation() {
        // Add custom validation rules
        this.setupCustomValidation();
    }

    setupCustomValidation() {
        const phoneInput = document.getElementById('phone');
        if (phoneInput) {
            phoneInput.addEventListener('input', (e) => {
                this.validatePhoneNumber(e.target);
            });
        }

        const emailInput = document.getElementById('email');
        if (emailInput) {
            emailInput.addEventListener('blur', (e) => {
                this.validateEmail(e.target);
            });
        }
    }

    validatePhoneNumber(input) {
        const value = input.value.trim();
        const errorElement = document.getElementById('phoneError');
        
        if (!value) {
            this.clearError(input, errorElement);
            return true;
        }

        // Basic phone validation (adjust for international format)
        const phoneRegex = /^[+]?[0-9\s\-\(\)]{10,}$/;
        if (!phoneRegex.test(value)) {
            this.showError(input, errorElement, 'Please enter a valid phone number');
            return false;
        }

        this.clearError(input, errorElement);
        return true;
    }

    validateEmail(input) {
        const value = input.value.trim();
        const errorElement = document.getElementById('emailError');
        
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(value)) {
            this.showError(input, errorElement, 'Please enter a valid email address');
            return false;
        }

        this.clearError(input, errorElement);
        return true;
    }

    setupRealTimeValidation() {
        const inputs = document.querySelectorAll('#contactForm input, #contactForm select, #contactForm textarea');
        
        inputs.forEach(input => {
            input.addEventListener('blur', () => {
                this.validateField(input);
            });

            input.addEventListener('input', () => {
                this.clearFieldError(input);
            });
        });
    }

    validateField(input) {
        const value = input.value.trim();
        const isRequired = input.hasAttribute('required');
        const errorElement = document.getElementById(`${input.id}Error`);

        if (isRequired && !value) {
            this.showError(input, errorElement, 'This field is required');
            return false;
        }

        // Field-specific validation
        switch (input.type) {
            case 'email':
                return this.validateEmail(input);
            case 'tel':
                return this.validatePhoneNumber(input);
            default:
                this.clearError(input, errorElement);
                return true;
        }
    }

    showError(input, errorElement, message) {
        input.classList.add('error');
        if (errorElement) {
            errorElement.textContent = message;
            errorElement.classList.add('show');
        }
    }

    clearError(input, errorElement) {
        input.classList.remove('error');
        if (errorElement) {
            errorElement.textContent = '';
            errorElement.classList.remove('show');
        }
    }

    clearFieldError(input) {
        const errorElement = document.getElementById(`${input.id}Error`);
        this.clearError(input, errorElement);
    }

    // ===== FORM STEPS =====
    goToStep(step) {
        // Validate current step before proceeding
        if (step > this.currentStep && !this.validateStep(this.currentStep)) {
            return;
        }

        // Save current step data
        this.saveStepData(this.currentStep);

        // Update UI
        this.hideStep(this.currentStep);
        this.showStep(step);
        this.updateStepIndicator(step);

        this.currentStep = step;

        // Special handling for review step
        if (step === 3) {
            this.updateReviewSection();
        }
    }

    validateStep(step) {
        const stepElement = document.querySelector(`[data-step="${step}"]`);
        const requiredInputs = stepElement.querySelectorAll('[required]');
        
        let isValid = true;

        requiredInputs.forEach(input => {
            if (!this.validateField(input)) {
                isValid = false;
            }
        });

        return isValid;
    }

    saveStepData(step) {
        const stepElement = document.querySelector(`[data-step="${step}"]`);
        const inputs = stepElement.querySelectorAll('input, select, textarea');
        
        this.formData[this.getStepDataKey(step)] = {};

        inputs.forEach(input => {
            if (input.name) {
                this.formData[this.getStepDataKey(step)][input.name] = input.value;
            }
        });
    }

    getStepDataKey(step) {
        const keys = { 1: 'basic', 2: 'project', 3: 'message' };
        return keys[step] || 'basic';
    }

    hideStep(step) {
        const stepElement = document.querySelector(`[data-step="${step}"]`);
        if (stepElement) {
            stepElement.classList.remove('active');
        }
    }

    showStep(step) {
        const stepElement = document.querySelector(`[data-step="${step}"]`);
        if (stepElement) {
            stepElement.classList.add('active');
        }
    }

    updateStepIndicator(step) {
        const indicators = document.querySelectorAll('.step-indicator');
        
        indicators.forEach((indicator, index) => {
            const stepNumber = index + 1;
            
            indicator.classList.remove('active', 'completed');
            
            if (stepNumber === step) {
                indicator.classList.add('active');
            } else if (stepNumber < step) {
                indicator.classList.add('completed');
            }
        });
    }

    // ===== REVIEW SECTION =====
    setupReviewUpdates() {
        // Update review when inputs change
        const inputs = document.querySelectorAll('#contactForm input, #contactForm select');
        inputs.forEach(input => {
            input.addEventListener('change', () => {
                if (this.currentStep === 3) {
                    this.updateReviewSection();
                }
            });
        });
    }

    updateReviewSection() {
        const reviewContent = document.getElementById('reviewContent');
        if (!reviewContent) return;

        // Combine all form data
        const allData = { ...this.formData.basic, ...this.formData.project };
        
        reviewContent.innerHTML = Object.keys(allData)
            .filter(key => allData[key]) // Only show non-empty values
            .map(key => `
                <div class="review-item">
                    <span class="review-label">${this.formatLabel(key)}</span>
                    <span class="review-value">${this.formatValue(key, allData[key])}</span>
                </div>
            `).join('');
    }

    formatLabel(key) {
        const labels = {
            'fullName': 'Full Name',
            'email': 'Email Address',
            'phone': 'Phone Number',
            'projectType': 'Project Type',
            'budgetRange': 'Budget Range',
            'timeline': 'Timeline'
        };
        return labels[key] || key;
    }

    formatValue(key, value) {
        if (key === 'budgetRange') {
            const budgets = {
                'under-500': 'Under $500',
                '500-2000': '$500 - $2,000',
                '2000-5000': '$2,000 - $5,000',
                '5000-10000': '$5,000 - $10,000',
                'over-10000': 'Over $10,000',
                'to-discuss': 'To be discussed'
            };
            return budgets[value] || value;
        }

        if (key === 'timeline') {
            const timelines = {
                'urgent': 'Urgent (1-2 weeks)',
                'short-term': 'Short-term (1 month)',
                'medium-term': 'Medium-term (1-3 months)',
                'long-term': 'Long-term (3+ months)',
                'flexible': 'Flexible'
            };
            return timelines[value] || value;
        }

        return value;
    }

    // ===== FORM SUBMISSION =====
    async handleFormSubmit(e) {
        e.preventDefault();

        if (this.isSubmitting) return;

        // Validate all steps
        if (!this.validateStep(3)) {
            this.showFormStatus('Please fix the errors before submitting.', 'error');
            return;
        }

        this.isSubmitting = true;
        this.setSubmitButtonState(true);

        try {
            // Save final step data
            this.saveStepData(3);

            // Prepare submission data
            const submissionData = {
                ...this.formData.basic,
                ...this.formData.project,
                message: this.formData.message.message,
                newsletter: document.getElementById('newsletter').checked,
                submittedAt: new Date().toISOString(),
                userAgent: navigator.userAgent,
                referrer: document.referrer
            };

            // Submit to API
            const response = await window.portfolioAPI.submitContactForm(submissionData);

            this.showFormStatus('Thank you! Your message has been sent successfully.', 'success');
            this.resetForm();

        } catch (error) {
            console.error('Form submission error:', error);
            this.showFormStatus('Sorry, there was an error sending your message. Please try again.', 'error');
        } finally {
            this.isSubmitting = false;
            this.setSubmitButtonState(false);
        }
    }

    setSubmitButtonState(loading) {
        const submitButton = document.querySelector('.btn-submit');
        if (!submitButton) return;

        if (loading) {
            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
            submitButton.classList.add('loading');
        } else {
            submitButton.disabled = false;
            submitButton.innerHTML = '<i class="fas fa-paper-plane"></i> Send Message';
            submitButton.classList.remove('loading');
        }
    }

    showFormStatus(message, type) {
        const statusElement = document.getElementById('formStatus');
        if (!statusElement) return;

        statusElement.textContent = message;
        statusElement.className = `form-status ${type} show`;

        // Auto-hide success messages
        if (type === 'success') {
            setTimeout(() => {
                statusElement.classList.remove('show');
            }, 5000);
        }
    }

    resetForm() {
        // Reset form data
        this.formData = { basic: {}, project: {}, message: {} };
        
        // Reset form elements
        const form = document.getElementById('contactForm');
        if (form) {
            form.reset();
        }

        // Reset to first step
        this.goToStep(1);

        // Clear errors
        document.querySelectorAll('.form-error').forEach(error => {
            error.classList.remove('show');
        });

        document.querySelectorAll('.form-input, .form-select, .form-textarea').forEach(input => {
            input.classList.remove('error');
        });
    }

    // ===== LIVE CHAT =====
    initializeLiveChat() {
        this.setupChatEventListeners();
        this.loadChatHistory();
    }

    setupChatEventListeners() {
        const chatToggle = document.getElementById('chatToggle');
        const chatClose = document.getElementById('chatClose');
        const chatSend = document.getElementById('chatSend');
        const chatInput = document.getElementById('chatInput');

        if (chatToggle) {
            chatToggle.addEventListener('click', () => this.toggleChat());
        }

        if (chatClose) {
            chatClose.addEventListener('click', () => this.closeChat());
        }

        if (chatSend && chatInput) {
            chatSend.addEventListener('click', () => this.sendChatMessage());
            chatInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    this.sendChatMessage();
                }
            });
        }
    }

    toggleChat() {
        const chatContainer = document.getElementById('chatContainer');
        const chatToggle = document.getElementById('chatToggle');

        if (chatContainer && chatToggle) {
            const isHidden = chatContainer.hidden;
            
            chatContainer.hidden = !isHidden;
            chatToggle.setAttribute('aria-expanded', !isHidden);
            
            if (!isHidden) {
                chatInput.focus();
            }
        }
    }

    closeChat() {
        const chatContainer = document.getElementById('chatContainer');
        const chatToggle = document.getElementById('chatToggle');

        if (chatContainer && chatToggle) {
            chatContainer.hidden = true;
            chatToggle.setAttribute('aria-expanded', 'false');
        }
    }

    async sendChatMessage() {
        const chatInput = document.getElementById('chatInput');
        const message = chatInput.value.trim();

        if (!message) return;

        // Add user message to chat
        this.addChatMessage(message, 'user');
        chatInput.value = '';

        // Simulate bot response (replace with actual API call)
        setTimeout(() => {
            this.addBotResponse(message);
        }, 1000);
    }

    addChatMessage(message, type) {
        const chatMessages = document.getElementById('chatMessages');
        if (!chatMessages) return;

        const messageElement = document.createElement('div');
        messageElement.className = `chat-message ${type}-message`;
        messageElement.innerHTML = `
            <div class="message-content">
                <p>${this.escapeHtml(message)}</p>
            </div>
            <div class="message-time">${this.getCurrentTime()}</div>
        `;

        chatMessages.appendChild(messageElement);
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }

    addBotResponse(userMessage) {
        const responses = {
            'hello': 'Hello! How can I help you today?',
            'hi': 'Hi there! What can I do for you?',
            'project': 'I can help you discuss your project requirements. Would you like to schedule a call or fill out our contact form?',
            'price': 'Pricing depends on project scope and requirements. Could you tell me more about what you need?',
            'time': 'Project timelines vary based on complexity. Simple projects take 1-2 weeks, while complex ones can take several months.',
            'default': 'Thanks for your message! Imran typically responds within 24 hours. For detailed discussions, please use the contact form above.'
        };

        const lowerMessage = userMessage.toLowerCase();
        let response = responses.default;

        if (lowerMessage.includes('hello') || lowerMessage.includes('hi')) {
            response = responses.hello;
        } else if (lowerMessage.includes('project')) {
            response = responses.project;
        } else if (lowerMessage.includes('price') || lowerMessage.includes('cost')) {
            response = responses.price;
        } else if (lowerMessage.includes('time') || lowerMessage.includes('schedule')) {
            response = responses.time;
        }

        this.addChatMessage(response, 'bot');
    }

    loadChatHistory() {
        // Load chat history from localStorage
        const history = localStorage.getItem('chatHistory');
        if (history) {
            try {
                const messages = JSON.parse(history);
                messages.forEach(msg => {
                    this.addChatMessage(msg.message, msg.type);
                });
            } catch (error) {
                console.error('Error loading chat history:', error);
            }
        }
    }

    saveChatHistory() {
        const chatMessages = document.getElementById('chatMessages');
        if (!chatMessages) return;

        const messages = Array.from(chatMessages.querySelectorAll('.chat-message')).map(msg => ({
            message: msg.querySelector('.message-content p').textContent,
            type: msg.classList.contains('user-message') ? 'user' : 'bot',
            time: msg.querySelector('.message-time').textContent
        }));

        localStorage.setItem('chatHistory', JSON.stringify(messages));
    }

    // ===== UTILITY FUNCTIONS =====
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    getCurrentTime() {
        return new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    }

    handleContentLoadError() {
        // Show fallback content or error state
        const loadingElements = document.querySelectorAll('.loading-skeleton');
        loadingElements.forEach(element => {
            element.innerHTML = 'Content unavailable';
            element.classList.remove('loading-skeleton');
        });
    }
}

// ===== INITIALIZATION =====
document.addEventListener('DOMContentLoaded', () => {
    if (document.getElementById('contactForm')) {
        window.contactManager = new ContactManager();
    }
});