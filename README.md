# ⚡ TaskFlow — Productivity & Task Management System

A modern, dark-themed SaaS-style **Task Management Web App** built with vanilla PHP, MySQL, HTML, CSS, and JavaScript. No frameworks used — pure fundamentals.

![PHP](https://img.shields.io/badge/PHP-8.x-777BB4?logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-8.x-4479A1?logo=mysql&logoColor=white)
![JavaScript](https://img.shields.io/badge/JavaScript-ES6-F7DF1E?logo=javascript&logoColor=black)
![CSS3](https://img.shields.io/badge/CSS3-Dark_Theme-1572B6?logo=css3&logoColor=white)

---

## ✨ Features

### 🔐 Authentication & Authorization
- Secure login/register with **password hashing** (`password_hash`)
- **Role-Based Access Control** (Admin vs User)
- CSRF token protection on all forms
- Session-based authentication

### 📋 Task Management (Full CRUD)
- Create, edit, delete, and view tasks
- Filter by **status** (Pending / In Progress / Completed) and **priority** (Low / Medium / High)
- Search and sort functionality
- Deadline tracking with **overdue highlighting**
- Assign tasks to projects

### 📁 Project Management
- Create and manage projects with start/end dates
- **Team collaboration** — add/remove project members by email
- View all project tasks and members in one place

### 🎯 Goal Tracking
- Set short-term and long-term goals
- Visual **progress bar** (0-100%)
- Auto-complete status when progress hits 100%

### ⏱️ Time Tracking
- **Built-in timer** with start/stop functionality
- Manual time entry with task association
- Daily, weekly, and all-time hour summaries

### 💬 Task Comments
- Add comments on any task detail page
- Delete your own comments (admin can delete any)

### 📈 Analytics Dashboard (Chart.js)
- **User Analytics**: Weekly tasks completed, hours tracked, tasks by status/priority
- **Admin Analytics**: System-wide stats, most active users, completion trends

### 🛡️ Admin Panel
- Manage all users (activate/deactivate, change roles, delete)
- View system-wide statistics and activity logs
- Access all user data across modules

---

## 🗂️ Project Structure

```
TaskFlow/
├── index.php                    # Landing redirect
├── config/
│   ├── database.example.php     # DB config template (copy & rename)
│   └── database.php             # Your local DB config (gitignored)
├── includes/
│   ├── auth.php                 # Session & role guard functions
│   ├── functions.php            # CSRF, escaping, flash messages, utilities
│   ├── header.php               # HTML <head>, CSS, Google Fonts
│   ├── navbar.php               # Top navigation bar
│   ├── sidebar.php              # Side navigation menu
│   └── footer.php               # Footer & JS scripts
├── assets/
│   ├── css/style.css            # Complete dark theme (1000+ lines)
│   └── js/app.js                # Sidebar toggle, timer, Chart.js helpers
├── auth/
│   ├── login.php                # Login page
│   ├── register.php             # Registration page
│   └── logout.php               # Session destroy
├── admin/
│   ├── dashboard.php            # Admin dashboard with system stats
│   ├── users.php                # User management table
│   └── edit_user.php            # Edit user role/status
├── user/
│   └── dashboard.php            # User dashboard with personal stats
├── modules/
│   ├── tasks/                   # Full CRUD + comments + time logs
│   ├── projects/                # CRUD + member management
│   ├── goals/                   # CRUD with progress tracking
│   ├── time_logs/               # Timer + manual entry
│   ├── comments/                # Add/delete task comments
│   └── analytics/               # Chart.js dashboards
└── database/
    └── schema.sql               # Full DDL + seed data
```

---

## 🚀 Setup Instructions

### Prerequisites
- **PHP 8.x** installed
- **MySQL Server** running locally
- **MySQL Workbench** (or any MySQL client)

### Step 1: Clone the Repository
```bash
git clone https://github.com/jacksontann/TaskFlow-Task-Management-System.git
cd TaskFlow-Task-Management-System
```

### Step 2: Create the Database
1. Open **MySQL Workbench**
2. Connect to your local MySQL server
3. Open `database/schema.sql`
4. Click ⚡ **Execute** to create the database, tables, and seed data

### Step 3: Configure Database Connection
```bash
cp config/database.example.php config/database.php
```
Then edit `config/database.php` and update with your MySQL credentials:
```php
$username = 'root';
$password = 'YOUR_PASSWORD';
```

### Step 4: Start the Server
```bash
php -S localhost:8080
```

### Step 5: Open in Browser
Navigate to: [http://localhost:8080](http://localhost:8080)

---

## 🔑 Default Login Credentials

| Role | Email | Password |
|------|-------|----------|
| Admin | admin@example.com | password123 |
| User | user1@example.com | password123 |
| User | user2@example.com | password123 |

---

## 🔒 Security Features

- **PDO Prepared Statements** — prevents SQL injection
- **Password Hashing** — `password_hash()` with `PASSWORD_DEFAULT`
- **CSRF Protection** — token-based form validation
- **Input Escaping** — `htmlspecialchars()` on all output
- **Role-Based Guards** — `requireLogin()`, `requireAdmin()` on every page
- **Ownership Checks** — users can only modify their own records

---

## 🛠️ Tech Stack

| Layer | Technology |
|-------|-----------|
| Backend | PHP 8.x (vanilla, no framework) |
| Database | MySQL 8.x with PDO |
| Frontend | HTML5, CSS3, Vanilla JavaScript |
| Charts | Chart.js |
| Fonts | Google Fonts (Inter) |
| Icons | Emoji-based (no external library) |

---

## 📸 Screenshots

*Coming soon*

---

## 📝 License

This project is open source and available for educational purposes.
