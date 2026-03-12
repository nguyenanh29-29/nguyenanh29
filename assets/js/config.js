// Auto-detect base path for both Live Server and localhost
const Config = {
    // Tự động phát hiện base URL
    getBasePath: function() {
        const path = window.location.pathname;
        
        // Nếu đang chạy từ Live Server (port 5500 hoặc không có EAnh trong path)
        if (window.location.port === '5500' || window.location.port === '5501' || !path.includes('/EAnh/')) {
            return '';
        }
        
        // Nếu đang chạy từ localhost/EAnh
        if (path.includes('/EAnh/')) {
            return '/EAnh';
        }
        
        return '';
    },
    
    // API base URL
    getApiUrl: function(endpoint) {
        return this.getBasePath() + '/backend/api/' + endpoint;
    },
    
    // Auth URL
    getAuthUrl: function(endpoint) {
        return this.getBasePath() + '/backend/auth/' + endpoint;
    },
    
    // Page URL
    getPageUrl: function(page) {
        return this.getBasePath() + '/' + page;
    },
    
    // Google OAuth
    getGoogleAuthUrl: function() {
        const clientId = 'YOUR_GOOGLE_CLIENT_ID.apps.googleusercontent.com';
        const redirectUri = window.location.origin + this.getBasePath() + '/backend/auth/google-login.php';
        const scope = 'email profile';
        
        return `https://accounts.google.com/o/oauth2/v2/auth?` +
            `client_id=${clientId}&` +
            `redirect_uri=${encodeURIComponent(redirectUri)}&` +
            `response_type=code&` +
            `scope=${encodeURIComponent(scope)}&` +
            `access_type=offline&` +
            `prompt=consent`;
    }
};

// Export cho global
window.Config = Config; 