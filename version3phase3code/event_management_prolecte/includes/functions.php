<?php
/**
 * Simple Helper Functions
 */
declare(strict_types=1);

/**
 * Basic HTML escaping.
 */
function escape(?string $value): string {
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
function e(?string $value): void { echo escape($value); } // Shortcut echo

/**
 * Redirects to a different page. Ensure no output before calling this.
 */
function redirect(string $url): void {
    // Basic check if relative path - assumes running from root directory scripts
     if (!preg_match('~^(?:f|ht)tps?://~i', $url) && substr($url, 0, 1) !== '/') {
       // Determine base path from current script - might need refinement
       $baseUrl = dirname($_SERVER['SCRIPT_NAME']);
       $url = rtrim($baseUrl, '/') . '/' . $url;
       // Ensure no double slashes, handle potential windows paths simply
        $url = str_replace(['//', '\\'], '/', $url);
     }
     header("Location: " . $url);
    exit;
}

/**
 * Checks if user is logged in.
 */
function isLoggedIn(): bool {
    if (session_status() == PHP_SESSION_NONE) { @session_start(); }
    return !empty($_SESSION['user_id']);
}

/**
 * Checks if logged in user is an admin.
 */
function isAdmin(): bool {
    return isLoggedIn() && isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

/**
 * Requires login, redirects to login page if not logged in.
 */
function requireLogin(string $redirectUrl = 'login.php'): void {
    if (!isLoggedIn()) {
        $_SESSION['message'] = ['type' => 'error', 'text' => 'Please log in to access this page.'];
        // Store intended destination? Optional for basic version
        // $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
        redirect($redirectUrl);
    }
}

/**
 * Requires admin privileges, redirects if not admin.
 */
function requireAdmin(string $redirectUrl = 'index.php'): void {
    requireLogin(); // Must be logged in first
    if (!isAdmin()) {
        $_SESSION['message'] = ['type' => 'error', 'text' => 'Access Denied: Administrator access required.'];
        redirect($redirectUrl);
    }
}

/**
 * Includes a template part from the includes/templates directory.
 * Passes the $data array which extracts into variables in the template.
 */
function includeTemplate(string $templateName, array $data = []): void {
     // Use a global $db variable if defined in db.php, otherwise ignore
     global $db;

     $templateFile = __DIR__ . '/templates/' . $templateName . '.php';
     if (file_exists($templateFile)) {
        extract($data, EXTR_SKIP); // Make data available as variables
        include $templateFile;
    } else {
         echo "<p style='color:red;'>Error: Template not found '{$templateName}'</p>";
         error_log("Template not found: {$templateFile}");
    }
}

/**
 * Displays and clears session messages.
 */
function displayMessages(): void {
    if (session_status() == PHP_SESSION_NONE) { @session_start(); }
    if (isset($_SESSION['message'])) {
         $msg = $_SESSION['message'];
         $typeClass = escape($msg['type'] ?? 'info'); // 'info', 'success', 'error', 'warning'
         $text = escape($msg['text'] ?? 'Notification');
         echo "<div class='message message-{$typeClass}' role='alert'>{$text}</div>";
        unset($_SESSION['message']); // Clear after displaying
    }
}
?>
