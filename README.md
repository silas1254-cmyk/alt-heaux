# ALT HEAUX - Premium Digital Fashion Store

A modern, high-end e-commerce website for digital fashion products built with PHP, MySQL, and Bootstrap.

## 🚀 Quick Start

1. **Setup Database**: Run `setup_tables.php` to initialize
2. **Admin Access**: Go to `/admin/login.php`
3. **Add Products**: Manage inventory via admin panel
4. **Go Live**: Site ready at `http://localhost/alt-heaux/`

## 📁 Project Structure

```
alt-heaux/
├── index.php              # Homepage with featured products
├── setup_tables.php       # Database initialization
├── database.sql           # Database schema
│
├── admin/                 # Admin dashboard
│   ├── dashboard.php      # Overview
│   ├── products.php       # Manage products
│   ├── categories.php     # Manage categories
│   ├── orders.php         # View orders
│   ├── sliders.php        # Hero carousel
│   └── settings.php       # Site configuration
│
├── auth/                  # Authentication
│   ├── login.php
│   ├── register.php
│   └── logout.php
│
├── pages/                 # Customer pages
│   ├── shop.php           # Product catalog with filters
│   ├── cart.php           # Shopping cart (no shipping)
│   ├── cart_api.php       # Cart AJAX operations
│   ├── dashboard.php      # User dashboard
│   ├── profile.php        # User profile
│   └── orders.php         # Order history
│
├── includes/              # Shared PHP functions
│   ├── config.php         # Database & site config
│   ├── header.php         # Navigation
│   ├── footer.php         # Footer
│   ├── cart_helper.php    # Cart operations
│   ├── products_helper.php
│   └── user_auth.php      # Auth functions
│
├── css/                   # Premium styling
│   └── style.css          # Gold/black theme
│
├── js/                    # Front-end interactions
│   └── main.js            # Cart & UI logic
│
├── docs/                  # Documentation
│   ├── DESIGN_UPGRADE_2025.md
│   ├── CART_IMPLEMENTATION.md
│   └── [other guides]
│
└── README.md              # This file
```

## ✨ Design & Features

### Premium Design
- **Color Scheme**: Pure black (#000000), crisp white, gold accents (#c9a961)
- **Typography**: Google Fonts (Poppins)
- **Animations**: Smooth transitions & hover effects
- **Responsive**: Mobile-first, works on all devices

### Shopping Features
- ✅ Product catalog with filtering
- ✅ Shopping cart (no shipping costs)
- ✅ Quantity management
- ✅ Real-time totals
- ✅ Persistent cart (database/session)

### User Features
- ✅ User registration & login
- ✅ User dashboard
- ✅ Order history
- ✅ Profile management

### Admin Features
- ✅ Product management
- ✅ Category management
- ✅ Order management
- ✅ Hero carousel/sliders
- ✅ Site settings
- ✅ Page content

## 💾 Database

**Core Tables:**
- `products` - Product catalog
- `categories` - Product categories
- `cart` - Shopping carts (quantities only)
- `orders` - Customer orders
- `users` - Customer accounts
- `admin_users` - Admin accounts

**Digital Store Setup:**
- Product quantities not used for stock (unlimited)
- No shipping costs
- Instant access/delivery

## ⚙️ Configuration

Database and configuration settings are stored securely in `includes/config.php` (excluded from Git).

**Security Best Practice**: Never hardcode or document database credentials. Configuration files should only exist on the server, not in version control.

For setup instructions, see `SECURITY_SETUP.md` and deployment guides.

## 📝 Database Setup

Initialize the database:
- Run `setup_tables.php` in your browser
- Or import `database.sql` directly in phpMyAdmin

## 🔐 Admin Access

Visit `/admin/login.php` after setup_tables.php initialization.

## 🛍️ Shopping Cart

**Features:**
- Add/remove items
- Adjust quantities
- Real-time subtotal calculation
- No shipping costs (digital products)
- Persistent storage

## 📱 Browser Support

- Chrome/Edge (latest)
- Firefox (latest)  
- Safari (latest)
- Mobile browsers (iOS/Android)

## 🎯 Getting Started

1. Run `setup_tables.php` to initialize database
2. Access admin at `/admin/login.php`
3. Add products via admin panel
4. Customize site settings
5. Launch your digital store!

## 📚 Documentation

Detailed guides available in `/docs/`:
- Design system & styling
- Cart implementation details
- Authentication & user management

---

**Version**: 1.0.0 (December 22, 2025)  
**License**: Proprietary - ALT HEAUX  
**Tech Stack**: PHP 7.4+, MySQL, Bootstrap 5, JavaScript
- Company values and commitment

### Contact (`pages/contact.php`)
- Contact form
- Business information
- Location and hours

### Cart (`pages/cart.php`)
- Shopping cart summary
- Order total calculation
- Checkout button

## Technologies Used

- **Frontend**: HTML5, CSS3, JavaScript, Bootstrap 5
- **Backend**: PHP 7.4+
- **Database**: MySQL
- **Icons**: Bootstrap Icons (via CDN)

## Customization

### Adding Products
Insert products directly into the database:
```php
INSERT INTO products (name, description, price, category, quantity) 
VALUES ('Product Name', 'Description', 29.99, 'Category', 50);
```

### Styling
- Main styles: `css/style.css`
- Bootstrap variables can be overridden in CSS
- Color scheme is customizable via CSS variables

### Adding Pages
1. Create new PHP file in `pages/` directory
2. Include header and footer components
3. Add navigation link in `includes/header.php`

## Future Enhancements

- [ ] User authentication and registration
- [ ] Checkout process integration
- [ ] Payment gateway integration (Stripe, PayPal)
- [ ] Order management system
- [ ] Product admin panel
- [ ] Email notifications
- [ ] Search functionality
- [ ] Product reviews and ratings
- [ ] Wishlist feature
- [ ] Inventory management

## File Descriptions

| File | Purpose |
|------|---------|
| `includes/config.php` | Database connection and site configuration |
| `includes/header.php` | Navigation and header components |
| `includes/footer.php` | Footer with links and company info |
| `css/style.css` | Custom styling and animations |
| `js/main.js` | Client-side functionality and cart management |
| `database.sql` | Database schema and sample data |

## Support

For issues or questions, contact: info@altheaux.com

## License

All rights reserved © 2025 ALT HEAUX
