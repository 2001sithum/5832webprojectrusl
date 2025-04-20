# Event Horizon (University Project Version)

A visually focused event management system concept for a university project. Features a landing page, event listings, details, contact, team, and gallery pages, along with basic user registration/login and RSVP functionality. Includes placeholders and setup for animations using GSAP.

**Focus:** Layout, Visual Appeal, Basic PHP Functionality.
**Security:** Basic password hashing only (CSRF, extensive validation omitted as per request).

## Pages Included

*   `index.php`: Landing page with hero banner and featured events.
*   `events.php`: Lists all upcoming/ongoing events.
*   `event_details.php`: Shows details for a single event.
*   `login.php`: User login form.
*   `register.php`: User registration form.
*   `logout.php`: Logs the user out.
*   `dashboard.php`: Simple dashboard for logged-in users (shows RSVPs).
*   `contact.php`: Contact information and form.
*   `team.php`: "Our Team" page showcase.
*   `gallery.php`: "Our Work" / Past event gallery.
*   `admin/index.php`: Admin dashboard / Event listing table.
*   `admin/edit_event.php`: Form to add or edit events.
*   `admin/delete_event.php`: Handles event deletion requests.
*   `rsvp_handler.php`: Processes RSVP/Cancel actions.

## Structure

*   Root: Core PHP pages (`index.php`, `login.php`, etc.).
*   `/admin`: Admin-specific pages.
*   `/css`: Stylesheets (`main.css`, `animations.css`).
*   `/js`: JavaScript (`main.js`, `animations.js`). GASP library included via CDN (can download to `/js/lib/`).
*   `/images`: Contains subfolders for `banners`, `team`, `gallery`, etc. **You must add image files here.** Also requires `event_default.jpg` as a fallback.
*   `/includes`: PHP backend logic (config, db connection, functions, HTML templates).
*   `/data`: SQLite database file (`events.db`) and logs. Attempted `.htaccess` protection.

## Setup

1.  **Prerequisites:**
    *   PHP >= 7.4 (with PDO and PDO_SQLite enabled).
    *   Web Server (Apache, Nginx, or PHP's built-in server for development).
    *   Write permissions for the web server on the `data/` directory.

2.  **Download/Place Files:** Put the project files on your web server or local development environment.

3.  **Permissions:** Ensure the `data/` directory is writable by the web server process (e.g., `www-data`, `apache`). You might need to use `chmod` or `chown` on your server:
    ```bash
    # Example - Navigate to project root first
    chmod 775 data
    # If needed, change group ownership (replace www-data with your server's user/group)
    # chown youruser:www-data data
    ```

4.  **Configuration (Optional but Recommended):**
    *   Edit `includes/config.php` to change `APP_NAME` or `ADMIN_EMAIL`.
    *   The database file (`events.db`) will be *automatically created* in the `data/` directory on the first visit if it doesn't exist, along with tables and a default admin user.

5.  **Default Admin:**
    *   Username: `admin`
    *   Password: `admin123`
    *   **!!! CHANGE THIS PASSWORD immediately after logging in !!!** (Functionality to change password would need to be added).

6.  **Add Images:**
    *   Place your banner images in `images/banners/`.
    *   Place team photos in `images/team/`.
    *   Place gallery images in `images/gallery/`.
    *   **IMPORTANT:** Create a default fallback image named `event_default.jpg` and place it in the main `images/` directory.

7.  **Access:** Open the project in your browser (e.g., `http://localhost/event_management_uni_project/`).

## Animations & 3D Effects

*   CSS classes like `.animate-on-scroll` are added to elements.
*   Basic CSS transitions are in `animations.css`.
*   `js/main.js` uses Intersection Observer to add the `.is-visible` class when elements scroll into view, triggering the basic CSS animations.
*   GSAP library is included via CDN in `includes/templates/header.php`.
*   `js/animations.js` contains placeholder code and examples for using GSAP for more complex effects like the animated letters, staggered card reveals, and parallax backgrounds. **You will need to customize `js/animations.js` significantly** to achieve specific 3D effects or complex sequences based on your design requirements.

## Security Notes (Simplified for Project)

*   Only basic password hashing (`PASSWORD_BCRYPT`) is implemented.
*   **CSRF protection is NOT implemented.**
*   Input validation is basic. Sanitize output using `escape()`.
*   Admin functionality should be password-protected (requires admin login).
*   File structure attempts to protect `includes` and `data` via `.htaccess` (Apache specific). Ensure your server config prevents direct access if not using Apache or if `.htaccess` is disabled.
