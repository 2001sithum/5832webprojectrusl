<?php
/**
 * Flash Message Display Template
 *
 * Retrieves and displays flash messages (success, error, etc.).
 * Called from header.php to display messages globally near the top.
 */

// Get and clear the message from session *once* per request
$flash = getFlashMessage();

?>
<?php if ($flash && isset($flash['type']) && isset($flash['message'])): ?>
    <?php
    // Sanitize type and message for output
    $messageType = escape($flash['type']);
    $messageText = escape($flash['message']); // Escape the main message text

    // Map message types to FontAwesome icons (optional but nice)
    $iconClass = match($messageType) {
        'success' => 'fas fa-check-circle',
        'error' => 'fas fa-exclamation-triangle',
        'info' => 'fas fa-info-circle',
        'warning' => 'fas fa-exclamation-circle',
        default => '', // No icon for unknown types
    };

    // Determine ARIA role based on type for accessibility
    $ariaRole = ($messageType === 'error' || $messageType === 'warning') ? 'alert' : 'status';
    ?>
    <div class="message <?php echo $messageType; ?>" role="<?php echo $ariaRole; ?>" aria-live="<?php echo ($ariaRole === 'alert' ? 'assertive' : 'polite'); ?>">
        <?php if ($iconClass): ?>
            <i class="<?php echo $iconClass; ?>" aria-hidden="true"></i>
        <?php endif; ?>
        <span><?php echo $messageText; // Already escaped ?></span>
        <?php /* Add a close button for manual dismissal if desired */ ?>
        <?php /*
        <button type="button" class="close-message" aria-label="Close message" onclick="this.parentElement.style.display='none';">&times;</button>
        */ ?>
    </div>
<?php endif; ?>
