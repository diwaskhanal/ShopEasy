# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Planned
- Multi-language support (Nepali/English)
- Email notifications for orders
- Product reviews and ratings
- Wishlist functionality
- Advanced search filters
- Product recommendations

## [1.0.0] - 2026-10-02

### Added
- Initial release of ShopEasy e-commerce platform
- User authentication system (registration and login)
- Product catalog with categories
- Shopping cart functionality
- Product variations (size, color, etc.)
- eSewa payment gateway integration
- Order management system
- Admin dashboard for product and order management
- Responsive design with mobile support
- Dark mode theme toggle
- User settings page
- Order history tracking
- Product search functionality
- Secure session management
- CSRF protection
- SQL injection prevention with prepared statements

### Security
- Password hashing using bcrypt
- XSS protection on user inputs
- Secure payment callback handling
- Session timeout implementation

### Database
- Users table with secure password storage
- Products and categories tables
- Product variations support
- Orders and order items tracking
- Optimized indexes for better performance

### UI/UX
- Modern, clean interface
- Font Awesome icons integration
- Responsive grid layout
- Interactive cart modal
- Product detail modal
- Form validation
- Loading states and animations

## [0.1.0] - Development Phase

### Added
- Basic project structure
- Database schema design
- Core PHP functionality
- Frontend assets (HTML, CSS, JS)

---

## Version History Format

### Types of changes
- `Added` for new features
- `Changed` for changes in existing functionality
- `Deprecated` for soon-to-be removed features
- `Removed` for now removed features
- `Fixed` for any bug fixes
- `Security` for vulnerability fixes

[Unreleased]: https://github.com/yourusername/shopeasy/compare/v1.0.0...HEAD
[1.0.0]: https://github.com/yourusername/shopeasy/releases/tag/v1.0.0
