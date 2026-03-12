-- eanh.sql - Tạo cấu trúc database cho EAnh

-- Tạo database
CREATE DATABASE IF NOT EXISTS eanh_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE eanh_db;

-- Bảng users - Quản lý người dùng
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fullname VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255),
    avatar VARCHAR(500),
    google_id VARCHAR(255),
    role ENUM('user', 'admin') DEFAULT 'user',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    last_login DATETIME,
    INDEX idx_email (email),
    INDEX idx_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bảng vocabulary - Từ vựng
CREATE TABLE IF NOT EXISTS vocabulary (
    id INT AUTO_INCREMENT PRIMARY KEY,
    word VARCHAR(255) NOT NULL,
    pronunciation VARCHAR(255),
    meaning TEXT NOT NULL,
    example TEXT,
    level ENUM('A1', 'A2', 'B1', 'B2', 'C1', 'C2') DEFAULT 'A1',
    category VARCHAR(100),
    image_url VARCHAR(500),
    audio_url VARCHAR(500),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_level (level),
    INDEX idx_category (category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bảng grammar - Ngữ pháp
CREATE TABLE IF NOT EXISTS grammar (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    level ENUM('A1', 'A2', 'B1', 'B2', 'C1', 'C2') DEFAULT 'A1',
    category VARCHAR(100),
    examples TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_level (level),
    INDEX idx_category (category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bảng listening - Bài nghe
CREATE TABLE IF NOT EXISTS listening (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    content TEXT,
    audio_url VARCHAR(500) NOT NULL,
    transcript TEXT,
    level ENUM('A1', 'A2', 'B1', 'B2', 'C1', 'C2') DEFAULT 'A1',
    duration INT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_level (level)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bảng reading - Bài đọc
CREATE TABLE IF NOT EXISTS reading (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    level ENUM('A1', 'A2', 'B1', 'B2', 'C1', 'C2') DEFAULT 'A1',
    category VARCHAR(100),
    word_count INT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_level (level),
    INDEX idx_category (category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bảng writing - Bài viết
CREATE TABLE IF NOT EXISTS writing (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    topic TEXT NOT NULL,
    level ENUM('A1', 'A2', 'B1', 'B2', 'C1', 'C2') DEFAULT 'A1',
    sample_essay TEXT,
    guidelines TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_level (level)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bảng speaking - Bài nói
CREATE TABLE IF NOT EXISTS speaking (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    topic TEXT NOT NULL,
    level ENUM('A1', 'A2', 'B1', 'B2', 'C1', 'C2') DEFAULT 'A1',
    sample_answer TEXT,
    keywords TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_level (level)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bảng tests - Bài thi
CREATE TABLE IF NOT EXISTS tests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    type ENUM('vocabulary', 'grammar', 'listening', 'reading', 'writing', 'speaking', 'full') DEFAULT 'full',
    level ENUM('A1', 'A2', 'B1', 'B2', 'C1', 'C2') DEFAULT 'A1',
    duration INT DEFAULT 60,
    total_questions INT DEFAULT 40,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_type (type),
    INDEX idx_level (level)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bảng questions - Câu hỏi
CREATE TABLE IF NOT EXISTS questions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    test_id INT NOT NULL,
    question_text TEXT NOT NULL,
    question_type ENUM('multiple_choice', 'fill_blank', 'essay', 'speaking') DEFAULT 'multiple_choice',
    option_a TEXT,
    option_b TEXT,
    option_c TEXT,
    option_d TEXT,
    correct_answer VARCHAR(10),
    explanation TEXT,
    points INT DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (test_id) REFERENCES tests(id) ON DELETE CASCADE,
    INDEX idx_test_id (test_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bảng user_progress - Tiến độ học tập
CREATE TABLE IF NOT EXISTS user_progress (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    content_type ENUM('vocabulary', 'grammar', 'listening', 'reading', 'writing', 'speaking') NOT NULL,
    content_id INT NOT NULL,
    completed BOOLEAN DEFAULT FALSE,
    score DECIMAL(5,2),
    time_spent INT DEFAULT 0,
    completed_at DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_content_type (content_type),
    INDEX idx_completed (completed)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bảng test_results - Kết quả thi
CREATE TABLE IF NOT EXISTS test_results (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    test_id INT NOT NULL,
    score DECIMAL(5,2) NOT NULL,
    total_questions INT NOT NULL,
    correct_answers INT NOT NULL,
    time_spent INT DEFAULT 0,
    answers TEXT,
    completed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (test_id) REFERENCES tests(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_test_id (test_id),
    INDEX idx_score (score)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bảng user_vocabulary - Từ vựng đã học
CREATE TABLE IF NOT EXISTS user_vocabulary (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    vocabulary_id INT NOT NULL,
    mastered BOOLEAN DEFAULT FALSE,
    review_count INT DEFAULT 0,
    last_reviewed DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (vocabulary_id) REFERENCES vocabulary(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_vocab (user_id, vocabulary_id),
    INDEX idx_user_id (user_id),
    INDEX idx_mastered (mastered)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Thêm tài khoản admin mặc định
INSERT INTO users (fullname, email, password, role, created_at) 
VALUES ('Administrator', 'admin@eanh.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', NOW())
ON DUPLICATE KEY UPDATE role = 'admin';
-- Password mặc định: password

-- Thêm dữ liệu mẫu từ vựng
INSERT INTO vocabulary (word, pronunciation, meaning, example, level, category) VALUES
('Hello', '/həˈloʊ/', 'Xin chào', 'Hello, how are you?', 'A1', 'Greetings'),
('Goodbye', '/ɡʊdˈbaɪ/', 'Tạm biệt', 'Goodbye, see you later!', 'A1', 'Greetings'),
('Thank you', '/θæŋk juː/', 'Cảm ơn', 'Thank you very much!', 'A1', 'Greetings'),
('Beautiful', '/ˈbjuːtɪfl/', 'Đẹp', 'She is beautiful.', 'A2', 'Adjectives'),
('Important', '/ɪmˈpɔːrtnt/', 'Quan trọng', 'This is important.', 'A2', 'Adjectives');

-- Thêm dữ liệu mẫu ngữ pháp
INSERT INTO grammar (title, content, level, category, examples) VALUES
('Present Simple Tense', 'Thì hiện tại đơn dùng để diễn tả thói quen, sự thật hiển nhiên.', 'A1', 'Tenses', 'I play football every day.'),
('Past Simple Tense', 'Thì quá khứ đơn dùng để diễn tả hành động đã xảy ra trong quá khứ.', 'A1', 'Tenses', 'I played football yesterday.'),
('Present Continuous', 'Thì hiện tại tiếp diễn dùng để diễn tả hành động đang xảy ra.', 'A2', 'Tenses', 'I am playing football now.');

-- Thêm dữ liệu mẫu bài test
INSERT INTO tests (title, description, type, level, duration, total_questions) VALUES
('Test A1 - Vocabulary', 'Kiểm tra từ vựng cơ bản trình độ A1', 'vocabulary', 'A1', 30, 20),
('Test A2 - Grammar', 'Kiểm tra ngữ pháp trình độ A2', 'grammar', 'A2', 45, 30),
('Full Test B1', 'Bài thi đầy đủ trình độ B1', 'full', 'B1', 120, 100);