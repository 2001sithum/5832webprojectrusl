/**
 * Animations Script using GSAP
 */
'use strict';

// Ensure GSAP and plugins are loaded before running this
window.addEventListener('load', () => {

    if (typeof gsap === 'undefined') {
        console.error("GSAP library not loaded. Animations will not run.");
        return;
    }

    // Register GSAP plugins if they exist
    if (typeof ScrollTrigger !== 'undefined') gsap.registerPlugin(ScrollTrigger);
    if (typeof TextPlugin !== 'undefined') gsap.registerPlugin(TextPlugin);

    console.log("GSAP Animations Initialized.");

    // --- Animated Letters Example ---
    const animatedHeaders = document.querySelectorAll('.animated-letters');
    animatedHeaders.forEach(header => {
         // Simple split - for complex cases use Splitting.js or similar
         const text = header.textContent;
         header.textContent = ''; // Clear original text
         text.split('').forEach(char => {
            const span = document.createElement('span');
            span.textContent = char === ' ' ? '\u00A0' : char; // Use non-breaking space
            span.style.display = 'inline-block'; // Needed for transform
            header.appendChild(span);
         });

        // GSAP Animation Timeline for letters
        gsap.from(header.querySelectorAll('span'), {
            opacity: 0,
            y: 20, // Start 20px below
            // filter: 'blur(5px)', // Optional blur effect
             duration: 0.8, // Faster duration
            stagger: 0.04, // Time between letters
             ease: "power2.out", // Smoother easing
             scrollTrigger: { // Trigger when header enters view
                trigger: header,
                start: "top 85%", // Trigger sooner
                 // markers: true, // Uncomment for debugging ScrollTrigger
                 once: true // Animate only once
            }
         });
    });

    // --- Staggered Card Animation ---
    gsap.from(".event-card.animate-on-scroll, .gallery-item.animate-on-scroll, .team-member-card.animate-on-scroll", {
         duration: 0.5,
         opacity: 0,
        y: 50, // Start lower
         stagger: 0.15, // Stagger animation for each card
         ease: "power1.out",
        scrollTrigger: {
             trigger: ".event-card-grid, .gallery-grid, .team-grid", // Trigger based on the grid container
            start: "top 80%", // Start animation when grid top reaches 80% viewport height
            // markers: true, // Debugging
            toggleActions: "play none none none" // Play animation once on enter
             // If you don't use animate-on-scroll CSS fallback, remove the 'once:true' from here and IntersectionObserver code in main.js
         }
     });

    // --- Parallax Background Effect ---
     gsap.utils.toArray(".parallax-banner").forEach(section => {
         const image = section; // Assume background image is set on the section itself

         // Use background-position for parallax
         gsap.to(image, {
             backgroundPosition: `50% ${window.innerHeight / 2}px`, // Move background position slower than scroll
            ease: "none", // Linear movement
             scrollTrigger: {
                trigger: section,
                 start: "top bottom", // When top of section hits bottom of viewport
                 end: "bottom top", // When bottom of section hits top of viewport
                 scrub: true, // Link animation progress to scroll position
                 // markers: true
             }
         });
     });

     // --- Example: Animate Section Titles on Scroll ---
    gsap.from(".section-title::after", { // Animate the underline pseudo-element
         scaleX: 0, // Start with zero width
        transformOrigin: "left", // Grow from the left
         duration: 0.8,
         ease: "power2.out",
         scrollTrigger: {
             trigger: ".section-title",
            start: "top 90%",
            toggleActions: "play none none reset" // Play on enter, reset if it leaves and re-enters
        }
    });


    // Add more sophisticated GSAP animations here...
    // - 3D card flips
    // - Complex text effects (e.g., TextPlugin)
    // - SVG animations
    // - Physics-based animations if needed (using Physics2DPlugin etc.)


    // --- Ensure 'is-visible' class is added even if only using GSAP, ---
    // --- In case some basic visibility CSS depends on it.        ---
     ScrollTrigger.batch(".animate-on-scroll", {
         onEnter: batch => batch.forEach(el => el.classList.add('is-visible')),
         // Optional: add onLeave if you want to reset visibility for replay
        // onLeaveBack: batch => batch.forEach(el => el.classList.remove('is-visible')),
        start: "top 95%", // Adjust trigger point as needed
        // markers: true
    });

    console.log("GSAP animation setups complete.");

}); // End window 'load'
