// Main.js - JavaScript chung cho toàn bộ ứng dụng

// API Base URL
const API_BASE_URL = window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1'
    ? 'http://localhost/EAnh/backend'
    : '/backend';

// Hàm gọi API chung
async function apiCall(endpoint, method = 'GET', data = null) {
    const token = localStorage.getItem('token');
    const options = {
        method: method,
        headers: {}
    };

    if (token) {
        options.headers['Authorization'] = 'Bearer ' + token;
    }

    if (data) {
        if (data instanceof FormData) {
            options.body = data;
        } else {
            options.headers['Content-Type'] = 'application/json';
            options.body = JSON.stringify(data);
        }
    }

    try {
        const response = await fetch(API_BASE_URL + endpoint, options);
        return await response.json();
    } catch (error) {
        console.error('API Error:', error);
        throw error;
    }
}

// Hiển thị loading
function showLoading(element) {
    if (element) {
        element.innerHTML = '<div class="loading">Đang tải...</div>';
    }
}

// Ẩn loading
function hideLoading(element) {
    if (element) {
        const loading = element.querySelector('.loading');
        if (loading) loading.remove();
    }
}

// Format thời gian
function formatTime(seconds) {
    const hours = Math.floor(seconds / 3600);
    const minutes = Math.floor((seconds % 3600) / 60);
    const secs = seconds % 60;
    
    if (hours > 0) {
        return `${hours}:${String(minutes).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
    }
    return `${minutes}:${String(secs).padStart(2, '0')}`;
}

// Format ngày tháng
function formatDate(dateString) {
    const date = new Date(dateString);
    const day = String(date.getDate()).padStart(2, '0');
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const year = date.getFullYear();
    return `${day}/${month}/${year}`;
}

// Hiển thị thông báo toast
function showToast(message, type = 'info') {
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.textContent = message;
    
    const colors = {
        success: '#10b981',
        error: '#ef4444',
        warning: '#f59e0b',
        info: '#6366f1'
    };
    
    toast.style.cssText = `
        position: fixed;
        bottom: 20px;
        right: 20px;
        background: ${colors[type] || colors.info};
        color: white;
        padding: 1rem 1.5rem;
        border-radius: 10px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.3);
        z-index: 9999;
        animation: slideIn 0.3s ease;
    `;
    
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// Thêm CSS cho animation
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from {
            transform: translateX(400px);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOut {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(400px);
            opacity: 0;
        }
    }
    
    .loading {
        display: flex;
        justify-content: center;
        align-items: center;
        padding: 2rem;
        color: var(--text-muted);
    }
    
    .loading::after {
        content: '';
        width: 30px;
        height: 30px;
        margin-left: 10px;
        border: 3px solid var(--border);
        border-top-color: var(--primary);
        border-radius: 50%;
        animation: spin 0.8s linear infinite;
    }
    
    @keyframes spin {
        to { transform: rotate(360deg); }
    }
`;
document.head.appendChild(style);

// Xử lý smooth scroll
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

// Lưu tiến độ học tập
async function saveProgress(type, data) {
    const user = JSON.parse(localStorage.getItem('user') || '{}');
    if (!user.id) return;

    try {
        const progressData = {
            userId: user.id,
            type: type,
            data: JSON.stringify(data),
            timestamp: new Date().toISOString()
        };

        const response = await apiCall('/api/result.php?action=saveProgress', 'POST', progressData);
        return response;
    } catch (error) {
        console.error('Error saving progress:', error);
    }
}

// Lấy tiến độ học tập
async function getProgress(type) {
    const user = JSON.parse(localStorage.getItem('user') || '{}');
    if (!user.id) return null;

    try {
        const response = await apiCall(`/api/result.php?action=getProgress&userId=${user.id}&type=${type}`);
        return response;
    } catch (error) {
        console.error('Error getting progress:', error);
        return null;
    }
}

// Kiểm tra quyền truy cập trang
function checkPageAccess() {
    const currentPage = window.location.pathname;
    const publicPages = ['index.html', 'login.html', 'register.html', 'admin-login.html', '/'];
    
    const isPublicPage = publicPages.some(page => currentPage.includes(page) || currentPage === '/');
    
    if (!isPublicPage) {
        const user = localStorage.getItem('user');
        if (!user) {
            window.location.href = '/login.html';
        }
    }
}

// Gọi kiểm tra khi trang load
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', checkPageAccess);
} else {
    checkPageAccess();
}

// Export functions for global use
window.apiCall = apiCall;
window.showToast = showToast;
window.showLoading = showLoading;
window.hideLoading = hideLoading;
window.formatTime = formatTime;
window.formatDate = formatDate;
window.saveProgress = saveProgress;
window.getProgress = getProgress;