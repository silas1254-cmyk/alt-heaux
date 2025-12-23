    <footer class="bg-dark text-white py-5 mt-5">
        <div class="container">
            <div class="row mb-5">
                <div class="col-md-4 mb-4">
                    <h5 style="color: var(--accent); font-weight: 700; text-transform: uppercase; letter-spacing: 1px;"><?php echo SITE_NAME; ?></h5>
                    <p class="text-white-50"><?php echo getSetting('site_description', 'Premium clothing brand for the modern lifestyle.'); ?></p>
                    <?php 
                        $social_links = [
                            'facebook' => ['icon' => 'fab fa-facebook', 'key' => 'social_facebook'],
                            'instagram' => ['icon' => 'fab fa-instagram', 'key' => 'social_instagram'],
                            'twitter' => ['icon' => 'fab fa-twitter', 'key' => 'social_twitter'],
                        ];
                    ?>
                    <div class="mt-3 social-icons">
                        <?php foreach ($social_links as $platform => $data): 
                            $url = getSetting($data['key'], '');
                            if (!empty($url)):
                        ?>
                            <a href="<?php echo htmlspecialchars($url); ?>" class="text-white-50 me-3" target="_blank" title="Follow us on <?php echo ucfirst($platform); ?>">
                                <i class="<?php echo $data['icon']; ?>"></i>
                            </a>
                        <?php 
                            endif;
                        endforeach; 
                        ?>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <h5 style="color: var(--accent); font-weight: 700; text-transform: uppercase; letter-spacing: 1px;">Quick Links</h5>
                    <ul class="list-unstyled">
                        <?php 
                            $footer_links = getMenuItems(null);
                            if (!empty($footer_links)):
                                foreach ($footer_links as $link):
                        ?>
                            <li class="mb-2"><a href="<?php echo htmlspecialchars($link['url']); ?>" class="text-white-50" style="transition: color 0.3s ease;"><?php echo htmlspecialchars($link['label']); ?></a></li>
                        <?php 
                                endforeach;
                            else:
                        ?>
                            <li class="mb-2"><a href="<?php echo SITE_URL; ?>index.php" class="text-white-50">Home</a></li>
                            <li class="mb-2"><a href="<?php echo SITE_URL; ?>pages/shop.php" class="text-white-50">Shop</a></li>
                            <li class="mb-2"><a href="<?php echo SITE_URL; ?>pages/contact.php" class="text-white-50">Contact</a></li>
                        <?php endif; ?>
                    </ul>
                </div>
                <div class="col-md-4 mb-4">
                    <h5 style="color: var(--accent); font-weight: 700; text-transform: uppercase; letter-spacing: 1px;">Contact</h5>
                    <p class="text-white-50 mb-2">
                        <?php 
                            $email = getSetting('contact_email', 'info@altheaux.com');
                            $phone = getSetting('contact_phone', '(555) 123-4567');
                            if (!empty($email)):
                                echo "<i class='fas fa-envelope me-2'></i><a href='mailto:" . htmlspecialchars($email) . "' class='text-white-50'>" . htmlspecialchars($email) . "</a>";
                            endif;
                        ?>
                    </p>
                    <?php if (!empty($phone)): ?>
                        <p class="text-white-50">
                            <i class="fas fa-phone me-2"></i><?php echo htmlspecialchars($phone); ?>
                        </p>
                    <?php endif; ?>
                </div>
            </div>
            <div style="border-top: 1px solid rgba(255,255,255,0.1); padding-top: 2rem;">
                <div class="text-center text-white-50">
                    <p class="mb-0"><?php echo getSetting('footer_copyright', '&copy; 2025 ' . SITE_NAME . '. All rights reserved.'); ?></p>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo SITE_URL; ?>js/main.js"></script>
</body>
</html>
