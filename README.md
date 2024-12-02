# WP Donation System

A WordPress plugin for handling donations via PayPal and M-Pesa payment gateways.

## Description

WP Donation System provides a complete donation management solution for WordPress websites. It supports both PayPal and M-Pesa payment gateways, making it ideal for organizations that need to accept donations globally and within Kenya.

### Features

- Easy-to-use donation form
- Multiple payment gateways:
  - PayPal
  - M-Pesa
- Admin dashboard with:
  - Donation management
  - Detailed reports
  - Export functionality
- Email notifications for:
  - Donors (receipt)
  - Administrators
- Test mode for safe testing
- Mobile-responsive design
- Detailed logging system

## Installation

1. Upload the `wp-donation-system` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to Donations > Settings to configure your payment gateways
4. Add the donation form to any page using the shortcode: `[donation_form]`

## Configuration

### PayPal Setup

1. Create a PayPal Developer account at https://developer.paypal.com
2. Create a new app to get your API credentials
3. Enter your Client ID and Secret in the plugin settings
4. Test using Sandbox mode before going live

### M-Pesa Setup

1. Create a Safaricom Developer account at https://developer.safaricom.co.ke
2. Create a new app to get your API credentials
3. Enter your Consumer Key, Secret, and Shortcode in the plugin settings
4. Test using Sandbox mode before going live

## Usage

### Basic Usage

Add the donation form to any page or post using the shortcode:
