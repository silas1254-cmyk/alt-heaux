# Product Visibility Feature - Implementation Complete

## Overview
Added a **hide/unhide** feature for products to support release scheduling. Products can now be hidden from the public website while remaining in the admin system for editing.

## Feature Details

### What It Does
- **Hide Products**: Individual products can be marked as "hidden" from the public site
- **Unhide Products**: Hidden products can be unhidden to make them visible again
- **Visual Indicator**: Hidden products show a red "Hidden" badge in the admin product list
- **Audit Logging**: All hide/unhide actions are automatically logged for tracking
- **Public Site Filter**: Hidden products automatically don't appear on:
  - Homepage featured products
  - Shop/product listings
  - Category product lists
  - Search results

### Use Cases
- **Release Scheduling**: Hide new products until launch day
- **Coming Soon Products**: Set up product pages before making them public
- **Temporary Removals**: Hide out-of-stock or seasonal products without deleting them
- **Testing**: Hide test products from customers

---

## Setup Instructions

### Step 1: Access Setup Page
Navigate to: `http://localhost/alt-heaux/setup_product_visibility.php`

### Step 2: Run the Migration
- The setup page will check if the database is ready
- If the `is_hidden` column is missing, click "Setup Product Visibility Feature"
- The migration will:
  - Add the `is_hidden` column to the products table (default: 0 = visible)
  - Create indexes for efficient filtering
  - Log the migration in the audit system

### Step 3: Start Using
Once setup is complete, you can immediately start hiding/unhiding products in the admin panel.

---

## How to Use

### In Admin Panel (Products Page)

1. Go to **Admin → Products**
2. Find the product you want to hide/unhide
3. Click the **eye icon** button:
   - **Gray eye** (Warning button) = Product is currently visible
   - **Open eye** (Success button) = Product is currently hidden
4. The page will refresh and show the updated status
5. Hidden products display a red "Hidden" badge

### Visual Indicators
- **Visible products**: Normal display, eye icon button is gray (warning)
- **Hidden products**: Show red "Hidden" badge, eye icon button is green (success)

---

## Technical Implementation

### Database Changes
```sql
-- New column added to products table
ALTER TABLE products 
ADD COLUMN is_hidden TINYINT DEFAULT 0 AFTER display_order;

-- Indexes for performance
CREATE INDEX idx_visibility ON products(is_hidden);
CREATE INDEX idx_category_visibility ON products(category_id, is_hidden);
```

### Code Changes

#### 1. **products_helper.php** (Query Filtering)
```php
// getProductsByCategory() - Added: AND is_hidden = 0
// getFilteredProducts() - Added: WHERE p.is_hidden = 0
```
All product queries now exclude hidden products from public queries.

#### 2. **admin/products.php** (UI & Handler)

**UI Changes:**
- Added toggle visibility button next to Edit/Delete buttons
- Shows eye icon with appropriate color (gray=visible, green=hidden)
- Displays "Hidden" badge on hidden products
- Button text updates to reflect current state

**Handler:**
```php
elseif ($action === 'toggle_visibility') {
    // Toggles is_hidden between 0 and 1
    // Logs the action to audit_log
    // Returns JSON response for AJAX handling
}
```

**JavaScript:**
```javascript
function toggleProductVisibility(productId, isHidden) {
    // AJAX request to toggle status
    // Shows loading state during request
    // Reloads page on success
    // Handles errors gracefully
}
```

#### 3. **index.php** (Homepage)
```php
// Featured products query now includes: WHERE is_hidden = 0
```

---

## Audit Logging Integration

All hide/unhide actions are automatically logged:
- **Category**: Product
- **Action Type**: "Hide" or "Unhide"
- **Description**: Includes product name
- **Timestamp**: Automatically recorded
- **Admin**: Associated with the user who performed the action

View logs in **Admin → Audit Log** with filters for:
- Product changes
- Hide/Unhide actions
- Date ranges
- Specific admins

---

## File Structure

### New Files Created
- `setup_product_visibility.php` - Web-based setup tool
- `migrations_add_product_visibility.sql` - Database migration
- `run_migration_product_visibility.php` - Migration runner (optional)

### Modified Files
- `admin/products.php` - Added UI controls and toggle handler
- `includes/products_helper.php` - Updated queries to filter hidden products
- `index.php` - Updated featured products query

---

## Testing Checklist

- [ ] Run setup via `setup_product_visibility.php`
- [ ] Column `is_hidden` exists in products table
- [ ] Go to Admin → Products
- [ ] Click eye icon to hide a product
- [ ] Verify "Hidden" badge appears
- [ ] Visit homepage - hidden product should not appear
- [ ] Visit shop.php - hidden product should not appear
- [ ] Click eye icon again to unhide the product
- [ ] Verify product reappears on public site
- [ ] Check Admin → Audit Log for hide/unhide entries
- [ ] Verify product images and details are preserved when hidden

---

## Important Notes

### Data Preservation
- Hiding a product **does not delete it**
- All product data, images, variants, and settings are preserved
- The product can be unhidden at any time with no data loss
- Deleted products are permanently removed (different action)

### Performance
- Indexed queries ensure fast filtering even with many hidden products
- Combined index on (category_id, is_hidden) optimizes category listing queries
- No performance impact on product management

### Backward Compatibility
- Existing products default to visible (is_hidden = 0)
- Old data queries automatically adapted
- No breaking changes to existing functionality

---

## Troubleshooting

### Setup Page Shows "Column Missing"
- Database connection may be failing
- Check `includes/config.php` for correct database credentials
- Try running setup page again - it will guide you

### Can't See Eye Icon in Product List
- Clear browser cache
- Make sure you're logged in as admin
- Check browser console for JavaScript errors

### Hidden Products Still Appearing on Site
- Verify the setup completed successfully
- Check that database column was added: `SHOW COLUMNS FROM products;`
- Clear any caching plugins or browser cache
- Verify the query filters are in place (check `products_helper.php`)

### Audit Log Not Showing Hide/Unhide Actions
- Check `includes/audit_helper.php` exists
- Verify `logWebsiteUpdate()` function is being called
- Check database `audit_log` table for entries

---

## Related Documentation
- [Admin Panel Guide](ADMIN_QUICK_REFERENCE.md)
- [Product Management](ADMIN_FEATURES_GUIDE.md)
- [Audit Log System](README_AUDIT_LOG.md)
- [Database Structure](CODE_DOCUMENTATION.md)

---

## Summary

The product visibility feature is now fully implemented and ready to use. Products can be quickly hidden or unhidden from the admin panel with a single click, supporting product release scheduling and inventory management strategies.

**Key Benefits:**
✓ Simple one-click hide/unhide UI
✓ Automatic filtering on public site
✓ Full audit trail of all changes
✓ No data loss when hiding
✓ Zero breaking changes to existing functionality
