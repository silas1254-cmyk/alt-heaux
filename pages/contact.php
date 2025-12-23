<?php
require '../includes/config.php';

// Get contact information from settings
$contact_email = getSetting('contact_email', '');
$contact_phone = getSetting('contact_phone', '');
$contact_address = getSetting('contact_address', '');
$business_hours = getSetting('business_hours', '');

// Get custom contact page content
$contact_content = getSetting('contact_content', '');
$contact_content_published = getSetting('contact_content_published', '1');

// Try to load "Contact" page from database
$contact_page = getPageBySlug('contact', $conn);

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

    <?php if ($contact_page): ?>
        <h1 class="mb-5"><?php echo htmlspecialchars($contact_page['title']); ?></h1>
        <div class="row">
            <div class="col-md-8">
                <?php echo $contact_page['content']; ?>
            </div>
            <div class="col-md-4">
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
        </div>
    <?php else: ?>
        <h1 class="mb-5">Contact Us</h1>
        
        <div class="alert alert-info">
            <p><strong>No contact page configured.</strong> Contact content will be available soon.</p>
        </div>

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
        </div>
    <?php endif; ?>
</div>

<?php require '../includes/footer.php'; ?>
