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