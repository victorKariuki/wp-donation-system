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

## Updates

### Checking for Updates

1. Log in to your WordPress dashboard
2. Navigate to Plugins > Installed Plugins
3. Look for "WP Donation System" in the list
4. If an update is available, you'll see an "Update Available" message
5. Click "View version x.x.x details" to see what's new
6. Click "Update Now" to install the latest version

### Automatic Updates

The plugin supports WordPress automatic updates. To enable:

1. Go to Dashboard > Settings > Auto Updates
2. Find "WP Donation System" in the plugins list
3. Toggle "Enable auto-updates"

Note: Always backup your website before updating any plugins.

## Usage

Add the donation form to any page or post using the shortcode:
