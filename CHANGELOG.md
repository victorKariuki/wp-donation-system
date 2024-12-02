# Changelog
All notable changes to WP Donation System will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.1] - 2024-12-02
### Added
- GitHub-based update system
- Proper plugin author and URI information
- Automatic update functionality via WordPress dashboard
- Update notification system

### Fixed
- Added missing include for form validator class
- Fixed plugin header information
- Improved update mechanism reliability

### Changed
- Switched from custom update server to GitHub releases
- Updated plugin metadata to reflect GitHub repository
- Removed deprecated update-info.json in favor of GitHub API

## [1.0.0] - 2024-12-02
### Added
- Initial release
- PayPal payment gateway integration
- M-Pesa payment gateway integration
- Admin dashboard for donation management
- Donation reporting system
- CSV export functionality
- Email notifications system
- Test mode for payment gateways
- Logging system for debugging
- Mobile-responsive donation form
- Shortcode support: [donation_form]

### Security
- Input sanitization and validation
- CSRF protection
- Rate limiting for payment attempts
- Secure payment processing 

## [1.0.2] - 2024-12-02
### Added
- Enhanced form validation with additional checks
- Data sanitization method in form validator
- Filter hooks for validation and sanitization
- Improved error messages
- Currency handling class with formatting options
- Error handling class with logging integration
- Support for additional currencies (EUR, GBP)
- Currency position formatting
- Error message standardization

### Fixed
- Class loading order to prevent initialization errors
- Form validator file structure and documentation
- Plugin file organization
- Error handling consistency

### Changed
- Reorganized class loading into logical groups
- Updated validation error messages
- Improved code documentation
- Standardized error handling across plugin