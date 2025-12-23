<?php
/**
 * Form Input Validation Helper
 * Sanitizes and validates common form inputs
 */

/**
 * Sanitize a string input
 * @param string $input - Raw input
 * @return string - Sanitized string
 */
function sanitizeString($input) {
    $input = trim($input);
    $input = stripslashes($input);
    $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
    return $input;
}

/**
 * Validate email address
 * @param string $email - Email to validate
 * @return bool
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate phone number
 * @param string $phone - Phone to validate
 * @return bool
 */
function isValidPhone($phone) {
    return preg_match('/^[\d\s\-\+\(\)]{10,}$/', $phone) === 1;
}

/**
 * Validate number/integer
 * @param mixed $value - Value to validate
 * @param int $min - Minimum value
 * @param int $max - Maximum value
 * @return bool
 */
function isValidNumber($value, $min = null, $max = null) {
    if (!is_numeric($value)) {
        return false;
    }
    
    $num = (int)$value;
    
    if ($min !== null && $num < $min) {
        return false;
    }
    
    if ($max !== null && $num > $max) {
        return false;
    }
    
    return true;
}

/**
 * Validate price (decimal number)
 * @param mixed $value - Value to validate
 * @return bool
 */
function isValidPrice($value) {
    return is_numeric($value) && floatval($value) > 0;
}

/**
 * Validate string length
 * @param string $str - String to validate
 * @param int $min - Minimum length
 * @param int $max - Maximum length
 * @return bool
 */
function isValidLength($str, $min = 1, $max = 255) {
    $len = strlen($str);
    return $len >= $min && $len <= $max;
}

/**
 * Validate date format
 * @param string $date - Date string
 * @param string $format - Expected format (default: Y-m-d)
 * @return bool
 */
function isValidDate($date, $format = 'Y-m-d') {
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}

/**
 * Validate URL
 * @param string $url - URL to validate
 * @return bool
 */
function isValidURL($url) {
    return filter_var($url, FILTER_VALIDATE_URL) !== false;
}

/**
 * Get validation errors array
 * Used to collect multiple validation errors
 */
class ValidationErrors {
    private $errors = [];
    
    public function add($field, $message) {
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = [];
        }
        $this->errors[$field][] = $message;
    }
    
    public function getAll() {
        return $this->errors;
    }
    
    public function hasErrors() {
        return !empty($this->errors);
    }
    
    public function getField($field) {
        return $this->errors[$field] ?? [];
    }
    
    public function toJSON() {
        return json_encode($this->errors);
    }
    
    public function toHTML() {
        if (empty($this->errors)) {
            return '';
        }
        
        $html = '<div class="alert alert-danger"><ul>';
        foreach ($this->errors as $field => $messages) {
            foreach ($messages as $message) {
                $html .= '<li>' . htmlspecialchars($message) . '</li>';
            }
        }
        $html .= '</ul></div>';
        return $html;
    }
}
