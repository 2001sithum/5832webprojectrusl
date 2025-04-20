
# Event Management Pro v5 - SithumSithara RUSL Applied Science Web Project

[![PHP Version](https://img.shields.io/badge/PHP-%3E%3D7.4%20(8.0%2B%20Recommended)-8892BF.svg)](https://www.php.net/)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https/opensource.org/licenses/MIT) <!-- Optional: Add License -->

## 📖 Overview

This repository contains **Event Management Pro v5**, a web application developed as part of the **SithumSithara RUSL Applied Science Web Project**. Built with plain PHP, it demonstrates core web development principles for managing events, user authentication, and RSVPs.

The project emphasizes foundational PHP skills, database interaction via PDO (supporting MySQL/SQLite), basic security practices, and a structured approach without relying on a full framework. It provides a practical example of event listing, user registration/login, admin event management (CRUD), and user RSVP functionality.

**Context Note:** While the current version focuses on core CRUD and RSVP features, a potential future direction or related project context could involve integrating **interactive charts** (e.g., using Chart.js or ApexCharts) to visualize data such as event attendance trends, registration numbers over time, or service popularity, adding an analytical dimension to the event management data.

**Disclaimer:** This is a demonstration or educational project and is **not suitable for production deployment** without significant security hardening, feature expansion, and code review.

## ✨ Core Functionalities Implemented

This project showcases the following individual functionalities:

*   **Backend Structure:**
    *   **Modular Code:** Separation of concerns using `includes/` for configuration, database logic, helper functions, and templates.
    *   **Configuration Management:** Centralized settings in `config.php` (database type, credentials, paths, session parameters).
    *   **Database Abstraction (PDO):** Uses PHP Data Objects for database interaction, allowing easy switching between MySQL and SQLite.
    *   **Helper Functions:** A library (`functions.php`) providing reusable functions for:
        *   URL Generation (`baseUrl`)
        *   Redirection (`redirect`)
        *   Authentication Checks (`isAuthenticated`, `isAdmin`, `requireAuth`)
        *   Session Management (`logoutUser`, secure cookie parameters)
        *   Output Escaping (`escape`/`e` for XSS prevention)
        *   Flash Messaging (`setFlashMessage`, `getFlashMessage`)
        *   CSRF Protection (`generateCsrfToken`, `validateCsrfToken`)
        *   Basic Template Rendering (`renderTemplate`)
        *   Date Formatting (`formatDate`)
*   **Database & Schema:**
    *   **Relational Schema:** Defines tables for `users`, `events`, and `rsvps`.
    *   **Foreign Key Constraints:** Enforces relationships (e.g., RSVPs linked to users and events) with cascading deletes.
    *   **Automated Setup:** `scripts/setup.sh` orchestrates database initialization using `initialize_db.php`.
    *   **Sample Data:** Optional script (`insert_sample_data.php`) to populate the database with initial users and events.
*   **Authentication & Authorization:**
    *   **User Registration:** Allows new users to create accounts (`register.php`).
    *   **Password Security:** Uses `password_hash()` for secure password storage and `password_verify()` for comparison.
    *   **Session-Based Login:** Manages user sessions securely after successful login (`login.php`).
    *   **Logout:** Provides secure session termination (`logout.php`).
    *   **Role Management:** Simple distinction between `admin` and `user` roles stored in the database and session.
    *   **Access Control:** Protects routes/pages based on login status and user role (`requireAuth` function).
*   **Event Management (Admin CRUD):**
    *   **Create:** Admins can add new events with details like name, date, time, location, description, category, capacity, image URL (`event_edit.php`).
    *   **Read:** Admins can view a list of all events (`admin.php`) and details of specific events (`event_view.php`).
    *   **Update:** Admins can modify existing event details (`event_edit.php?id=...`).
    *   **Delete:** Admins can remove events, automatically cascading to remove associated RSVPs (`event_delete.php`).
*   **RSVP System (User Interaction):**
    *   **RSVP:** Logged-in regular users can RSVP to 'Upcoming' or 'Ongoing' events (`rsvp.php` action='rsvp').
    *   **Cancel RSVP:** Users can cancel their existing RSVP (`rsvp.php` action='cancel').
    *   **Capacity Check:** Prevents users from RSVPing to events that have reached full capacity.
    *   **Attendee Count:** Automatically increments/decrements the `attendees_count` on the `events` table within a transaction for data integrity.
    *   **User Dashboard:** Users can view a list of events they have RSVP'd for (`dashboard.php`).
*   **Frontend & UI:**
    *   **Templating:** Simple PHP includes for header, footer, navigation, and message display.
    *   **Basic Styling:** Uses CSS (`public/css/style.css`) for a dark theme, layout (Flexbox/Grid), basic component styling, and responsiveness.
    *   **JavaScript Enhancements:** Minimal vanilla JS (`public/js/script.js`) for confirmation dialogs, auto-hiding flash messages, and active nav link highlighting.
    *   **External Image Linking:** Supports linking event images via URL (field `image_url`).
*   **Basic Security:**
    *   **Directory Access Control:** `.htaccess` rules block direct web access to non-public directories.
    *   **CSRF Protection:** Tokens generated and validated for all state-changing forms.
    *   **XSS Prevention:** Output consistently escaped using `htmlspecialchars`.
    *   **SQL Injection Prevention:** Uses PDO prepared statements exclusively for database queries.
    *   **Secure Session Cookies:** Configured with `HttpOnly`, `Secure` (if HTTPS), and `SameSite` flags.

## 🛠️ Technology Stack

*   **Backend:** PHP (7.4+, 8.0+ Recommended)
*   **Database:** PDO (PHP Data Objects) supporting:
    *   MySQL / MariaDB
    *   SQLite 3
*   **Frontend:** HTML5, CSS3, JavaScript (ES6+)
*   **Web Server:** Apache or Nginx (with mod_php/PHP-FPM) recommended.

## 🚀 Setup and Installation

*(These steps remain the same as the previous README)*

**Prerequisites:**

*   PHP CLI (Command Line Interface) 7.4+ with PDO, pdo_mysql, and pdo_sqlite extensions enabled.
*   A web server (Apache, Nginx, etc.).
*   Access to a MySQL database server (if using MySQL).
*   Bash environment (for running setup scripts).

**Steps:**

1.  **Generate Project:** Use the `create_event_project.sh` script.
2.  **Review Configuration (`event_management_pro/includes/config.php`):** Set `DB_TYPE`, credentials (especially DB_PASS), and `BASE_URL`.
3.  **Run Setup Script:**
    ```bash
    cd event_management_pro/scripts
    ./setup.sh
    ```
    Follow prompts for database initialization and optional sample data.
4.  **Configure Web Server:** Point DocumentRoot to `event_management_pro/public`. Ensure PHP processing is enabled.
5.  **Set Permissions:** Ensure the `data/` directory (and potentially `data/php_errors.log`) is writable by the web server user, especially if using SQLite.
6.  **Access Application:** Navigate to the configured URL in your browser.

## 📂 Project Structure

*(Structure remains the same as the previous README)*


event_management_pro/
├── data/
├── includes/
│ ├── templates/
│ ├── config.php
│ ├── database.php
│ ├── functions.php
│ └── .htaccess
├── public/
│ ├── assets/
│ ├── css/
│ ├── js/
│ ├── index.php
│ ├── login.php
│ └── ... (other public PHP files)
├── scripts/
│ ├── initialize_db.php
│ ├── insert_sample_data.php
│ ├── mysql_schema.sql
│ ├── setup.sh
│ └── .htaccess
└── .gitignore

## 💻 Usage

*(Usage remains the same as the previous README)*

*   **Visitors:** View events on the homepage.
*   **Users:** Register, Login, View event details, RSVP/Cancel RSVP, View their RSVPs on the dashboard.
*   **Admin:** Login, Manage all events (Add, Edit, View List, View Detail, Delete).
    *   *(Sample Data Logins if inserted)*: `admin`/`adminpass`, `testuser`/`userpass`.

## 🔐 Security Considerations

*(Security points remain the same as the previous README)*

*   **DEMO ONLY:** Basic security implemented; not production-ready.
*   Requires robust input validation, rate limiting, fine-grained permissions, etc., for real-world use.
*   Review session and error handling configurations.

## 🔮 Future Enhancements

*   **Interactive Charts:** Visualize data like RSVP counts per event, registration trends over time, or event popularity using a charting library (e.g., Chart.js, ApexCharts).
*   Advanced Input Validation.
*   Image Uploads (instead of URLs).
*   Event Categories/Tags Filtering & Search.
*   Email Notifications (Registration, RSVP, Reminders).
*   Password Reset Functionality.
*   AJAX for smoother UI interactions.
*   Unit & Integration Testing.
*   Production Deployment Configuration.

## 🤝 Project Context / Contributing

This project was developed for the SithumSithara RUSL Applied Science curriculum. While primarily an educational demo, feedback or suggestions related to the core concepts demonstrated are welcome.

## 📜 License

