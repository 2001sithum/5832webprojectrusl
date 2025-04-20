/**
 * Main Interactive Script
 */
'use strict';

document.addEventListener('DOMContentLoaded', () => {
    console.log("Main JS Initialized.");

    // --- Basic Delete/Action Confirmation ---
    document.body.addEventListener('click', function(e) {
        const target = e.target.closest('[data-confirm]');
        if (target) {
            const message = target.getAttribute('data-confirm') || 'Are you sure?';
            if (!confirm(message)) {
                e.preventDefault(); // Prevent default action (submit/link)
            }
        }
    });

    // --- Mobile Navigation Toggle ---
    const navToggle = document.getElementById('nav-toggle');
    const mainNav = document.getElementById('main-nav-links');

    if (navToggle && mainNav) {
        navToggle.addEventListener('click', () => {
            const isVisible = mainNav.getAttribute('data-visible') === 'true';
            mainNav.setAttribute('data-visible', !isVisible);
            navToggle.setAttribute('aria-expanded', !isVisible);
            // Add class for styling active state/transition if needed
             mainNav.classList.toggle('is-active');
             navToggle.classList.toggle('is-active');
        });

        // Optional: Close nav when clicking outside of it
        document.addEventListener('click', (e) => {
            if (!mainNav.contains(e.target) && !navToggle.contains(e.target) && mainNav.getAttribute('data-visible') === 'true') {
                mainNav.setAttribute('data-visible', false);
                 navToggle.setAttribute('aria-expanded', false);
                mainNav.classList.remove('is-active');
                navToggle.classList.remove('is-active');
            }
        });
    } else {
        console.warn("Mobile nav toggle or nav links not found.");
    }


    // --- Animate on Scroll (using Intersection Observer) ---
    const animatedElements = document.querySelectorAll('.animate-on-scroll');

    if ("IntersectionObserver" in window) {
        const observer = new IntersectionObserver((entries, observerInstance) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    observerInstance.unobserve(entry.target); // Only animate once
                }
            });
        }, {
            root: null, // Use the viewport
            threshold: 0.15 // Trigger when 15% of element is visible
        });

        animatedElements.forEach(el => observer.observe(el));

    } else {
         // Fallback for browsers without Intersection Observer (optional: just make them visible)
         console.warn("Intersection Observer not supported, animations may not trigger on scroll.");
        animatedElements.forEach(el => el.classList.add('is-visible'));
    }


    // --- Simple Header Scroll Effect ---
     const header = document.querySelector('.main-header');
     if(header) {
         window.addEventListener('scroll', () => {
            if (window.scrollY > 50) { // Add class after scrolling 50px
                 header.classList.add('scrolled');
            } else {
                 header.classList.remove('scrolled');
            }
        });
     }

     // --- Remove Preload Class on Load ---
      window.addEventListener('load', () => {
          document.body.classList.remove('preload');
     });


    // Add more general interactivity...

}); // End DOMContentLoaded
