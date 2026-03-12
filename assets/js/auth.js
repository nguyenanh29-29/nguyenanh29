// Auth.js - Xử lý đăng nhập và đăng ký

// Hàm hiển thị thông báo
function showMessage(message, type = 'error') {
    const messageDiv = document.getElementById(type + 'Message');
    if (messageDiv) {
        messageDiv.textContent = message;
        messageDiv.style.display = 'block';
        setTimeout(() => {
            messageDiv.style.display = 'none';
        }, 5000);
    } else {
        alert(message); // Backup nếu không tìm thấy thẻ div thông báo
    }
}

// Xử lý đăng ký bằng Form thường
const registerForm = document.getElementById('registerForm');
if (registerForm) {
    registerForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        const fullname = document.getElementById('fullname').value;
        const email = document.getElementById('email').value;
        const password = document.getElementById('password').value;
        const confirmPassword = document.getElementById('confirmPassword').value;
        
        if (password !== confirmPassword) {
            showMessage('Mật khẩu xác nhận không khớp!', 'error');
            return;
        }
        if (password.length < 6) {
            showMessage('Mật khẩu phải có ít nhất 6 ký tự!', 'error');
            return;
        }
        
        try {
            const formData = new FormData();
            formData.append('fullname', fullname);
            formData.append('email', email);
            formData.append('password', password);
            
            const response = await fetch('backend/auth/register.php', { method: 'POST', body: formData });
            const data = await response.json();
            
            if (data.success) {
                showMessage('Đăng ký thành công! Đang chuyển hướng...', 'success');
                setTimeout(() => { window.location.href = 'login.html'; }, 2000);
            } else {
                showMessage(data.message || 'Đăng ký thất bại!', 'error');
            }
        } catch (error) {
            showMessage('Lỗi kết nối Server. Vui lòng thử lại!', 'error');
            console.error(error);
        }
    });
}

// Xử lý đăng nhập bằng Form thường
const loginForm = document.getElementById('loginForm');
if (loginForm) {
    loginForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        const email = document.getElementById('email').value;
        const password = document.getElementById('password').value;
        
        try {
            const formData = new FormData();
            formData.append('email', email);
            formData.append('password', password);
            
            const response = await fetch('backend/auth/login.php', { method: 'POST', body: formData });
            const data = await response.json();
            
            if (data.success) {
                localStorage.setItem('user', JSON.stringify(data.user));
                localStorage.setItem('token', data.token);
                showMessage('Đăng nhập thành công!', 'success');
                setTimeout(() => { window.location.href = 'dashboard.html'; }, 1000);
            } else {
                showMessage(data.message || 'Đăng nhập thất bại!', 'error');
            }
        } catch (error) {
            showMessage('Lỗi kết nối Server!', 'error');
            console.error(error);
        }
    });
}

// ==========================================
// XỬ LÝ ĐĂNG NHẬP / ĐĂNG KÝ VỚI GOOGLE
// ==========================================
async function loginWithGoogle(email, fullname, avatar, google_id) {
    try {
        const formData = new FormData();
        formData.append('email', email);
        formData.append('fullname', fullname);
        formData.append('avatar', avatar);
        formData.append('google_id', google_id);
        
        const response = await fetch('backend/auth/google-login.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            localStorage.setItem('user', JSON.stringify(data.user));
            localStorage.setItem('token', data.token);
            
            showMessage(data.message, 'success'); // Hiển thị "Đăng nhập..." hoặc "Tạo tài khoản..."
            setTimeout(() => {
                window.location.href = 'dashboard.html';
            }, 1000);
        } else {
            showMessage(data.message || 'Đăng nhập Google thất bại!', 'error');
        }
    } catch (error) {
        showMessage('Lỗi kết nối Database!', 'error');
        console.error('Google Server Error:', error);
    }
}

// Kiểm tra authentication bảo vệ trang
function checkAuth() {
    const user = localStorage.getItem('user');
    if (!user) {
        window.location.href = 'login.html';
        return null;
    }
    return JSON.parse(user);
}

// Đăng xuất
function logout() {
    localStorage.removeItem('user');
    localStorage.removeItem('token');
    window.location.href = 'login.html';
}