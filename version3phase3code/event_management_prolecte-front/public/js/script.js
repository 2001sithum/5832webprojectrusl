/**
 * Global Site JavaScript - Event Ticket Pro Demo
 *
 * Includes basic interactions:
 * - Confirmation dialogs for sensitive actions.
 * - Auto-hiding flash messages.
 * - Active navigation link highlighting.
 * - Mobile navigation toggle.
 * - Minimal additional enhancements.
 */

// Strict mode helps catch common coding errors and unsafe actions
"use strict";

document.addEventListener('DOMContentLoaded', () => {

    console.log("Event Ticket Pro JS Initialized (Demo Version).");

    /**
     * Confirmation Dialogs
     * Adds confirmation prompt to elements with 'data-confirm' attribute.
     */
    const confirmElements = document.querySelectorAll('[data-confirm]');
    confirmElements.forEach(element => {
        // Use 'submit' event for forms, 'click' for links/buttons
        const eventType = element.tagName === 'FORM' ? 'submit' : 'click';

        element.addEventListener(eventType, (event) => {
            const message = element.getAttribute('data-confirm') || 'Are you sure you want to proceed?';
            // Display the confirmation dialog
            if (!confirm(message)) {
                event.preventDefault(); // Prevent default action (form submission or link navigation)
                console.log('Action cancelled by user.');
            } else {
                 console.log('Action confirmed by user.');
                 // Optional: Add a loading indicator or disable the button after confirmation
                 if (element.tagName === 'BUTTON' || (element.tagName === 'INPUT' && element.type === 'submit')) {
                    element.disabled = true;
                    element.style.opacity = '0.7';
                    element.textContent = 'Processing...'; // Example text change
                 }
            }
        });
    });


    /**
     * Auto-hide Flash Messages
     * Fades out elements with class 'message' after a delay.
     */
    const flashMessages = document.querySelectorAll('.message[role="alert"], .message[role="status"]');
    if (flashMessages.length > 0) {
        flashMessages.forEach(message => {
            setTimeout(() => {
                message.style.transition = 'opacity 0.5s ease-out';
                message.style.opacity = '0';
                // Remove the element from DOM after fade out animation completes
                setTimeout(() => message.remove(), 550); // Delay should match or slightly exceed transition duration
            }, 6000); // Message disappears after 6 seconds
        });
    }


    /**
     * Active Navigation Link Highlighting
     * Adds 'active' class to the navigation link corresponding to the current page.
     */
    try {
        const currentPath = window.location.pathname;
        const navLinks = document.querySelectorAll('.navbar-links a'); // Target links within the main nav

        navLinks.forEach(link => {
            const linkUrl = new URL(link.href); // Create URL object from href
            const linkPath = linkUrl.pathname;

            // Remove existing active class and aria-current attribute
            link.classList.remove('active');
            link.removeAttribute('aria-current');

            // Basic comparison: Exact match or matching index.php for root path
            // This might need refinement based on server config (e.g., if index.php is hidden)
            if (linkPath === currentPath ||
               (currentPath === '/' && linkPath.endsWith('/index.php')) ||
               (currentPath.endsWith('/index.php') && linkPath === '/'))
            {
                link.classList.add('active');
                link.setAttribute('aria-current', 'page'); // Set ARIA attribute for accessibility
            }
            // More robust check: Check if current path starts with link path (for nested pages)
            // Be careful not to mark parent links active if child link is more specific
            // Example: if link is /admin.php and current is /admin_users.php, don't mark /admin.php active unless desired
            // else if (currentPath.startsWith(linkPath) && linkPath !== '/') {
                 // Add logic here if needed, comparing path segments might be better
            // }
        });
    } catch (e) {
        console.error("Error setting active navigation link:", e);
    }


    /**
     * Mobile Navigation Toggle
     * Handles the hamburger menu button click to show/hide mobile navigation.
     */
    const navbarToggle = document.querySelector('.navbar-toggle');
    const mainNavLinks = document.querySelector('#main-navigation'); // Target the nav element itself

    if (navbarToggle && mainNavLinks) {
        navbarToggle.addEventListener('click', () => {
            const isExpanded = navbarToggle.getAttribute('aria-expanded') === 'true';

            // Toggle ARIA attribute
            navbarToggle.setAttribute('aria-expanded', !isExpanded);

            // Toggle the 'is-active' class on the navigation links container
            mainNavLinks.classList.toggle('is-active');

            // Optional: Change hamburger icon to close icon (X)
            const icon = navbarToggle.querySelector('i');
            if (icon) {
                icon.classList.toggle('fa-bars'); // Toggle hamburger icon
                icon.classList.toggle('fa-times'); // Toggle close icon
            }
        });
    } else {
        if (!navbarToggle) console.warn("Mobile navigation toggle button not found.");
        if (!mainNavLinks) console.warn("Mobile navigation links container (#main-navigation) not found.");
    }


    // --- Add other simple JS enhancements below ---

    /**
     * Example: Smooth scrolling for in-page anchors (if any are used)
     * Selects all anchor links starting with '#'
     */
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(event) {
            const hrefAttribute = this.getAttribute('href');
            // Ensure it's not just "#" and is a valid selector
            if (hrefAttribute && hrefAttribute.length > 1 && hrefAttribute.startsWith('#')) {
                try {
                    const targetElement = document.querySelector(hrefAttribute);
                    if (targetElement) {
                         event.preventDefault(); // Prevent default jump
                         targetElement.scrollIntoView({
                             behavior: 'smooth', // Enable smooth scrolling
                             block: 'start'      // Align to top of the target element
                         });
                         // Optional: Set focus to the target for accessibility
                         targetElement.focus({ preventScroll: true }); // preventScroll stops it from jumping again after smooth scroll
                    }
                } catch (e) {
                    // Handle potential invalid selector errors gracefully
                    console.warn(`Smooth scroll target selector error for ${hrefAttribute}: ${e.message}`);
                }
            }
        });
    });


}); // End DOMContentLoaded Wrapper
