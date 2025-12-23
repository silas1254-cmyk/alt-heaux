<?php
/**
 * Settings and content management helper functions
 */

// Prevent multiple inclusions
if (defined('CONTENT_HELPER_LOADED')) {
    return;
}
define('CONTENT_HELPER_LOADED', true);

/**
 * Get setting value
 * @param string $key Setting key
 * @param string $default Default value if not found
 * @param mysqli $conn Database connection
 * @return string Setting value
 */
function getSetting($key, $default = '', $conn = null) {
    global $conn;
    $query = "SELECT setting_value FROM settings WHERE setting_key = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('s', $key);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();
        return $row['setting_value'];
    }
    return $default;
}

/**
 * Set setting value
 * @param string $key Setting key
 * @param string $value Setting value
 * @param string $type Setting type (text, email, textarea, etc)
 * @param mysqli $conn Database connection
 * @return bool
 */
function setSetting($key, $value, $type = 'text', $conn = null) {
    global $conn;
    $query = "INSERT INTO settings (setting_key, setting_value, setting_type) VALUES (?, ?, ?) 
              ON DUPLICATE KEY UPDATE setting_value = ?, updated_at = NOW()";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('ssss', $key, $value, $type, $value);
    return $stmt->execute();
}

/**
 * Get all settings
 * @param mysqli $conn Database connection
 * @return array All settings
 */
function getAllSettings($conn = null) {
    global $conn;
    $query = "SELECT * FROM settings ORDER BY setting_key ASC";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

/**
 * Get section content
 * @param string $section_key Section key
 * @param mysqli $conn Database connection
 * @return array|null Section data
 */
function getSection($section_key, $conn = null) {
    global $conn;
    $query = "SELECT * FROM sections WHERE section_key = ? AND active = true";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('s', $section_key);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->num_rows === 1 ? $result->fetch_assoc() : null;
}

/**
 * Update section
 * @param string $section_key Section key
 * @param string $title Section title
 * @param string $content Section content
 * @param string $image_url Image URL
 * @param bool $active Active status
 * @param mysqli $conn Database connection
 * @return bool
 */
function updateSection($section_key, $title, $content, $image_url, $active, $conn = null) {
    global $conn;
    $active = $active ? 1 : 0;
    $query = "INSERT INTO sections (section_key, title, content, image_url, active) 
              VALUES (?, ?, ?, ?, ?) 
              ON DUPLICATE KEY UPDATE 
              title = ?, content = ?, image_url = ?, active = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('ssssisssi', $section_key, $title, $content, $image_url, $active, $title, $content, $image_url, $active);
    return $stmt->execute();
}

/**
 * Get menu items
 * @param int $parent_id Parent menu ID (null for top level)
 * @param mysqli $conn Database connection
 * @return array Menu items
 */
function getMenuItems($parent_id = null, $conn = null) {
    global $conn;
    if ($parent_id === null) {
        $query = "SELECT * FROM menu_items WHERE parent_id IS NULL AND active = true ORDER BY position ASC";
        $stmt = $conn->prepare($query);
    } else {
        $query = "SELECT * FROM menu_items WHERE parent_id = ? AND active = true ORDER BY position ASC";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('i', $parent_id);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

/**
 * Get all menu items (for admin)
 * @param mysqli $conn Database connection
 * @return array All menu items
 */
function getAllMenuItems($conn = null) {
    global $conn;
    $query = "SELECT * FROM menu_items ORDER BY position ASC";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

/**
 * Create menu item
 * @param string $label Menu label
 * @param string $url Menu URL
 * @param int $position Position
 * @param int|null $parent_id Parent menu ID
 * @param bool $active Active status
 * @param mysqli $conn Database connection
 * @return bool
 */
function createMenuItem($label, $url, $position, $parent_id = null, $active = true, $conn = null) {
    global $conn;
    $query = "INSERT INTO menu_items (label, url, position, parent_id, active) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    $active_int = $active ? 1 : 0;
    $stmt->bind_param('ssiii', $label, $url, $position, $parent_id, $active_int);
    return $stmt->execute();
}

/**
 * Update menu item
 * @param int $id Menu ID
 * @param string $label Menu label
 * @param string $url Menu URL
 * @param int $position Position
 * @param bool $active Active status
 * @param mysqli $conn Database connection
 * @return bool
 */
function updateMenuItem($id, $label, $url, $position, $active, $conn = null) {
    global $conn;
    $active_int = $active ? 1 : 0;
    $query = "UPDATE menu_items SET label = ?, url = ?, position = ?, active = ? WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('ssiii', $label, $url, $position, $active_int, $id);
    return $stmt->execute();
}

/**
 * Delete menu item
 * @param int $id Menu ID
 * @param mysqli $conn Database connection
 * @return bool
 */
function deleteMenuItem($id, $conn = null) {
    global $conn;
    $query = "DELETE FROM menu_items WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $id);
    return $stmt->execute();
}

/**
 * Get sliders
 * @param mysqli $conn Database connection
 * @return array Sliders
 */
function getSliders($conn = null) {
    global $conn;
    $query = "SELECT * FROM sliders WHERE active = true ORDER BY position ASC";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

/**
 * Get all sliders (for admin)
 * @param mysqli $conn Database connection
 * @return array All sliders
 */
function getAllSliders($conn = null) {
    global $conn;
    $query = "SELECT * FROM sliders ORDER BY position ASC";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

/**
 * Create slider
 * @param string $title Slider title
 * @param string $description Slider description
 * @param string $image_url Image URL
 * @param string $button_text Button text
 * @param string $button_url Button URL
 * @param int $position Position
 * @param bool $active Active status
 * @param mysqli $conn Database connection
 * @return bool
 */
function createSlider($title, $description, $image_url, $button_text, $button_url, $position, $active = true, $conn = null) {
    global $conn;
    $query = "INSERT INTO sliders (title, description, image_url, button_text, button_url, position, active) 
              VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    $active_int = $active ? 1 : 0;
    $stmt->bind_param('ssssiii', $title, $description, $image_url, $button_text, $button_url, $position, $active_int);
    return $stmt->execute();
}

/**
 * Update slider
 * @param int $id Slider ID
 * @param string $title Slider title
 * @param string $description Slider description
 * @param string $image_url Image URL
 * @param string $button_text Button text
 * @param string $button_url Button URL
 * @param int $position Position
 * @param bool $active Active status
 * @param mysqli $conn Database connection
 * @return bool
 */
function updateSlider($id, $title, $description, $image_url, $button_text, $button_url, $position, $active, $conn = null) {
    global $conn;
    $active_int = $active ? 1 : 0;
    $query = "UPDATE sliders SET title = ?, description = ?, image_url = ?, button_text = ?, button_url = ?, position = ?, active = ? WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('ssssssii', $title, $description, $image_url, $button_text, $button_url, $position, $active_int, $id);
    return $stmt->execute();
}

/**
 * Delete slider
 * @param int $id Slider ID
 * @param mysqli $conn Database connection
 * @return bool
 */
function deleteSlider($id, $conn = null) {
    global $conn;
    $query = "DELETE FROM sliders WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $id);
    return $stmt->execute();
}
?>
