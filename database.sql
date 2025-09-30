-- PostgreSQL schema

CREATE TABLE IF NOT EXISTS users (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(20) DEFAULT 'student' CHECK (role IN ('student', 'admin')),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS courses (
    id SERIAL PRIMARY KEY,
    course_code VARCHAR(20) NOT NULL UNIQUE,
    course_name VARCHAR(255) NOT NULL,
    instructor VARCHAR(255) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS enrollments (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL,
    course_id INTEGER NOT NULL,
    enrolled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    UNIQUE(user_id, course_id)
);

CREATE TABLE IF NOT EXISTS grades (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL,
    course_id INTEGER NOT NULL,
    grade VARCHAR(10) NOT NULL,
    grade_points DECIMAL(3,2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    UNIQUE(user_id, course_id)
);

CREATE TABLE IF NOT EXISTS announcements (
    id SERIAL PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS messages (
    id SERIAL PRIMARY KEY,
    user_id INTEGER,
    subject VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS remember_tokens (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL,
    token_hash VARCHAR(255) NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_token_hash ON remember_tokens(token_hash);
CREATE INDEX IF NOT EXISTS idx_user_id_tokens ON remember_tokens(user_id);

INSERT INTO users (name, email, password, role) VALUES 
('Admin User', 'admin@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin')
ON CONFLICT (email) DO NOTHING;

INSERT INTO courses (course_code, course_name, instructor, description) VALUES 
('CS101', 'Introduction to Computer Science', 'Dr. Ahmed Al-Qadhi', 'Fundamental concepts of programming and computer science'),
('MATH201', 'Calculus I', 'Dr. Mohammed Al-Jabali', 'Differential and integral calculus with applications'),
('ENG102', 'Academic English Skills', 'Prof. Sarah Al-Halimi', 'Writing and communication skills for academic purposes'),
('PHYS301', 'General Physics I', 'Dr. Abdulkarim Al-Shaibani', 'Classical mechanics and thermodynamics'),
('HIST150', 'History of Yemen', 'Prof. Fatima Ba-Wazir', 'Overview of Yemen''s ancient and modern history')
ON CONFLICT (course_code) DO NOTHING;

INSERT INTO announcements (title, content) VALUES 
('Welcome to the New Semester!', 'We wish you a successful semester. Please check your course schedules and contact your academic advisor for any questions.'),
('Library Hours Extended', 'The university library will remain open 24/7 during exam periods. Please bring your student ID for late-hour access.'),
('Spring Semester Registration', 'Registration for the spring semester will open next Monday. Kindly meet with your academic advisor before registering.')
ON CONFLICT DO NOTHING;
