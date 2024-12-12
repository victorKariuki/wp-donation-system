# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.0.0] - 2024-03-20

### Breaking Changes
- Complete redesign of donation form interface
- Restructured form steps and validation logic
- Updated payment gateway integration flow

### Added
- New progress indicator with visual feedback
- Preset amount selection buttons
- Custom amount input with validation
- Real-time form validation
- Loading states and animations
- Success message handling
- Mobile-first responsive design
- Accessibility improvements
- Form validation animations
- Enhanced error messaging system

### Changed
- Improved visual hierarchy of form elements
- Enhanced payment method selection UI
- Updated button states and feedback
- Reorganized form sections for better flow
- Modernized CSS architecture
- Enhanced mobile experience

### Fixed
- Form validation issues
- Mobile responsiveness bugs
- Payment method selection glitches
- Step transition animations
- Error message display
- Fixed M-Pesa payment initiation error
- Added better error logging for payment processing
- Improved validation of phone numbers for M-Pesa payments
- Enhanced error handling in AJAX processing
- Fixed missing gateway class error
- Fixed currency formatting method inconsistency in donations list table
- Fixed database table name inconsistency in queries
- Updated all database queries to use correct table prefix
- Added forced table creation on activation
- Added admin notice and manual table creation option
- Improved database table creation error handling
- Fixed currency formatting in donations list display
- Updated all currency formatting calls to use correct method
- Fixed pending donations count display
- Enhanced donation statistics visualization
- Added improved styling for donation statistics
- Fixed integer type casting for donation counts
- Added debug information display for administrators
- Improved data verification and display accuracy
- Enhanced error reporting for database queries
- Added number formatting for better readability

## [1.0.0] - 2024-03-01

### Added
- Initial release of donation form
- Basic payment processing
- Simple form validation
- Basic responsive design

[2.0.0]: https://github.com/username/repo/compare/v1.0.0...v2.0.0
[1.0.0]: https://github.com/username/repo/releases/tag/v1.0.0