// CẤU HÌNH API (Trỏ về XAMPP)
const API_URL = 'http://localhost/EANH/backend/auth'; 

function showAlert(message, type = 'danger') {
    const alertBox = document.getElementById('alertBox');
    if (!alertBox) return;
    alertBox.innerHTML = `<div class="alert alert-${type}" style="padding:10px; margin-bottom:10px; background:${type==='success'?'#d4edda':'#f8d7da'}; color:${type==='success'?'#155724':'#721c24'}; border-radius:5px; text-align:center;">${message}</div>`;
    setTimeout(() => { alertBox.innerHTML = ''; }, 3000);
}

// XỬ LÝ ĐĂNG KÝ
const registerForm = document.getElementById('registerForm');
if (registerForm) {
    registerForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const btn = document.getElementById('registerBtn');
        const originalText = btn.innerHTML;
        btn.innerHTML = '⏳...'; btn.disabled = true;

        const fullName = document.getElementById('fullName').value;
        const email = document.getElementById('email').value;
        const password = document.getElementById('password').value;
        const confirmPassword = document.getElementById('confirmPassword').value;

        try {
            if (password !== confirmPassword) throw new Error('Mật khẩu không khớp!');
            
            const response = await fetch(`${API_URL}/register.php`, {
                method: 'POST', 
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ full_name: fullName, email, password, confirm_password: confirmPassword })
            });
            const data = await response.json();

            if (data.success) {
                showAlert('Đăng ký thành công!', 'success');
                // Lưu tạm thông tin để vào dashboard luôn không cần login lại
                localStorage.setItem('user_info', JSON.stringify(data.user));
                setTimeout(() => window.location.href = 'dashboard.html', 1000);
            } else { throw new Error(data.message); }
        } catch (err) { showAlert(err.message || 'Lỗi kết nối', 'danger'); } 
        finally { btn.innerHTML = originalText; btn.disabled = false; }
    });
}

// XỬ LÝ ĐĂNG NHẬP
const loginForm = document.getElementById('loginForm');
if (loginForm) {
    loginForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const btn = document.getElementById('loginBtn');
        const originalText = btn.innerHTML;
        btn.innerHTML = '⏳...'; btn.disabled = true;

        const email = document.getElementById('email').value;
        const password = document.getElementById('password').value;

        try {
            const response = await fetch(`${API_URL}/login.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ email, password })
            });
            
            // Xử lý lỗi nếu server trả về HTML (lỗi PHP)
            const text = await response.text();
            let data;
            try { data = JSON.parse(text); } catch(e) { throw new Error('Lỗi Server: ' + text); }

            if (data.success) {
                showAlert('Đăng nhập thành công!', 'success');
                
                // QUAN TRỌNG: Lưu thông tin vào LocalStorage để Dashboard đọc được
                localStorage.setItem('user_info', JSON.stringify(data.user));
                
                setTimeout(() => { 
                    // Chuyển hướng bằng đường dẫn tương đối
                    window.location.href = 'dashboard.html'; 
                }, 1000);
            } else { throw new Error(data.message); }
        } catch (err) { showAlert(err.message, 'danger'); } 
        finally { btn.innerHTML = originalText; btn.disabled = false; }
    });
}