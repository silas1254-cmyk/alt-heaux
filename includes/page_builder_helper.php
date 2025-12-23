<?php
/**
 * Page Builder Helper Functions
 */

if (defined('PAGE_BUILDER_HELPER_LOADED')) {
    return;
}
define('PAGE_BUILDER_HELPER_LOADED', true);

// ============================================================================
// PAGE CRUD FUNCTIONS
// ============================================================================

/**
 * Get all pages
 */
function getAllPages($conn = null) {
    global $conn;
    $query = "SELECT * FROM pages ORDER BY created_at DESC";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

/**
 * Get published pages
 */
function getPublishedPages($conn = null) {
    global $conn;
    $query = "SELECT * FROM pages WHERE is_published = 1 AND is_hidden = 0 ORDER BY created_at DESC";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

/**
 * Get page by ID
 */
function getPageById($id, $conn = null) {
    global $conn;
    $query = "SELECT * FROM pages WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->num_rows === 1 ? $result->fetch_assoc() : null;
}

/**
 * Get page by slug
 */
function getPageBySlug($slug, $conn = null) {
    global $conn;
    $query = "SELECT * FROM pages WHERE slug = ? AND is_published = 1 AND is_hidden = 0";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('s', $slug);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->num_rows === 1 ? $result->fetch_assoc() : null;
}

/**
 * Create new page
 */
function createPage($title, $slug, $content = '[]', $admin_id, $conn = null) {
    global $conn;
    $query = "INSERT INTO pages (title, slug, content, created_by, updated_by) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('sssii', $title, $slug, $content, $admin_id, $admin_id);
    if ($stmt->execute()) {
        $id = $stmt->insert_id;
        logWebsiteUpdate('Page', "Created page: $title", "New page created with slug: $slug", 'Create', $conn);
        return $id;
    }
    return false;
}

/**
 * Update page
 */
function updatePage($id, $title, $slug, $meta_description, $meta_keywords, $featured_image, $is_published, $is_hidden, $admin_id, $conn = null) {
    global $conn;
    $query = "UPDATE pages SET title = ?, slug = ?, meta_description = ?, meta_keywords = ?, featured_image = ?, is_published = ?, is_hidden = ?, updated_by = ? WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('ssssissii', $title, $slug, $meta_description, $meta_keywords, $featured_image, $is_published, $is_hidden, $admin_id, $id);
    if ($stmt->execute()) {
        logWebsiteUpdate('Page', "Updated page: $title", "Page details modified", 'Update', $conn);
        return true;
    }
    return false;
}

/**
 * Update page content (builder blocks)
 */
function updatePageContent($id, $content, $admin_id, $conn = null) {
    global $conn;
    $query = "UPDATE pages SET content = ?, updated_by = ?, updated_at = NOW() WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('sii', $content, $admin_id, $id);
    if ($stmt->execute()) {
        logWebsiteUpdate('Page', "Updated page content", "Page builder blocks modified", 'Update', $conn);
        return true;
    }
    return false;
}

/**
 * Delete page
 */
function deletePage($id, $conn = null) {
    global $conn;
    $page = getPageById($id, $conn);
    $query = "DELETE FROM pages WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $id);
    if ($stmt->execute()) {
        logWebsiteUpdate('Page', "Deleted page: {$page['title']}", "Page removed from system", 'Delete', $conn);
        return true;
    }
    return false;
}

// ============================================================================
// PAGE BLOCK FUNCTIONS
// ============================================================================

/**
 * Get all page blocks
 */
function getAllPageBlocks($conn = null) {
    global $conn;
    $query = "SELECT * FROM page_blocks WHERE is_active = 1 ORDER BY display_order ASC";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

/**
 * Get page block by ID
 */
function getPageBlockById($id, $conn = null) {
    global $conn;
    $query = "SELECT * FROM page_blocks WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->num_rows === 1 ? $result->fetch_assoc() : null;
}

/**
 * Create page block
 */
function createPageBlock($block_type, $title, $content, $admin_id, $conn = null) {
    global $conn;
    $query = "INSERT INTO page_blocks (block_type, title, content, created_by, updated_by) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('sssii', $block_type, $title, $content, $admin_id, $admin_id);
    if ($stmt->execute()) {
        logWebsiteUpdate('PageBlock', "Created block: $title", "New reusable content block created", 'Create', $conn);
        return $stmt->insert_id;
    }
    return false;
}

/**
 * Update page block
 */
function updatePageBlock($id, $block_type, $title, $content, $secondary_content, $image_url, $button_text, $button_url, $button_style, $admin_id, $conn = null) {
    global $conn;
    $query = "UPDATE page_blocks SET block_type = ?, title = ?, content = ?, secondary_content = ?, image_url = ?, button_text = ?, button_url = ?, button_style = ?, updated_by = ? WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('ssssssssi', $block_type, $title, $content, $secondary_content, $image_url, $button_text, $button_url, $button_style, $admin_id, $id);
    if ($stmt->execute()) {
        logWebsiteUpdate('PageBlock', "Updated block: $title", "Content block modified", 'Update', $conn);
        return true;
    }
    return false;
}

/**
 * Delete page block
 */
function deletePageBlock($id, $conn = null) {
    global $conn;
    $query = "DELETE FROM page_blocks WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $id);
    if ($stmt->execute()) {
        logWebsiteUpdate('PageBlock', "Deleted block", "Content block removed", 'Delete', $conn);
        return true;
    }
    return false;
}

// ============================================================================
// PAGE TEMPLATE FUNCTIONS
// ============================================================================

/**
 * Get all templates
 */
function getAllPageTemplates($conn = null) {
    global $conn;
    $query = "SELECT * FROM page_templates WHERE is_active = 1 ORDER BY name ASC";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

/**
 * Get template by ID
 */
function getPageTemplateById($id, $conn = null) {
    global $conn;
    $query = "SELECT * FROM page_templates WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->num_rows === 1 ? $result->fetch_assoc() : null;
}

/**
 * Render page with builder content
 */
function renderPageBuilder($content, $conn = null) {
    if (is_string($content)) {
        $content = json_decode($content, true) ?? [];
    }

    if (empty($content)) {
        return '';
    }

    $html = '<div class="page-builder-content">';

    foreach ($content as $block) {
        $type = $block['type'] ?? 'text';
        $html .= renderPageBlock($block);
    }

    $html .= '</div>';
    return $html;
}

/**
 * Render individual page block
 */
function renderPageBlock($block, $conn = null) {
    global $conn;
    $type = $block['type'] ?? 'text';
    
    switch ($type) {
        case 'heading':
            $level = $block['level'] ?? 2;
            $text = htmlspecialchars($block['content'] ?? '');
            $align = $block['alignment'] ?? 'left';
            return "<h{$level} style=\"text-align: {$align};\">{$text}</h{$level}>";

        case 'text':
            $content = $block['content'] ?? '';
            return "<div class=\"builder-text-block\">{$content}</div>";

        case 'image':
            $src = htmlspecialchars($block['image'] ?? '');
            $alt = htmlspecialchars($block['alt'] ?? '');
            $width = $block['width'] ?? '100%';
            return "<img src=\"{$src}\" alt=\"{$alt}\" style=\"width: {$width}; max-width: 100%;\">";

        case 'button':
            $text = htmlspecialchars($block['text'] ?? 'Click Me');
            $url = htmlspecialchars($block['url'] ?? '#');
            $style = $block['style'] ?? 'primary';
            $align = $block['alignment'] ?? 'left';
            return "<div style=\"text-align: {$align};\"><a href=\"{$url}\" class=\"btn btn-{$style}\">{$text}</a></div>";

        case 'divider':
            return '<hr class="builder-divider" style="margin: 2rem 0; border: none; border-top: 2px solid var(--border-color);">';

        case 'gallery':
            $images = $block['images'] ?? [];
            $html = '<div class="builder-gallery" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem;">';
            foreach ($images as $img) {
                $src = htmlspecialchars($img['src'] ?? '');
                $alt = htmlspecialchars($img['alt'] ?? '');
                $html .= "<img src=\"{$src}\" alt=\"{$alt}\" style=\"width: 100%; border-radius: 8px;\">";
            }
            $html .= '</div>';
            return $html;

        case 'feature':
            $title = htmlspecialchars($block['title'] ?? '');
            $content = $block['content'] ?? '';
            $icon = htmlspecialchars($block['icon'] ?? '');
            return "
                <div class=\"builder-feature\" style=\"padding: 1.5rem; background: var(--primary-light); border-radius: 8px; margin-bottom: 1.5rem;\">
                    <div style=\"font-size: 2rem; margin-bottom: 0.75rem;\">$icon</div>
                    <h3 style=\"margin-bottom: 0.75rem;\">$title</h3>
                    <div>$content</div>
                </div>
            ";

        case 'testimonial':
            $quote = $block['quote'] ?? '';
            $author = htmlspecialchars($block['author'] ?? 'Anonymous');
            $rating = $block['rating'] ?? 5;
            $stars = str_repeat('★', $rating) . str_repeat('☆', 5 - $rating);
            return "
                <div class=\"builder-testimonial\" style=\"padding: 1.5rem; background: var(--primary-light); border-left: 4px solid var(--accent-gold); border-radius: 8px; margin-bottom: 1.5rem;\">
                    <div style=\"color: var(--accent-gold); margin-bottom: 0.5rem;\">$stars</div>
                    <p style=\"font-style: italic; margin-bottom: 1rem;\">\"$quote\"</p>
                    <div style=\"color: var(--text-secondary); font-weight: 600;\">— $author</div>
                </div>
            ";

        case 'cta':
            $title = htmlspecialchars($block['title'] ?? '');
            $subtitle = $block['subtitle'] ?? '';
            $button_text = htmlspecialchars($block['button_text'] ?? 'Learn More');
            $button_url = htmlspecialchars($block['button_url'] ?? '#');
            return "
                <div class=\"builder-cta\" style=\"padding: 2rem; background: linear-gradient(135deg, var(--primary-light) 0%, var(--primary-medium) 100%); border: 2px solid var(--accent-gold); border-radius: 8px; text-align: center; margin: 2rem 0;\">
                    <h2 style=\"color: var(--accent-gold); margin-bottom: 0.75rem;\">$title</h2>
                    <p style=\"margin-bottom: 1.5rem; color: var(--text-secondary);\">$subtitle</p>
                    <a href=\"{$button_url}\" class=\"btn btn-primary\">$button_text</a>
                </div>
            ";

        case 'columns':
            $column1 = $block['column1'] ?? '';
            $column2 = $block['column2'] ?? '';
            return "
                <div style=\"display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin: 2rem 0;\">
                    <div>$column1</div>
                    <div>$column2</div>
                </div>
            ";

        default:
            return '';
    }
}

/**
 * Validate page slug
 */
function isSlugAvailable($slug, $exclude_id = null, $conn = null) {
    global $conn;
    $query = "SELECT id FROM pages WHERE slug = ?";
    if ($exclude_id) {
        $query .= " AND id != ?";
    }
    
    $stmt = $conn->prepare($query);
    if ($exclude_id) {
        $stmt->bind_param('si', $slug, $exclude_id);
    } else {
        $stmt->bind_param('s', $slug);
    }
    $stmt->execute();
    return $stmt->get_result()->num_rows === 0;
}

/**
 * Generate slug from title
 */
function generateSlug($title) {
    $slug = strtolower(trim($title));
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
    $slug = trim($slug, '-');
    return $slug;
}
?>
