-- =============================================
-- TaskFlow - Database Schema
-- Run this in MySQL Workbench after creating
-- the database: CREATE DATABASE taskflow;
-- =============================================

CREATE DATABASE IF NOT EXISTS taskflow CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE taskflow;

-- =============================================
-- 1. USERS TABLE
-- =============================================
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'user') NOT NULL DEFAULT 'user',
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =============================================
-- 2. PROJECTS TABLE
-- =============================================
CREATE TABLE projects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    owner_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    status ENUM('active', 'completed', 'archived') NOT NULL DEFAULT 'active',
    start_date DATE,
    end_date DATE,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =============================================
-- 3. PROJECT MEMBERS TABLE
-- =============================================
CREATE TABLE project_members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    user_id INT NOT NULL,
    role ENUM('member', 'viewer') NOT NULL DEFAULT 'member',
    joined_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_membership (project_id, user_id)
) ENGINE=InnoDB;

-- =============================================
-- 4. TASKS TABLE
-- =============================================
CREATE TABLE tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    project_id INT DEFAULT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    priority ENUM('low', 'medium', 'high') NOT NULL DEFAULT 'medium',
    status ENUM('pending', 'in_progress', 'completed') NOT NULL DEFAULT 'pending',
    category VARCHAR(100) DEFAULT NULL,
    deadline DATE DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- =============================================
-- 5. GOALS TABLE
-- =============================================
CREATE TABLE goals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    type ENUM('short-term', 'long-term') NOT NULL DEFAULT 'short-term',
    target_date DATE DEFAULT NULL,
    progress INT NOT NULL DEFAULT 0,
    status ENUM('active', 'completed') NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =============================================
-- 6. TASK COMMENTS TABLE
-- =============================================
CREATE TABLE task_comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    task_id INT NOT NULL,
    user_id INT NOT NULL,
    content TEXT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =============================================
-- 7. TIME LOGS TABLE
-- =============================================
CREATE TABLE time_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    task_id INT NOT NULL,
    user_id INT NOT NULL,
    start_time DATETIME NOT NULL,
    end_time DATETIME DEFAULT NULL,
    duration_minutes INT DEFAULT 0,
    notes VARCHAR(255) DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =============================================
-- 8. ACTIVITY LOGS TABLE
-- =============================================
CREATE TABLE activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    action VARCHAR(255) NOT NULL,
    entity_type VARCHAR(50) DEFAULT NULL,
    entity_id INT DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =============================================
-- SEED DATA
-- =============================================

-- Passwords are all "password123" hashed with password_hash()
-- $2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi

INSERT INTO users (name, email, password, role, status) VALUES
('Admin', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'active'),
('Alice Johnson', 'user1@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', 'active'),
('Bob Smith', 'user2@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', 'active');

-- Projects
INSERT INTO projects (owner_id, title, description, status, start_date, end_date) VALUES
(2, 'Website Redesign', 'Complete redesign of the company website with modern UI', 'active', '2026-03-01', '2026-04-30'),
(3, 'Mobile App MVP', 'Build the minimum viable product for the mobile app', 'active', '2026-03-10', '2026-05-15'),
(2, 'Marketing Campaign', 'Q2 digital marketing campaign planning and execution', 'active', '2026-03-15', '2026-06-30');

-- Project Members
INSERT INTO project_members (project_id, user_id, role) VALUES
(1, 2, 'member'),
(1, 3, 'member'),
(2, 3, 'member'),
(2, 2, 'viewer'),
(3, 2, 'member');

