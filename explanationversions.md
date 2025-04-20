# Event Management Application - Development Evolution (v1-v3)

[![PHP Version](https://img.shields.io/badge/PHP-%3E%3D7.4%20(v3)-8892BF.svg)](https://www.php.net/)
[![Database Support](https://img.shields.io/badge/DB-MySQL%20%7C%20SQLite%20(v3)-blue.svg)]()
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https/opensource.org/licenses/MIT) <!-- Optional: Add License -->

## 📖 Overview

This document outlines the development journey of the Event Management Application, showcasing its incremental evolution across three major versions (v1, v2, v3). The project progressed from a basic concept to a more structured, feature-rich, and secure web application designed for managing events and handling user interactions.

*   **Version 1:** Focused on establishing the foundational concept and basic functionality.
*   **Version 2:** Shifted towards improved code structure and acting primarily as an event "showcasing" platform.
*   **Version 3:** Matured into a professional, interactive platform incorporating industry-standard security practices, user authentication, event booking/RSVP capabilities, flexible database support, and an automated setup process.

## 🚀 Version Evolution

---

### Version 1: The Foundation

*   **Goal:** Proof-of-concept, basic event listing.
*   **Functionality:**
    *   Display a static or minimally dynamic list of events.
    *   Very basic page structure.
*   **Code Structure:**
    *   Likely monolithic PHP files or minimal includes.
    *   Primarily procedural code.
    *   Limited separation of concerns (HTML, CSS, PHP potentially mixed).
    *   Basic database connection (if any), possibly using older methods (e.g., `mysql_*` - deprecated).
*   **Security:** Minimal attention to security practices (e.g., no CSRF protection, potential SQL injection vulnerabilities, no output escaping).
*   **Setup:** Fully manual (copying files, creating database tables manually).

---

### Version 2: The Showcase & Structural Improvement

*   **Goal:** Enhance presentation, improve code organization, act as a display/portfolio page for events.
*   **Functionality:**
    *   Improved event listing and display (better styling, potentially basic filtering).
    *   Focus on showcasing event information rather than interaction (booking/RSVP might be absent or very basic).
    *   May include a simple way to update displayed event data (e.g., editing a data file or a very basic admin interface).
*   **Code Structure:**
    *   **Incremental Updates:** Introduction of more `include` files for reusable components (header, footer).
    *   Basic helper functions might be introduced.
    *   Slightly better separation of PHP logic and HTML presentation.
    *   Database interaction might be more organized, perhaps using early PDO concepts but not fully structured.
*   **Security:**
    *   **Incremental Updates:** Basic output escaping (`htmlspecialchars`) might be introduced to prevent simple XSS.
    *   Still lacking comprehensive security measures found in v3.
*   **Setup:** Still largely manual, but file organization might be slightly improved.

---

### Version 3: The Professional Platform (Event Management Pro v5)

*   **Goal:** Create a functional, interactive, and secure platform for managing events, user registrations, and RSVPs, adhering to better development practices and offering easier setup.
*   **Functionality:**
    *   **Full User Authentication:** Secure Registration, Login (Password Hashing), Logout, Role-based access (Admin vs User).
    *   **Event CRUD:** Admins can Create, Read, Update, and Delete events via a dedicated panel.
    *   **RSVP System:** Users can RSVP for events, cancel RSVPs, with checks against event capacity.
    *   **User Dashboard:** Users see events they have RSVP'd for.
    *   **Admin Panel:** Centralized event management.
    *   **Dynamic Event Listing & Details:** Displays upcoming/ongoing events, shows detailed views.
    *   **Flash Messages:** User feedback for actions (success, error, info).
*   **Code Structure:**
    *   **Modular Design:** Clear separation into `public/`, `includes/`, `data/`, `scripts/` directories.
    *   **Core Includes:** Dedicated files for `config.php`, `database.php` (PDO connection, session start), `functions.php` (reusable helpers).
    *   **Templating:** Simple but effective PHP templating (`renderTemplate`, partials like `header`, `footer`, `navbar`).
    *   **Database Abstraction:** Consistent use of **PDO** with **prepared statements**.
*   **Industry Standard Security Practices:**
    *   **CSRF Protection:** Implemented on all state-changing forms.
    *   **Password Hashing:** Uses `password_hash()` and `password_verify()`.
    *   **SQL Injection Prevention:** Relies entirely on PDO prepared statements.
    *   **XSS Prevention:** Consistent output escaping (`htmlspecialchars`).
    *   **Secure Sessions:** Configurable secure cookie parameters (HttpOnly, Secure, SameSite).
    *   **Directory Protection:** `.htaccess` rules prevent direct access to non-public directories.
*   **Database Flexibility & Schema:**
    *   **Dual DB Support:** Designed to work seamlessly with both **MySQL** and **SQLite** via configuration toggle (`config.php`).
    *   **Well-Defined Schema:** Clear structure for `users`, `events`, `rsvps` tables with appropriate relationships and constraints (Foreign Keys, `ON DELETE CASCADE`).
    *   **Schema Generation:** `initialize_db.php` creates the schema, and `mysql_schema.sql` is generated for reference/manual import if using MySQL.
*   **Automated Setup & Ease of Use:**
    *   **`setup.sh` Script:** Automates the database initialization process (table creation) based on `config.php`.
    *   **Optional Sample Data:** Script can populate the database with initial users and events for quick testing.
    *   **Clear Instructions:** Setup script provides guidance on configuration and next steps.
*   **Technology:** Mature PHP practices (strict types, PDO), structured CSS, minimal vanilla JavaScript for UI enhancements.

---

## 🛠️ Technology Stack (v3)

*   **Backend:** PHP (7.4+, 8.0+ Recommended)
*   **Database:** PDO supporting MySQL/MariaDB and SQLite 3
*   **Frontend:** HTML5, CSS3, JavaScript (ES6+)
*   **Setup:** Bash Scripting

## 🚀 Setup and Installation (v3)

Version 3 provides an automated setup process:

1.  **Configure:** Edit `includes/config.php` to set `DB_TYPE` ('mysql' or 'sqlite') and corresponding database credentials or path. **Crucially, change default passwords if using MySQL sample data.**
2.  **Run Setup Script:**
    ```bash
    cd event_management_pro/scripts
    ./setup.sh
    ```
    This script handles prerequisite checks, directory setup, database table creation, and optionally inserts sample data.
3.  **Web Server Config:** Point your web server's document root to the `event_management_pro/public` directory. Ensure PHP is configured correctly.
4.  **Permissions:** Ensure the `data/` directory is writable by the web server, especially for SQLite.

*(Refer to the detailed v3 README for more specific setup instructions)*

## 📂 Project Structure (v3)

*(The v3 structure as detailed in the previous README)*



## 💻 Usage (v3)

*   **Visitors:** View events.
*   **Users:** Register, Login, View event details, RSVP/Cancel, View Dashboard.
*   **Admin:** Login, Full Event CRUD, View all events.

## 🔐 Security Focus (v3)

Version 3 significantly elevates security by implementing:
*   CSRF Protection
*   Password Hashing (bcrypt)
*   Prepared Statements (PDO)
*   Output Escaping
*   Secure Session Cookies
*   Directory Access Control

## 💾 Database Flexibility (v3)

Version 3 leverages PDO to provide native support for both **MySQL** and **SQLite** databases, selectable via a simple configuration setting. The setup script handles the schema creation for the chosen database type.

## 🔮 Conclusion

The Event Management Application demonstrates a clear progression in web development practices. Starting from a basic concept (v1), it evolved through structural improvements and a focus on presentation (v2), culminating in a functional, interactive, and significantly more secure platform (v3) that incorporates industry-standard practices, automated setup, and database flexibility suitable for a professional demonstration or a strong starting point for further development.