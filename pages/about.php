<?php
/**
 * ABOUT PAGE
 * Public-facing about/company information page
 */

require '../includes/config.php';
require '../includes/header.php';
?>

<div class="container py-5">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <!-- Page Header -->
            <div class="mb-5 text-center">
                <h1 class="display-4 fw-bold mb-3">About ALT HEAUX</h1>
                <p class="lead text-muted">Discover our story, mission, and values</p>
            </div>

            <!-- Main Content -->
            <div class="card border-0 shadow-sm mb-5">
                <div class="card-body p-5">
                    <h2 class="h3 mb-4">Our Story</h2>
                    <p class="fs-5 mb-4">
                        ALT HEAUX is an innovative e-commerce platform dedicated to bringing quality products and exceptional service to our customers. 
                        We believe in empowering small businesses and creators by providing them with the tools they need to succeed online.
                    </p>

                    <h2 class="h3 mb-4 mt-5">Our Mission</h2>
                    <p class="fs-5 mb-4">
                        To create an accessible, user-friendly marketplace that connects passionate sellers with enthusiastic customers. 
                        We're committed to simplifying the online shopping experience while maintaining the highest standards of quality and integrity.
                    </p>

                    <h2 class="h3 mb-4 mt-5">Our Values</h2>
                    <div class="row mt-4">
                        <div class="col-md-6 mb-4">
                            <div class="d-flex">
                                <div class="flex-shrink-0">
                                    <div class="flex-grow-1 ms-3">
                                        <h4 class="h5">Quality First</h4>
                                        <p class="text-muted mb-0">We ensure every product meets our high standards before it reaches you.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-4">
                            <div class="d-flex">
                                <div class="flex-shrink-0">
                                    <div class="flex-grow-1 ms-3">
                                        <h4 class="h5">Customer Focused</h4>
                                        <p class="text-muted mb-0">Your satisfaction is our top priority in everything we do.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-4">
                            <div class="d-flex">
                                <div class="flex-shrink-0">
                                    <div class="flex-grow-1 ms-3">
                                        <h4 class="h5">Transparency</h4>
                                        <p class="text-muted mb-0">We're honest about our products, pricing, and policies.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-4">
                            <div class="d-flex">
                                <div class="flex-shrink-0">
                                    <div class="flex-grow-1 ms-3">
                                        <h4 class="h5">Innovation</h4>
                                        <p class="text-muted mb-0">We continuously improve our platform and services.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <h2 class="h3 mb-4 mt-5">Get in Touch</h2>
                    <p class="fs-5 mb-4">
                        Have questions or feedback? We'd love to hear from you! 
                        <a href="<?php echo SITE_URL; ?>pages/contact.php" class="text-decoration-none fw-bold">Contact us here</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require '../includes/footer.php'; ?>
