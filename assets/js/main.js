// TRAGOS Main JavaScript File

document.addEventListener('DOMContentLoaded', function() {
    // Mobile menu toggle
    const mobileMenuToggle = document.querySelector('.mobile-menu-toggle');
    const navMenu = document.querySelector('.nav-menu');
    
    if (mobileMenuToggle && navMenu) {
        mobileMenuToggle.addEventListener('click', function() {
            navMenu.classList.toggle('active');
            this.querySelector('i').classList.toggle('fa-bars');
            this.querySelector('i').classList.toggle('fa-times');
        });
    }
    
    // Smooth scrolling for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
    
    // Form validation
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const requiredFields = form.querySelectorAll('[required]');
            let isValid = true;
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    field.classList.add('error');
                    isValid = false;
                } else {
                    field.classList.remove('error');
                }
            });
            
            if (!isValid) {
                e.preventDefault();
            }
        });
    });
    
    // Auto-hide alerts
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            setTimeout(() => {
                alert.remove();
            }, 300);
        }, 5000);
    });
    
    // Image lazy loading
    const images = document.querySelectorAll('img[data-src]');
    const imageObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                img.src = img.dataset.src;
                img.classList.remove('lazy');
                imageObserver.unobserve(img);
            }
        });
    });
    
    images.forEach(img => imageObserver.observe(img));
    
    // Chat functionality
    initializeChat();
    
    // Notification handling
    initializeNotifications();
    
    // Search functionality
    initializeSearch();
});

// Chat functionality
function initializeChat() {
    const chatContainer = document.querySelector('.chat-container');
    if (!chatContainer) return;
    
    const chatMessages = chatContainer.querySelector('.chat-messages');
    const chatForm = chatContainer.querySelector('.chat-form');
    const messageInput = chatContainer.querySelector('.message-input');
    
    if (chatForm) {
        chatForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const message = messageInput.value.trim();
            if (!message) return;
            
            // Add message to chat (this would normally send to server)
            addMessageToChat(message, 'current-user');
            messageInput.value = '';
            
            // Scroll to bottom
            chatMessages.scrollTop = chatMessages.scrollHeight;
        });
    }
    
    // Auto-refresh chat messages
    setInterval(refreshChatMessages, 5000);
}

function addMessageToChat(message, userClass) {
    const chatMessages = document.querySelector('.chat-messages');
    if (!chatMessages) return;
    
    const messageElement = document.createElement('div');
    messageElement.className = `chat-message ${userClass}`;
    messageElement.innerHTML = `
        <div class="message-content">
            <p>${escapeHtml(message)}</p>
            <span class="message-time">${new Date().toLocaleTimeString()}</span>
        </div>
    `;
    
    chatMessages.appendChild(messageElement);
    chatMessages.scrollTop = chatMessages.scrollHeight;
}

function refreshChatMessages() {
    // This would fetch new messages from the server
    // For now, we'll just check if there are new messages
    const groupId = document.querySelector('[data-group-id]')?.dataset.groupId;
    if (!groupId) return;
    
    // AJAX call to fetch new messages would go here
}

// Notification handling
function initializeNotifications() {
    // Mark notifications as read when viewed
    const notificationItems = document.querySelectorAll('.notification-item');
    notificationItems.forEach(item => {
        item.addEventListener('click', function() {
            const notificationId = this.dataset.notificationId;
            if (notificationId) {
                markNotificationAsRead(notificationId);
            }
        });
    });
    
    // Check for new notifications periodically
    setInterval(checkNewNotifications, 30000);
}

function markNotificationAsRead(notificationId) {
    fetch('api/mark-notification-read.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ notification_id: notificationId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateNotificationBadge();
        }
    })
    .catch(error => console.error('Error:', error));
}

function checkNewNotifications() {
    fetch('api/get-notifications.php')
    .then(response => response.json())
    .then(data => {
        if (data.unread_count > 0) {
            updateNotificationBadge(data.unread_count);
        }
    })
    .catch(error => console.error('Error:', error));
}

