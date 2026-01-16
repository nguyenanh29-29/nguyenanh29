<?php
require_once '../config/db.php';

// Handle Google OAuth callback
if (isset($_GET['code'])) {
    $code = $_GET['code'];
    
    // Exchange code for access token
    $tokenUrl = 'https://oauth2.googleapis.com/token';
    $tokenData = [
        'code' => $code,
        'client_id' => GOOGLE_CLIENT_ID,
        'client_secret' => GOOGLE_CLIENT_SECRET,
        'redirect_uri' => GOOGLE_REDIRECT_URI,
        'grant_type' => 'authorization_code'
    ];
    
    $ch = curl_init($tokenUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($tokenData));
    $response = curl_exec($ch);
    curl_close($ch);
    
    $tokenInfo = json_decode($response, true);
    
    if (isset($tokenInfo['access_token'])) {
        // Get user info from Google
        $userInfoUrl = 'https://www.googleapis.com/oauth2/v2/userinfo';
        $ch = curl_init($userInfoUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $tokenInfo['access_token']
        ]);
        $userInfoResponse = curl_exec($ch);
        curl_close($ch);
        
        $userInfo = json_decode($userInfoResponse, true);
        
        if (isset($userInfo['id'])) {
            try {
                // Check if user exists
                $stmt = $conn->prepare("SELECT * FROM users WHERE google_id = ? OR email = ?");
                $stmt->execute([$userInfo['id'], $userInfo['email']]);
                $user = $stmt->fetch();
                
                if ($user) {
                    // Update user info if needed
                    $stmt = $conn->prepare("UPDATE users SET google_id = ?, full_name = ?, avatar = ? WHERE id = ?");
                    $stmt->execute([
                        $userInfo['id'],
                        $userInfo['name'],
                        $userInfo['picture'],
                        $user['id']
                    ]);
                    $userId = $user['id'];
                    $role = $user['role'];
                } else {
                    // Create new user
                    $stmt = $conn->prepare("INSERT INTO users (email, google_id, full_name, avatar) VALUES (?, ?, ?, ?)");
                    $stmt->execute([
                        $userInfo['email'],
                        $userInfo['id'],
                        $userInfo['name'],
                        $userInfo['picture']
                    ]);
                    $userId = $conn->lastInsertId();
                    $role = 'user';
                }
                
                // Set session
                $_SESSION['user_id'] = $userId;
                $_SESSION['email'] = $userInfo['email'];
                $_SESSION['full_name'] = $userInfo['name'];
                $_SESSION['role'] = $role;
                $_SESSION['avatar'] = $userInfo['picture'];
                
                // Redirect to dashboard
                $redirectUrl = $role === 'admin' ? '../../admin-dashboard.html' : '../../dashboard.html';
                header('Location: ' . $redirectUrl);
                exit;
                
            } catch (PDOException $e) {
                die('Database error: ' . $e->getMessage());
            }
        }
    }
    
    // If error, redirect to login
    header('Location: ../../login.html?error=google_auth_failed');
    exit;
}

// If no code, redirect to login
header('Location: ../../login.html');
exit;
?>