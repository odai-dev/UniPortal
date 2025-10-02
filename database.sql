-- PostgreSQL schema for Fitness Center Portal

CREATE TABLE IF NOT EXISTS users (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(20) DEFAULT 'member' CHECK (role IN ('member', 'admin')),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS classes (
    id SERIAL PRIMARY KEY,
    class_code VARCHAR(20) NOT NULL UNIQUE,
    class_name VARCHAR(255) NOT NULL,
    trainer VARCHAR(255) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS memberships (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL,
    class_id INTEGER NOT NULL,
    enrolled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
    UNIQUE(user_id, class_id)
);

CREATE TABLE IF NOT EXISTS progress (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL,
    class_id INTEGER NOT NULL,
    performance_score VARCHAR(10) NOT NULL,
    score_points DECIMAL(3,2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
    UNIQUE(user_id, class_id)
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

CREATE TABLE IF NOT EXISTS course_materials (
    id SERIAL PRIMARY KEY,
    class_id INTEGER NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    file_size INTEGER NOT NULL,
    mime_type VARCHAR(100) NOT NULL,
    uploaded_by INTEGER NOT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE CASCADE
);

INSERT INTO users (name, email, password, role) VALUES 
('Fitness Admin', 'admin@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin')
ON CONFLICT (email) DO NOTHING;

INSERT INTO classes (class_code, class_name, trainer, description) VALUES 
('YOGA101', 'Beginner Yoga & Flexibility', 'Sarah Mitchell', 'Perfect for beginners - learn basic poses, breathing techniques, and improve flexibility'),
('HIIT201', 'High-Intensity Interval Training', 'Marcus Johnson', 'Burn calories fast with intense cardio and strength intervals'),
('SPIN150', 'Indoor Cycling Power', 'Emily Rodriguez', 'High-energy cycling class with music - all fitness levels welcome'),
('STRENGTH301', 'Strength & Conditioning', 'David Thompson', 'Build muscle and increase strength with weights and functional movements'),
('PILATES102', 'Core Pilates', 'Jessica Chen', 'Strengthen your core, improve posture and body awareness')
ON CONFLICT (class_code) DO NOTHING;

INSERT INTO announcements (title, content) VALUES 
('Welcome to FitZone Fitness Center!', 'Start your fitness journey with us! Check out our class schedules and book your first session today.'),
('Extended Hours This Week', 'The gym will be open 24/7 this week! Get your workout in anytime that suits your schedule.'),
('New Class Added: Boxing Bootcamp', 'Join our new Boxing Bootcamp class every Saturday at 9 AM. High-energy cardio meets boxing fundamentals!')
ON CONFLICT DO NOTHING;