function updateNotificationBadge(count = 0) {
    const badge = document.querySelector('.notification-badge');
    if (badge) {
        if (count > 0) {
            badge.textContent = count;
            badge.style.display = 'block';
        } else {
            badge.style.display = 'none';
        }
    }
}

// Search functionality
function initializeSearch() {
    const searchInputs = document.querySelectorAll('.search-input');
    searchInputs.forEach(input => {
        let searchTimeout;
        
        input.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                performSearch(this.value);
            }, 300);
        });
    });
}

function performSearch(query) {
    if (query.length < 2) return;
    
    // This would perform live search
    console.log('Searching for:', query);
}

// Utility functions
function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    
    return text.replace(/[&<>"']/g, function(m) { return map[m]; });
}

function showToast(message, type = 'info') {
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.textContent = message;
    
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.classList.add('show');
    }, 100);
    
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => {
            document.body.removeChild(toast);
        }, 300);
    }, 3000);
}

function confirmAction(message, callback) {
    if (confirm(message)) {
        callback();
    }
}

// Loading states
function showLoading(element) {
    element.classList.add('loading');
    element.disabled = true;
}

function hideLoading(element) {
    element.classList.remove('loading');
    element.disabled = false;
}

// Form helpers
function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

function validatePassword(password) {
    return password.length >= 6;
}

// Form handling with AJAX
function handleFormSubmit(form, successCallback) {
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(form);
        const submitButton = form.querySelector('button[type="submit"]');
        
        if (submitButton) showLoading(submitButton);
        
        fetch(form.action, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                if (data.redirect) {
                    window.location.href = data.redirect;
                } else if (successCallback) {
                    successCallback(data);
                }
            } else {
                showToast(data.message || 'An error occurred', 'error');
            }
        })
        .catch(error => {
            showToast('An error occurred', 'error');
        })
        .finally(() => {
            if (submitButton) hideLoading(submitButton);
        });
    });
}

// Initialize AJAX form handling
document.addEventListener('DOMContentLoaded', function() {
    // Mobile menu toggle
    const mobileMenuToggle = document.querySelector('.mobile-menu-toggle');
    const navMenu = document.querySelector('.nav-menu');
    
    if (mobileMenuToggle && navMenu) {
        mobileMenuToggle.addEventListener('click', function() {
            navMenu.classList.toggle('active');
            this.querySelector('i').classList.toggle('fa-bars');
            this.querySelector('i').classList.toggle('fa-times');
        });
    }
    
    // Smooth scrolling for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
    
    // Form validation
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const requiredFields = form.querySelectorAll('[required]');
            let isValid = true;
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    field.classList.add('error');
                    isValid = false;
                } else {
                    field.classList.remove('error');
                }
            });
            
            if (!isValid) {
                e.preventDefault();
            }
        });
    });
    
    // Auto-hide alerts
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            setTimeout(() => {
                alert.remove();
            }, 300);
        }, 5000);
    });
    
    // Image lazy loading
    const images = document.querySelectorAll('img[data-src]');
    const imageObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                img.src = img.dataset.src;
                img.classList.remove('lazy');
                imageObserver.unobserve(img);
            }
        });
    });
    
    images.forEach(img => imageObserver.observe(img));
    
    // Chat functionality
    initializeChat();
    
    // Notification handling
    initializeNotifications();
    
    // Search functionality
    initializeSearch();
});

// Chat functionality
function initializeChat() {
    const chatContainer = document.querySelector('.chat-container');
    if (!chatContainer) return;
    
    const chatMessages = chatContainer.querySelector('.chat-messages');
    const chatForm = chatContainer.querySelector('.chat-form');
    const messageInput = chatContainer.querySelector('.message-input');
    
    if (chatForm) {
        chatForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const message = messageInput.value.trim();
            if (!message) return;
            
            // Add message to chat (this would normally send to server)
            addMessageToChat(message, 'current-user');
            messageInput.value = '';
            
            // Scroll to bottom
            chatMessages.scrollTop = chatMessages.scrollHeight;
        });
    }
    
    // Auto-refresh chat messages
    setInterval(refreshChatMessages, 5000);
}