-- Tasks
INSERT INTO tasks (user_id, project_id, title, description, priority, status, category, deadline) VALUES
(2, 1, 'Design homepage mockup', 'Create wireframes and high-fidelity mockup for the new homepage', 'high', 'in_progress', 'Design', '2026-03-20'),
(2, 1, 'Implement responsive navbar', 'Build a mobile-friendly navigation bar using CSS flexbox', 'medium', 'pending', 'Development', '2026-03-25'),
(2, NULL, 'Write project documentation', 'Document all API endpoints and setup instructions', 'low', 'pending', 'Documentation', '2026-04-01'),
(2, 3, 'Create social media content plan', 'Plan content calendar for Instagram, Twitter, and LinkedIn', 'medium', 'completed', 'Marketing', '2026-03-15'),
(3, 2, 'Set up Flutter project', 'Initialize Flutter project with required dependencies', 'high', 'completed', 'Development', '2026-03-12'),
(3, 2, 'Design login screen', 'Create UI for the mobile app login and registration screens', 'high', 'in_progress', 'Design', '2026-03-22'),
(3, NULL, 'Research competitor apps', 'Analyze top 5 competitor apps for feature comparison', 'medium', 'pending', 'Research', '2026-03-28'),
(2, 1, 'Set up CI/CD pipeline', 'Configure GitHub Actions for automated deployment', 'high', 'pending', 'DevOps', '2026-03-18'),
(3, 2, 'Implement user auth flow', 'Build login, register, and password reset functionality', 'high', 'pending', 'Development', '2026-04-05'),
(2, NULL, 'Prepare internship report', 'Write weekly internship report and submit to supervisor', 'medium', 'pending', 'Admin', '2026-03-14');

-- Goals
INSERT INTO goals (user_id, title, description, type, target_date, progress, status) VALUES
(2, 'Complete 50 tasks this month', 'Finish at least 50 tasks by end of March', 'short-term', '2026-03-31', 30, 'active'),
(2, 'Learn React.js', 'Complete an online React course and build a portfolio project', 'long-term', '2026-06-30', 15, 'active'),
(3, 'Ship mobile app beta', 'Release the first beta version of the mobile app', 'short-term', '2026-04-15', 40, 'active'),
(3, 'Read 12 tech books', 'Read one technical book per month for the year', 'long-term', '2026-12-31', 25, 'active'),
(2, 'Master Git workflow', 'Learn advanced Git branching, rebasing, and CI/CD', 'short-term', '2026-04-30', 60, 'active');

-- Task Comments
INSERT INTO task_comments (task_id, user_id, content) VALUES
(1, 2, 'Started working on the wireframes. Will share Figma link soon.'),
(1, 3, 'Looking forward to seeing the designs! Let me know if you need any input.'),
(5, 3, 'Flutter project is set up with all dependencies. Ready to start building screens.'),
(6, 2, 'Maybe we should use a dark theme for the login screen? Looks more modern.'),
(6, 3, 'Good idea! I will explore both light and dark theme options.'),
(8, 2, 'Need to set up Docker first before configuring the pipeline.');

-- Time Logs
INSERT INTO time_logs (task_id, user_id, start_time, end_time, duration_minutes, notes) VALUES
(1, 2, '2026-03-16 09:00:00', '2026-03-16 11:30:00', 150, 'Worked on homepage wireframes'),
(1, 2, '2026-03-16 13:00:00', '2026-03-16 14:45:00', 105, 'Polished wireframe details'),
(4, 2, '2026-03-14 10:00:00', '2026-03-14 12:00:00', 120, 'Created content calendar'),
(5, 3, '2026-03-12 08:00:00', '2026-03-12 10:30:00', 150, 'Set up Flutter and dependencies'),
(6, 3, '2026-03-16 09:00:00', '2026-03-16 12:00:00', 180, 'Designing login screen variants'),
(6, 3, '2026-03-16 14:00:00', '2026-03-16 16:00:00', 120, 'Implementing login UI'),
(3, 2, '2026-03-15 14:00:00', '2026-03-15 15:30:00', 90, 'Outlined documentation structure'),
(8, 2, '2026-03-16 08:00:00', '2026-03-16 09:00:00', 60, 'Researched CI/CD options');

-- Activity Logs
INSERT INTO activity_logs (user_id, action, entity_type, entity_id) VALUES
(2, 'Created task: Design homepage mockup', 'task', 1),
(2, 'Created task: Implement responsive navbar', 'task', 2),
(2, 'Created project: Website Redesign', 'project', 1),
(3, 'Completed task: Set up Flutter project', 'task', 5),
(3, 'Created project: Mobile App MVP', 'project', 2),
(2, 'Completed task: Create social media content plan', 'task', 4),
(2, 'Created goal: Complete 50 tasks this month', 'goal', 1),
(3, 'Created goal: Ship mobile app beta', 'goal', 3),
(2, 'Added comment on task: Design homepage mockup', 'comment', 1),
(3, 'Logged 150 minutes on task: Set up Flutter project', 'time_log', 4);
