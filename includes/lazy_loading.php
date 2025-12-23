<?php
/**
 * Lazy Loading Helper
 * Provides functions to output images with lazy loading support
 */

/**
 * Output lazy-loaded image
 * 
 * @param string $src Image source URL
 * @param string $alt Alt text
 * @param string $class CSS classes (default: "img-fluid")
 * @param string $placeholder Placeholder color (default: "light")
 * @return void
 */
function lazyImage($src, $alt, $class = "img-fluid", $placeholder = "light") {
    $placeholderClass = $placeholder === 'light' ? 'bg-light' : 'bg-secondary';
    ?>
    <img 
        src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 1 1'%3E%3C/svg%3E" 
        data-src="<?php echo htmlspecialchars($src); ?>" 
        alt="<?php echo htmlspecialchars($alt); ?>" 
        class="lazy-load <?php echo htmlspecialchars($class); ?>" 
        loading="lazy"
    >
    <noscript>
        <img src="<?php echo htmlspecialchars($src); ?>" alt="<?php echo htmlspecialchars($alt); ?>" class="<?php echo htmlspecialchars($class); ?>">
    </noscript>
    <?php
}

/**
 * Output inline lazy loading CSS and JS
 * Call this once in your page header or footer
 * 
 * @return void
 */
function includeLazyLoadingScripts() {
    static $included = false;
    if ($included) return;
    $included = true;
    ?>
    <style>
        img.lazy-load {
            display: block;
            width: 100%;
            height: auto;
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 200% 100%;
            animation: lazy-loading-pulse 1.5s infinite;
        }
        
        img.lazy-load.loaded {
            animation: none;
            background: none;
        }
        
        @keyframes lazy-loading-pulse {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }
    </style>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const lazyImages = document.querySelectorAll('img.lazy-load');
            
            if ('IntersectionObserver' in window) {
                const imageObserver = new IntersectionObserver(function(entries, observer) {
                    entries.forEach(function(entry) {
                        if (entry.isIntersecting) {
                            const img = entry.target;
                            img.src = img.dataset.src;
                            img.classList.remove('lazy-load');
                            img.classList.add('loaded');
                            observer.unobserve(img);
                        }
                    });
                }, {
                    rootMargin: '50px'
                });
                
                lazyImages.forEach(img => imageObserver.observe(img));
            } else {
                // Fallback for older browsers
                lazyImages.forEach(img => {
                    img.src = img.dataset.src;
                    img.classList.remove('lazy-load');
                    img.classList.add('loaded');
                });
            }
        });
    </script>
    <?php
}
?>
