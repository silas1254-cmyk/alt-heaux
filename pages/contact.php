<?php
require '../includes/config.php';

// Get contact information from settings
$contact_email = getSetting('contact_email', '');
$contact_phone = getSetting('contact_phone', '');
$contact_address = getSetting('contact_address', '');
$business_hours = getSetting('business_hours', '');

// Get custom contact page content from settings
$contact_content = getSetting('contact_content', '');
$contact_content_published = getSetting('contact_content_published', '1');

require '../includes/header.php';
?>

<div class="container py-5">
    <!-- Custom Contact Content -->
    <?php if (!empty($contact_content) && $contact_content_published): ?>
        <div class="section-padding" style="background-color: #f5f5f5; margin: -2rem -2rem 2rem -2rem;">
            <div class="container">
                <?php echo $contact_content; ?>
            </div>
        </div>
    <?php endif; ?>

    <h1 class="mb-5">Contact Us</h1>
    
    <?php if (empty($contact_email) && empty($contact_phone) && empty($contact_address)): ?>
        <div class="alert alert-info">
            <p><strong>Contact information coming soon.</strong> Check back later!</p>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-6">
            <h3>Contact Information</h3>
            <?php if (!empty($contact_email)): ?>
                <div class="mb-4">
                    <h5>Email</h5>
                    <p><a href="mailto:<?php echo htmlspecialchars($contact_email); ?>"><?php echo htmlspecialchars($contact_email); ?></a></p>
                </div>
            <?php endif; ?>
            <?php if (!empty($contact_phone)): ?>
                <div class="mb-4">
                    <h5>Phone</h5>
                    <p><a href="tel:<?php echo htmlspecialchars($contact_phone); ?>"><?php echo htmlspecialchars($contact_phone); ?></a></p>
                </div>
            <?php endif; ?>
            <?php if (!empty($contact_address)): ?>
                <div class="mb-4">
                    <h5>Address</h5>
                    <p><?php echo nl2br(htmlspecialchars($contact_address)); ?></p>
                </div>
            <?php endif; ?>
            <?php if (!empty($business_hours)): ?>
                <div class="mb-4">
                    <h5>Business Hours</h5>
                    <p><?php echo nl2br(htmlspecialchars($business_hours)); ?></p>
                </div>
            <?php endif; ?>
        </div>

        <div class="col-md-6">
            <h3>Send us a Message</h3>
            <p>We'd love to hear from you! Send us a message and we'll get back to you as soon as possible.</p>
            
            <form method="POST" action="">
                <div class="mb-3">
                    <label for="name" class="form-label">Name *</label>
                    <input type="text" class="form-control" id="name" name="name" required>
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label">Email *</label>
                    <input type="email" class="form-control" id="email" name="email" required>
                </div>

                <div class="mb-3">
                    <label for="subject" class="form-label">Subject *</label>
                    <input type="text" class="form-control" id="subject" name="subject" required>
                </div>

                <div class="mb-3">
                    <label for="message" class="form-label">Message *</label>
                    <textarea class="form-control" id="message" name="message" rows="5" required></textarea>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-paper-plane"></i> Send Message
                </button>
            </form>
        </div>
    </div>
</div>

<?php require '../includes/footer.php'; ?>
