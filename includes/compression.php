<?php
/**
 * Enable output compression for all pages
 * Add this to the top of index.php and other main entry points
 */

// Enable gzip compression
if (extension_loaded('zlib') && !ini_get('zlib.output_compression')) {
    ob_start('ob_gzhandler');
    // Set compression level (1-9, where 9 is highest compression)
    ini_set('zlib.output_compression_level', 6);
}

// Set headers for better caching
header('Vary: Accept-Encoding');
header('Cache-Control: public, max-age=3600'); // Cache for 1 hour
header('Expires: ' . gmdate('D, d M Y H:i:s \G\M\T', time() + 3600));
