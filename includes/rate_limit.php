<?php
/**
 * Rate Limiting Helper
 * Prevents API abuse by limiting requests per IP/user
 */

/**
 * Check if a request should be rate limited
 * @param string $identifier - IP address or user ID
 * @param int $max_requests - Maximum requests allowed
 * @param int $time_window - Time window in seconds
 * @return bool - true if should be rate limited, false if allowed
 */
function isRateLimited($identifier, $max_requests = 100, $time_window = 3600) {
    $cache_key = 'rate_limit_' . md5($identifier);
    $cache_file = sys_get_temp_dir() . '/' . $cache_key . '.txt';
    
    $now = time();
    $request_data = [];
    
    // Read existing request log
    if (file_exists($cache_file)) {
        $data = @file_get_contents($cache_file);
        if ($data) {
            $request_data = json_decode($data, true) ?? [];
        }
    }
    
    // Remove old requests outside the time window
    $request_data = array_filter($request_data, function($timestamp) use ($now, $time_window) {
        return ($now - $timestamp) < $time_window;
    });
    
    // Check if over limit
    if (count($request_data) >= $max_requests) {
        return true; // Rate limited
    }
    
    // Add current request
    $request_data[] = $now;
    
    // Save updated log
    @file_put_contents($cache_file, json_encode(array_values($request_data)), LOCK_EX);
    
    return false; // Request allowed
}

/**
 * Get client IP address
 * @return string
 */
function getClientIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
    } else {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
    
    return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '0.0.0.0';
}

/**
 * Check rate limit and return error if exceeded
 * @param string $identifier - Optional custom identifier (default: IP)
 * @param int $max_requests - Max requests allowed
 * @param int $time_window - Time window in seconds
 */
function checkRateLimit($identifier = null, $max_requests = 100, $time_window = 3600) {
    if ($identifier === null) {
        $identifier = getClientIP();
    }
    
    if (isRateLimited($identifier, $max_requests, $time_window)) {
        http_response_code(429);
        exit(json_encode([
            'success' => false,
            'error' => 'Too many requests. Please try again later.'
        ]));
    }
}