function addMessageToChat(message, userClass) {
    const chatMessages = document.querySelector('.chat-messages');
    if (!chatMessages) return;
    
    const messageElement = document.createElement('div');
    messageElement.className = `chat-message ${userClass}`;
    messageElement.innerHTML = `
        <div class="message-content">
            <p>${escapeHtml(message)}</p>
            <span class="message-time">${new Date().toLocaleTimeString()}</span>
        </div>
    `;
    
    chatMessages.appendChild(messageElement);
    chatMessages.scrollTop = chatMessages.scrollHeight;
}

function refreshChatMessages() {
    // This would fetch new messages from the server
    // For now, we'll just check if there are new messages
    const groupId = document.querySelector('[data-group-id]')?.dataset.groupId;
    if (!groupId) return;
    
    // AJAX call to fetch new messages would go here
}

// Notification handling
function initializeNotifications() {
    // Mark notifications as read when viewed
    const notificationItems = document.querySelectorAll('.notification-item');
    notificationItems.forEach(item => {
        item.addEventListener('click', function() {
            const notificationId = this.dataset.notificationId;
            if (notificationId) {
                markNotificationAsRead(notificationId);
            }
        });
    });
    
    // Check for new notifications periodically
    setInterval(checkNewNotifications, 30000);
}

function markNotificationAsRead(notificationId) {
    fetch('api/mark-notification-read.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ notification_id: notificationId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateNotificationBadge();
        }
    })
    .catch(error => console.error('Error:', error));
}

function checkNewNotifications() {
    fetch('api/get-notifications.php')
    .then(response => response.json())
    .then(data => {
        if (data.unread_count > 0) {
            updateNotificationBadge(data.unread_count);
        }
    })
    .catch(error => console.error('Error:', error));
}

function updateNotificationBadge(count = 0) {
    const badge = document.querySelector('.notification-badge');
    if (badge) {
        if (count > 0) {
            badge.textContent = count;
            badge.style.display = 'block';
        } else {
            badge.style.display = 'none';
        }
    }
}

// Search functionality
function initializeSearch() {
    const searchInputs = document.querySelectorAll('.search-input');
    searchInputs.forEach(input => {
        let searchTimeout;
        
        input.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                performSearch(this.value);
            }, 300);
        });
    });
}

function performSearch(query) {
    if (query.length < 2) return;
    
    // This would perform live search
    console.log('Searching for:', query);
}

// Utility functions
function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    
    return text.replace(/[&<>"']/g, function(m) { return map[m]; });
}

function showToast(message, type = 'info') {
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.textContent = message;
    
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.classList.add('show');
    }, 100);
    
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => {
            document.body.removeChild(toast);
        }, 300);
    }, 3000);
}

function confirmAction(message, callback) {
    if (confirm(message)) {
        callback();
    }
}

// Loading states
function showLoading(element) {
    element.classList.add('loading');
    element.disabled = true;
}

function hideLoading(element) {
    element.classList.remove('loading');
    element.disabled = false;
}

// Form helpers
function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

function validatePassword(password) {
    return password.length >= 6;
}

// Export functions for use in other scripts
window.TRAGOS = {
    showToast,
    confirmAction,
    showLoading,
    hideLoading,
    validateEmail,
    validatePassword,
    escapeHtml
};

// Handle login form
const loginForm = document.querySelector('#loginForm');
if (loginForm) {
    handleFormSubmit(loginForm);
}

// Handle chat form
const chatForm = document.querySelector('#chatForm');
if (chatForm) {
    handleFormSubmit(chatForm, function(data) {
        // Update chat messages
        const chatMessages = document.querySelector('.chat-messages');
        if (chatMessages && data.message) {
            const messageHtml = `
                <div class="message-item user-message">
                    <div class="message-content">${escapeHtml(data.message)}</div>
                    <div class="message-time">Just now</div>
                </div>
            `;
            chatMessages.insertAdjacentHTML('beforeend', messageHtml);
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }
    });
}

// Handle group actions (join, leave, etc)
const groupActionForms = document.querySelectorAll('.group-action-form');
groupActionForms.forEach(form => {
    handleFormSubmit(form, function(data) {
        if (data.refresh) {
            window.location.reload();
        }
    });
});