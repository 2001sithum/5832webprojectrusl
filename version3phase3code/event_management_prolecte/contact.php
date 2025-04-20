 <?php
require_once 'includes/db.php'; // For config and session maybe
require_once 'includes/functions.php';

$pageTitle = "Contact Us";
$formSubmitted = false;
$submitErrors = [];
$formData = ['name' => '', 'email' => '', 'subject' => '', 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData['name'] = trim($_POST['name'] ?? '');
    $formData['email'] = trim($_POST['email'] ?? '');
    $formData['subject'] = trim($_POST['subject'] ?? 'General Inquiry');
    $formData['message'] = trim($_POST['message'] ?? '');

    // Basic Validation
    if (empty($formData['name'])) $submitErrors[] = "Your name is required.";
    if (empty($formData['email'])) $submitErrors[] = "Your email is required.";
    elseif (!filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) $submitErrors[] = "Please provide a valid email address.";
    if (empty($formData['message'])) $submitErrors[] = "Please enter your message.";
    elseif (strlen($formData['message']) < 10) $submitErrors[] = "Message should be at least 10 characters.";
    // Simple honeypot (optional)
     if (!empty($_POST['website_hp'])) { die("BOT DETECTED"); }


    if (empty($submitErrors)) {
         // --- Process Submission ---
         // Ideally use PHPMailer or similar library, mail() is basic
        $to = ADMIN_EMAIL;
        $subject = "Contact Form: " . $formData['subject'];
         $body = "Message from: {$formData['name']} ({$formData['email']})\n\n";
        $body .= "Subject: {$formData['subject']}\n\n";
         $body .= "Message:\n------------------\n{$formData['message']}\n------------------";
         $headers = "From: \"{$formData['name']}\" <contactform@{$_SERVER['HTTP_HOST']}>\r\n"; // Basic From
        $headers .= "Reply-To: {$formData['email']}\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion();

        if (mail($to, $subject, $body, $headers)) {
            $_SESSION['message'] = ['type' => 'success', 'text' => "Thanks, {$formData['name']}! Your message has been sent."];
            $formSubmitted = true;
            $formData = ['name' => '', 'email' => '', 'subject' => '', 'message' => '']; // Clear form
             // Optional redirect after success: redirect('contact.php?success=1');
        } else {
             $submitErrors[] = "Sorry, there was a technical problem sending your message. Please try again later or email us directly at " . escape(ADMIN_EMAIL);
            error_log("Contact form mail() failed. To: {$to}, From: {$formData['email']}, Subject: {$formData['subject']}");
        }
    }

     // Set flash errors if validation/sending failed
    if (!empty($submitErrors)) {
        $_SESSION['message'] = ['type' => 'error', 'text' => implode('<br>', $submitErrors)];
    }

} // End POST handling

includeTemplate('header', ['pageTitle' => $pageTitle]);
?>
    <section class="content-section">
         <h1 class="page-title animated-letters">Get In Touch</h1>
         <p class="lead text-center">We'd love to hear from you. Fill out the form below or use the contact details.</p>

        <?php /* displayMessages(); // Handled in header */ ?>

        <div class="contact-container">
            <div class="contact-details animate-on-scroll fade-in-left">
                 <h2>Contact Information</h2>
                <p><i class="fas fa-map-marker-alt"></i> 123 Event Ln, Celebration City, 90210</p>
                <p><i class="fas fa-phone"></i> <a href="tel:+15559876543">(555) 987-6543</a></p>
                <p><i class="fas fa-envelope"></i> <a href="mailto:<?php e(ADMIN_EMAIL); ?>"><?php e(ADMIN_EMAIL); ?></a></p>
                <div class="contact-map mt-3">
                     <?php /* Embed map later - Placeholder */ ?>
                    <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d387193.3157681987!2d-74.25986979873255!3d40.69714941160257!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x89c24fa5d33f083b%3A0xc80b8f06e177fe62!2sNew%20York%2C%20NY%2C%20USA!5e0!3m2!1sen!2suk!4v1678886565352!5m2!1sen!2suk" width="100%" height="250" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade" title="Location Map"></iframe>
                 </div>
            </div>

            <div class="contact-form-wrapper animate-on-scroll fade-in-right">
                 <h2>Send a Message</h2>
                 <?php if ($formSubmitted && empty($submitErrors)) : ?>
                    <?php /* Message displayed by flash message system */ ?>
                    <p class="text-center"><a href="index.php" class="button">Back to Home</a></p>
                 <?php else: ?>
                <form id="contact-form" action="contact.php" method="POST" novalidate>
                    <?php /* Honeypot - Simple bot trap */ ?>
                     <input type="text" name="website_hp" style="display:none;" tabindex="-1" autocomplete="off">

                     <div class="form-group">
                        <label for="name">Your Name <span class="required">*</span></label>
                        <input type="text" id="name" name="name" class="form-input" value="<?php e($formData['name']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Your Email <span class="required">*</span></label>
                        <input type="email" id="email" name="email" class="form-input" value="<?php e($formData['email']); ?>" required>
                    </div>
                     <div class="form-group">
                         <label for="subject">Subject</label>
                        <input type="text" id="subject" name="subject" class="form-input" value="<?php e($formData['subject']); ?>">
                     </div>
                    <div class="form-group">
                         <label for="message">Message <span class="required">*</span></label>
                        <textarea id="message" name="message" rows="6" class="form-textarea" required minlength="10"><?php e($formData['message']); ?></textarea>
                     </div>
                     <button type="submit" class="button button-primary full-width">
                         <i class="fas fa-paper-plane"></i> Send Message
                     </button>
                </form>
                <?php endif; ?>
            </div>
         </div>
    </section>

    <?php /* Basic styles for contact page layout (add to main.css) */ ?>
    <style>
        .contact-container { display: grid; grid-template-columns: 1fr; gap: var(--spacing-xl); }
        @media (min-width: 768px) { .contact-container { grid-template-columns: 1fr 1.5fr; } } /* Sidebar / Form */
        .contact-details h2, .contact-form-wrapper h2 { margin-bottom: var(--spacing-md); border-bottom: 1px solid var(--border-color); padding-bottom: var(--spacing-sm); font-size: 1.5rem;}
         .contact-details p { display: flex; align-items: center; gap: var(--spacing-sm); margin-bottom: var(--spacing-sm); color: var(--text-medium); }
         .contact-details i { color: var(--primary-color); width: 20px; text-align: center; }
         .contact-map iframe { border-radius: var(--border-radius-md); filter: grayscale(60%) contrast(1.1); }
    </style>

<?php includeTemplate('footer'); ?>
