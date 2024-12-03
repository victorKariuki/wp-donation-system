# Changelog
All notable changes to WP Donation System will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]
### Added
- Enhanced M-Pesa integration with improved callback handling
- Detailed M-Pesa gateway logging system
- Callback URL validation and sanitization
- M-Pesa transaction status tracking
- M-Pesa credentials test functionality
- Real-time test transaction status checking
- Minimal test amount transaction support
- Debug tab with M-Pesa testing tools
- Real-time test transaction monitoring
- Improved test feedback system
- Comprehensive uninstall routine
- Safe data removal options
- Cleanup of all plugin artifacts
- Installation requirements checking
- Detailed activation error handling
- Uninstallation error tracking
- Fallback error logging
- Comprehensive callback data processing
- Payment confirmation notifications
- Detailed transaction metadata storage

### Removed
- PayPal gateway integration (temporary)
- PayPal IPN handling
- PayPal-related settings

### Fixed
- M-Pesa callback URL validation issue
- STK Push response handling
- Error handling in M-Pesa gateway
- Database update methods for donation status
- File permissions for M-Pesa gateway
- Removed duplicate M-Pesa class file
- Standardized file permissions across plugin
- Directory access permissions
- Asset file permissions
- Log file write permissions
- Created required directories with proper permissions
- Added .htaccess protection for logs directory
- Corrected gateway file paths
- Plugin cleanup on uninstall
- Installation and uninstallation error handling
- Database creation permissions check
- Improved database table creation process
- Enhanced activation error logging
- Database table verification after creation
- M-Pesa credentials validation
- Access token error handling
- Credential verification in settings
- Callback data validation
- Payment status updates
- Notification delivery

### Changed
- Improved M-Pesa gateway initialization
- Enhanced error logging for payment processing
- Updated callback endpoint structure
- Refined M-Pesa API response handling
- Moved M-Pesa class to gateways directory
- Organized directory structure
- Enhanced credential validation process
- Improved callback response handling
- Enhanced notification system

### Security
- Input sanitization and validation
- CSRF protection
- Rate limiting for payment attempts
- Secure payment processing
- Hardened file permissions
- Protected sensitive configuration files
- Secured logs directory access
- Safe data removal during uninstall