# ShopEasy - E-Commerce Platform

A modern e-commerce web application built with PHP, featuring product management, user authentication, shopping cart functionality, and eSewa payment integration.

## Features

- **User Authentication**: Secure login and registration system
- **Product Management**: Browse products by categories with detailed product pages
- **Shopping Cart**: Add products to cart with quantity management
- **Order Management**: Track and view order history
- **Payment Integration**: eSewa payment gateway integration for Nepali users
- **Admin Dashboard**: Manage products, orders, and users
- **Responsive Design**: Mobile-friendly interface with dark mode support
- **Product Variations**: Support for product attributes (size, color, etc.)

## Tech Stack

- **Frontend**: HTML5, CSS3, JavaScript
- **Backend**: PHP
- **Database**: MySQL
- **Payment**: eSewa Payment Gateway
- **Icons**: Font Awesome 6.4.0

## Prerequisites

Before you begin, ensure you have the following installed:

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache/Nginx web server
- Composer (optional, for dependency management)

## Installation

1. **Clone the repository**
   ```bash
   git clone https://github.com/yourusername/shopeasy.git
   cd shopeasy
   ```

2. **Set up the database**
   - Create a new MySQL database
   - Import the database schema:
     ```bash
     mysql -u your_username -p your_database < setup_db.sql
     ```
   - Run migrations if needed:
     ```bash
     mysql -u your_username -p your_database < database_migration.sql
     ```

3. **Configure database connection**
   - Create a `config.php` file in the root directory (copy from `config.example.php` if provided)
   - Update database credentials:
     ```php
     <?php
     define('DB_HOST', 'localhost');
     define('DB_USER', 'your_username');
     define('DB_PASS', 'your_password');
     define('DB_NAME', 'your_database');
     ?>
     ```

4. **Configure eSewa Payment**
   - Update eSewa merchant credentials in your configuration
   - Set up callback URLs for success/failure pages

5. **Set up web server**
   - Point your web server document root to the project directory
   - Ensure proper permissions for file uploads and logs
   - Enable mod_rewrite if using Apache

6. **Populate sample data** (optional)
   ```bash
   php populate_db.php
   ```

## Usage

### For Users

1. Visit the homepage at `http://localhost/spm` (or your configured domain)
2. Browse products and add items to your cart
3. Register for an account or login
4. Proceed to checkout and complete payment via eSewa
5. Track your orders in "My Orders" section

### For Administrators

1. Access admin panel at `/admin_login.php`
2. Login with admin credentials
3. Manage products, categories, and orders from the dashboard

## Project Structure

```
spm/
├── admin_dashboard.php      # Admin panel
├── admin_login.php          # Admin authentication
├── checkout.php             # Checkout process
├── esewa_callback.php       # eSewa payment callback handler
├── esewa_success.php        # Payment success page
├── esewa_failure.php        # Payment failure page
├── get_products.php         # API endpoint for products
├── index.html               # Main homepage
├── login.php                # User login
├── register.php             # User registration
├── my_orders.php            # Order history
├── product_details.php      # Product detail page
├── settings.php             # User settings
├── setup_db.sql             # Database schema
├── database_migration.sql   # Database migrations
├── populate_db.php          # Sample data seeder
├── styles.css               # Main stylesheet
├── admin_styles.css         # Admin panel styles
├── script.js                # Frontend JavaScript
└── spmkofinal/              # Additional resources
```

## Configuration

### Database Configuration

Update your database connection settings in the main configuration file.

### eSewa Configuration

Configure eSewa payment gateway:
- Merchant ID
- Success URL
- Failure URL
- Service URL

### Session Management

Ensure PHP sessions are properly configured in `php.ini`:
```ini
session.gc_maxlifetime = 1440
session.cookie_lifetime = 0
```

## Security Considerations

- All passwords are hashed using secure algorithms
- SQL injection prevention using prepared statements
- XSS protection on user inputs
- CSRF token validation on forms
- Secure session management

## Development

### Running in Development Mode

```bash
# Using PHP built-in server
php -S localhost:8000
```

### Database Migrations

To apply database changes:
```bash
mysql -u username -p database_name < database_migration.sql
```

## Troubleshooting

### Common Issues

1. **Database Connection Failed**
   - Verify database credentials in config
   - Ensure MySQL service is running

2. **Payment Integration Issues**
   - Check eSewa merchant credentials
   - Verify callback URLs are accessible

3. **Session Issues**
   - Check PHP session configuration
   - Ensure proper file permissions for session storage

## Contributing

Contributions are welcome! Please follow these steps:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## Support

For support and questions:
- Open an issue on GitHub
- Email: support@shopeasy.example.com

## Acknowledgments

- Font Awesome for icons
- eSewa for payment gateway services
- All contributors who have helped improve this project

## Roadmap

- [ ] Add more payment gateway options
- [ ] Implement product reviews and ratings
- [ ] Add wishlist functionality
- [ ] Email notifications for orders
- [ ] Advanced search and filtering
- [ ] Product recommendations
- [ ] Multi-language support

---

**Note**: This is an educational project. Ensure proper security audits before deploying to production.
