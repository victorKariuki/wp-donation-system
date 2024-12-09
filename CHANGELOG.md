# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.0.2] - 2024-03-XX

### Fixed
- Fixed database error where donations table was missing
- Added proper database table creation during plugin activation
- Ensured table names are correctly prefixed in queries

## [2.0.1] - 2024-03-XX

### Fixed
- Fixed fatal error related to undefined method `format_amount()` in Currency class
- Updated donation list table to properly use Currency class formatting methods
- Improved currency formatting consistency across the plugin

## [2.0.0] - Previous version

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

## [1.0.0] - 2024-03-01

### Added
- Initial release of donation form
- Basic payment processing
- Simple form validation
- Basic responsive design

[2.0.0]: https://github.com/username/repo/compare/v1.0.0...v2.0.0
[1.0.0]: https://github.com/username/repo/releases/tag/v1.0.0