   <?php // Main page content ends before this in specific page templates ?>
    </main> <?php // Close main#main-content .container ?>

    <footer>
        <div class="footer-content">
            <p>&copy; <?php echo date('Y'); ?> <?php echo escape(APP_NAME); ?>. All rights reserved. (Demo Project)</p>
            <nav class="footer-links" aria-label="Footer Navigation">
                <a href="<?php echo baseUrl('index.php'); ?>">Home</a>
                <a href="#">Privacy Policy</a> <?php /* Example Link - non-functional */ ?>
                <a href="#">Terms of Service</a> <?php /* Example Link - non-functional */ ?>
                 <?php /* <a href="<?php echo baseUrl('contact.php'); ?>">Contact</a> */ ?>
            </nav>
        </div>
    </footer>

    <?php /* Core JavaScript file - include at the bottom */ ?>
    <script src="<?php echo baseUrl('js/script.js?v=' . filemtime('../public/js/script.js')); ?>"></script> <?php /* Cache busting */ ?>

    <?php /* Include specific JS for the page if needed (passed via $data in renderTemplate) */ ?>
    <?php if (isset($pageScripts) && is_array($pageScripts)): ?>
        <?php foreach ($pageScripts as $jsFile): ?>
            <script src="<?php echo baseUrl('js/' . escape($jsFile)); ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>

</body>
</html>
