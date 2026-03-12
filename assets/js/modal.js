// modal.js - Xử lý modal chung cho toàn bộ ứng dụng

class ModalManager {
    constructor(modalId) {
        this.modal = document.getElementById(modalId);
        this.init();
    }

    init() {
        if (!this.modal) return;

        // Close button
        const closeBtn = this.modal.querySelector('.close, .close-quiz');
        if (closeBtn) {
            closeBtn.onclick = () => this.close();
        }

        // Click outside to close
        this.modal.onclick = (e) => {
            if (e.target === this.modal) {
                this.close();
            }
        };

        // ESC key to close
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.isOpen()) {
                this.close();
            }
        });
    }

    open() {
        if (!this.modal) return;
        this.modal.classList.add('show');
        this.modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
        
        // Focus trap
        const focusableElements = this.modal.querySelectorAll(
            'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
        );
        if (focusableElements.length > 0) {
            focusableElements[0].focus();
        }
    }

    close() {
        if (!this.modal) return;
        this.modal.classList.remove('show');
        this.modal.style.display = 'none';
        document.body.style.overflow = 'auto';
    }

    isOpen() {
        return this.modal && this.modal.classList.contains('show');
    }

    setContent(html) {
        const content = this.modal.querySelector('[id$="Detail"], [id$="Content"]');
        if (content) {
            content.innerHTML = html;
        }
    }
}

// Auto-init all modals on page load
document.addEventListener('DOMContentLoaded', function() {
    // Find all modals and initialize them
    const modals = document.querySelectorAll('.modal');
    modals.forEach(modal => {
        new ModalManager(modal.id);
    });
});

// Global modal helper functions
window.openModal = function(modalId) {
    const manager = new ModalManager(modalId);
    manager.open();
};

window.closeModal = function(modalId) {
    const manager = new ModalManager(modalId);
    manager.close();
};

// Fix for touch devices
if ('ontouchstart' in window) {
    document.addEventListener('touchstart', function() {}, true);
}

// Prevent scroll on iOS when modal is open
let scrollPosition = 0;

function lockScroll() {
    scrollPosition = window.pageYOffset;
    document.body.style.overflow = 'hidden';
    document.body.style.position = 'fixed';
    document.body.style.top = `-${scrollPosition}px`;
    document.body.style.width = '100%';
}

function unlockScroll() {
    document.body.style.removeProperty('overflow');
    document.body.style.removeProperty('position');
    document.body.style.removeProperty('top');
    document.body.style.removeProperty('width');
    window.scrollTo(0, scrollPosition);
}

// Export for use in other scripts
window.ModalManager = ModalManager;
window.lockScroll = lockScroll;
window.unlockScroll = unlockScroll;