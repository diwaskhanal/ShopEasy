# Security Policy

## Supported Versions

We release patches for security vulnerabilities. Currently supported versions:

| Version | Supported          |
| ------- | ------------------ |
| 1.0.x   | :white_check_mark: |

## Reporting a Vulnerability

We take the security of ShopEasy seriously. If you discover a security vulnerability, please follow these steps:

### How to Report

1. **DO NOT** open a public issue
2. Email details to: security@shopeasy.example.com (replace with your actual contact)
3. Include:
   - Description of the vulnerability
   - Steps to reproduce
   - Potential impact
   - Suggested fix (if any)

### What to Expect

- **Acknowledgment**: Within 48 hours
- **Initial Assessment**: Within 5 business days
- **Status Updates**: Weekly until resolved
- **Resolution**: Target within 30 days for critical issues

### Disclosure Policy

- We will coordinate with you on disclosure timing
- We aim to fix issues before public disclosure
- We will credit you in release notes (if desired)

## Security Best Practices

### For Administrators

1. **Database Security**
   - Use strong, unique passwords
   - Restrict database access to localhost when possible
   - Regularly backup your database
   - Keep MySQL/MariaDB updated

2. **File Permissions**
   ```bash
   # Recommended permissions
   chmod 755 /path/to/spm
   chmod 644 /path/to/spm/*.php
   chmod 600 /path/to/spm/config.php
   chmod 700 /path/to/spm/uploads
   ```

3. **Web Server Configuration**
   - Use HTTPS in production (SSL/TLS certificate)
   - Disable directory listing
   - Configure proper error pages
   - Set security headers:
     ```apache
     # Apache .htaccess
     Header always set X-Frame-Options "SAMEORIGIN"
     Header always set X-Content-Type-Options "nosniff"
     Header always set X-XSS-Protection "1; mode=block"
     Header always set Referrer-Policy "strict-origin-when-cross-origin"
     ```

4. **PHP Configuration**
   ```ini
   # php.ini security settings
   expose_php = Off
   display_errors = Off
   log_errors = On
   error_log = /path/to/logs/php_errors.log
   session.cookie_httponly = 1
   session.cookie_secure = 1
   session.use_only_cookies = 1
   ```

5. **Regular Updates**
   - Keep PHP updated
   - Update dependencies regularly
   - Monitor security advisories

### For Developers

1. **Input Validation**
   - Validate all user inputs server-side
   - Use prepared statements for database queries
   - Sanitize output to prevent XSS

2. **Authentication**
   - Use strong password hashing (bcrypt, Argon2)
   - Implement rate limiting on login attempts
   - Use secure session management

3. **Authorization**
   - Verify user permissions for every action
   - Use CSRF tokens for state-changing operations
   - Implement principle of least privilege

4. **Data Protection**
   - Never store plain-text passwords
   - Encrypt sensitive data at rest
   - Use HTTPS for data in transit
   - Implement proper access controls

5. **Code Review**
   - Review code for security issues before merging
   - Use static analysis tools
   - Test for common vulnerabilities (OWASP Top 10)

## Known Security Considerations

### Payment Integration

- eSewa credentials must be kept secure
- Validate all payment callbacks server-side
- Never trust client-side payment confirmations
- Log all payment transactions for audit

### Session Management

- Sessions expire after inactivity
- Regenerate session IDs after login
- Secure session cookies (httpOnly, secure flags)

### File Uploads

- Validate file types server-side
- Limit file sizes
- Store uploads outside web root when possible
- Scan uploads for malware

### SQL Injection Prevention

All database queries use prepared statements:
```php
// Good - Using prepared statements
$stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();

// Bad - String concatenation
$query = "SELECT * FROM users WHERE email = '$email'"; // NEVER DO THIS
```

### XSS Prevention

Always escape output:
```php
// Escape output
echo htmlspecialchars($userInput, ENT_QUOTES, 'UTF-8');

// For HTML attributes
echo htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
```

## Security Checklist for Production

- [ ] HTTPS enabled with valid SSL certificate
- [ ] Database credentials secured (strong password, restricted access)
- [ ] `config.php` not in version control
- [ ] Error display disabled in production
- [ ] Error logging enabled and monitored
- [ ] File upload directory secured
- [ ] Admin panel IP restricted (if applicable)
- [ ] Regular backups configured
- [ ] Security headers configured
- [ ] PHP version up to date
- [ ] Dependencies up to date
- [ ] Rate limiting implemented on critical endpoints
- [ ] CSRF protection enabled
- [ ] Session security configured
- [ ] Input validation implemented
- [ ] Output escaping applied

## Resources

- [OWASP Top 10](https://owasp.org/www-project-top-ten/)
- [PHP Security Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/PHP_Configuration_Cheat_Sheet.html)
- [MySQL Security Best Practices](https://dev.mysql.com/doc/refman/8.0/en/security-guidelines.html)

## Contact

For security concerns: security@shopeasy.example.com

---

**Note**: This is a living document. Security practices should be reviewed and updated regularly.
