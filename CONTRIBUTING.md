# Contributing to WP Donation System

Thank you for considering contributing to WP Donation System! This document provides guidelines and steps for contributing to the project.

## Code of Conduct

By participating in this project, you agree to abide by our Code of Conduct. Please read [CODE_OF_CONDUCT.md](CODE_OF_CONDUCT.md) before contributing.

## How Can I Contribute?

### Reporting Bugs

Before creating bug reports, please check the existing issues to avoid duplicates. When creating a bug report, include as many details as possible:

1. **Use a clear and descriptive title**
2. **Describe the exact steps to reproduce the problem**
3. **Provide specific examples**
4. Include:
   - WordPress version
   - PHP version
   - Plugin version
   - Any relevant error messages
   - Screenshots if applicable

### Suggesting Enhancements

Enhancement suggestions are tracked as GitHub issues. When creating an enhancement suggestion:

1. **Use a clear and descriptive title**
2. **Provide a detailed description of the suggested enhancement**
3. **Explain why this enhancement would be useful**
4. **List any relevant examples**

### Pull Requests

1. Fork the repository
2. Create a new branch (`git checkout -b feature/amazing-feature`)
3. Make your changes
4. Run tests if available
5. Commit your changes (`git commit -m 'Add some amazing feature'`)
6. Push to the branch (`git push origin feature/amazing-feature`)
7. Open a Pull Request

#### Pull Request Guidelines

- Follow the WordPress [Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/)
- Update the README.md with details of changes if applicable
- Update the CHANGELOG.md following [Keep a Changelog](https://keepachangelog.com/en/1.0.0/) format
- The PR should work for PHP 7.4 and above
- Include relevant issue numbers in your PR description

### Development Setup

1. Clone the repository 
2. Set up a local WordPress development environment
3. Symlink or copy the plugin to your WordPress plugins directory
4. Activate the plugin in WordPress admin

### Coding Standards

- Follow WordPress [Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/)
- Use meaningful variable and function names
- Comment your code when necessary
- Keep functions focused and modular
- Use WordPress native functions when available

### Version Control Guidelines

- Keep commits atomic and focused
- Write clear, concise commit messages
- Reference issues and pull requests in commit messages
- Keep your branch up to date with main

### Documentation

- Update documentation for any changed functionality
- Add PHPDoc blocks for new functions
- Include inline comments for complex logic
- Update README.md if adding new features

### Testing

Before submitting a PR:

1. Test in a clean WordPress installation
2. Test with both PayPal and M-Pesa gateways
3. Test in both live and sandbox modes
4. Verify all existing functionality still works
5. Test with different WordPress versions

## Financial Contributions

We accept donations through:
- PayPal
- M-Pesa

## Questions?

Feel free to open an issue with your question or contact the maintainers directly.

## License

By contributing to WP Donation System, you agree that your contributions will be licensed under its GPL v2 or later license.