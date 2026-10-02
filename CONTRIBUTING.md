# Contributing to ShopEasy

First off, thank you for considering contributing to ShopEasy! It's people like you that make this project better for everyone.

## Code of Conduct

By participating in this project, you are expected to uphold our Code of Conduct:

- Be respectful and inclusive
- Welcome newcomers and help them get started
- Focus on what is best for the community
- Show empathy towards other community members

## How Can I Contribute?

### Reporting Bugs

Before creating bug reports, please check existing issues to avoid duplicates. When you create a bug report, include as many details as possible:

- **Use a clear and descriptive title**
- **Describe the exact steps to reproduce the problem**
- **Provide specific examples**
- **Describe the behavior you observed and what you expected**
- **Include screenshots if relevant**
- **Include your environment details** (PHP version, MySQL version, OS, browser)

### Suggesting Enhancements

Enhancement suggestions are tracked as GitHub issues. When creating an enhancement suggestion:

- **Use a clear and descriptive title**
- **Provide a detailed description of the suggested enhancement**
- **Explain why this enhancement would be useful**
- **List some examples of how it would be used**

### Pull Requests

1. **Fork the repository** and create your branch from `main`
   ```bash
   git checkout -b feature/my-new-feature
   ```

2. **Make your changes**
   - Write clear, commented code
   - Follow the existing code style
   - Test your changes thoroughly

3. **Commit your changes**
   ```bash
   git commit -m "Add some feature"
   ```
   
   Use clear commit messages:
   - `feat:` for new features
   - `fix:` for bug fixes
   - `docs:` for documentation changes
   - `style:` for formatting changes
   - `refactor:` for code refactoring
   - `test:` for adding tests
   - `chore:` for maintenance tasks

4. **Push to your fork**
   ```bash
   git push origin feature/my-new-feature
   ```

5. **Open a Pull Request**
   - Provide a clear title and description
   - Reference any related issues
   - Include screenshots for UI changes

## Development Setup

1. Follow the installation instructions in README.md
2. Create a development database separate from production
3. Enable error reporting for development:
   ```php
   error_reporting(E_ALL);
   ini_set('display_errors', 1);
   ```

## Coding Standards

### PHP

- Follow PSR-12 coding standards
- Use meaningful variable and function names
- Comment complex logic
- Use prepared statements for all database queries
- Validate and sanitize all user inputs

Example:
```php
// Good
function getUserById($userId) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

// Bad
function getUser($id) {
    global $conn;
    $result = mysqli_query($conn, "SELECT * FROM users WHERE id = $id");
    return mysqli_fetch_assoc($result);
}
```

### JavaScript

- Use ES6+ features where appropriate
- Use camelCase for variables and functions
- Add comments for complex logic
- Handle errors appropriately

### CSS

- Use meaningful class names
- Group related properties
- Maintain responsive design principles
- Use CSS variables for theming

## Testing

- Test all new features thoroughly
- Test on multiple browsers (Chrome, Firefox, Safari, Edge)
- Test responsive design on various screen sizes
- Test with both light and dark modes
- Verify database operations don't cause issues

## Security

- Never commit sensitive data (passwords, API keys, etc.)
- Always use prepared statements for SQL queries
- Validate and sanitize all user inputs
- Use HTTPS in production
- Follow OWASP security guidelines

## Documentation

- Update README.md if you change functionality
- Comment your code where necessary
- Update API documentation if you add/modify endpoints
- Include JSDoc comments for JavaScript functions

## Questions?

Feel free to open an issue with the tag `question` if you need help or clarification on anything.

## Recognition

Contributors will be recognized in the project README and release notes.

Thank you for contributing! 🎉
