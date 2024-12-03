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

### Fixed
- M-Pesa callback URL validation issue
- STK Push response handling
- Error handling in M-Pesa gateway
- Database update methods for donation status

### Changed
- Improved M-Pesa gateway initialization
- Enhanced error logging for payment processing
- Updated callback endpoint structure
- Refined M-Pesa API response handling

### Security
- Input sanitization and validation
- CSRF protection
- Rate limiting for payment attempts
- Secure payment processing 

## [1.0.2] - 2024-12-02
### Added
- Complete admin interface implementation
- Donations list table with search and filtering
- Settings page with gateway configurations
- Reports page structure
- Admin assets (CSS and JavaScript)
- Comprehensive M-Pesa STK Push settings
- Environment selection for sandbox/live
- Integration type selection (Paybill/Till)
- Callback URL configurations
- Transaction reference settings
- Comprehensive donations database table
- Donation data persistence layer
- Status tracking for donations
- M-Pesa transaction tracking fields
- Metadata support for donations
- Donation retrieval methods

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