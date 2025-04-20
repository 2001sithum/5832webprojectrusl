        <?php // <!-- Page Specific Content Ends Here --> ?>
    </main> <?php // End main#main-content ?>

    <footer class="site-footer">
        <div class="footer-container">
            <div class="footer-brand">
                <h4><?php echo escape(APP_NAME); ?></h4>
                <p>Bringing events to life.</p>
            </div>
            <div class="footer-nav">
                <h5>Quick Links</h5>
                <ul>
                    <li><a href="index.php">Home</a></li>
                    <li><a href="events.php">Events</a></li>
                    <li><a href="contact.php">Contact Us</a></li>
                     <li><a href="gallery.php">Gallery</a></li>
                     <li><a href="team.php">Our Team</a></li>
                </ul>
            </div>
            <div class="footer-contact">
                <h5>Contact Info</h5>
                <p><i class="fas fa-envelope"></i> <a href="mailto:<?php echo escape(ADMIN_EMAIL); ?>"><?php echo escape(ADMIN_EMAIL); ?></a></p>
                <p><i class="fas fa-phone"></i> <a href="tel:+1234567890">(123) 456-7890</a></p> <?php /* Example */ ?>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; <?php echo date('Y'); ?> <?php echo escape(APP_NAME); ?>. All Rights Reserved.</p>
            <?php /* Optional: Add social media links here */ ?>
        </div>
    </footer>

    <?php /* Core JS for interactivity */ ?>
    <script src="js/main.js?v=<?php echo filemtime('js/main.js'); ?>"></script>
    <?php /* Animation specific JS */ ?>
    <script src="js/animations.js?v=<?php echo filemtime('js/animations.js'); ?>"></script>

    <?php /* Remove preload class once JS runs to prevent FOUC */ ?>
    <script> window.addEventListener('load', () => document.body.classList.remove('preload')); </script>
</body>
</html>
