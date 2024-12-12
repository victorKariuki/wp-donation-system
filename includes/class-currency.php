<?php
/**
 * Currency Handler Class
 *
 * @package WP_Donation_System
 */

if (!defined('ABSPATH')) {
    exit;
}

class WP_Donation_System_Currency {
    private $default_currency;
    private $logger;
    private $settings_manager;
    private $currencies = [
        'USD' => [
            'name' => 'US Dollar',
            'symbol' => '$',
            'position' => 'left',
            'decimals' => 2,
            'thousands_sep' => ',',
            'decimal_sep' => '.'
        ],
        'EUR' => [
            'name' => 'Euro',
            'symbol' => '€',
            'position' => 'left',
            'decimals' => 2,
            'thousands_sep' => ',',
            'decimal_sep' => '.'
        ],
        'GBP' => [
            'name' => 'British Pound',
            'symbol' => '£',
            'position' => 'left',
            'decimals' => 2,
            'thousands_sep' => ',',
            'decimal_sep' => '.'
        ],
        'KES' => [
            'name' => 'Kenyan Shilling',
            'symbol' => 'KSh',
            'position' => 'left',
            'decimals' => 2,
            'thousands_sep' => ',',
            'decimal_sep' => '.'
        ]
    ];
    
    public function __construct() {
        require_once WP_DONATION_SYSTEM_PATH . 'includes/class-logger.php';
        require_once WP_DONATION_SYSTEM_PATH . 'includes/class-settings-manager.php';
        $this->logger = new WP_Donation_System_Logger();
        $this->settings_manager = WP_Donation_System_Settings_Manager::get_instance();
        $this->load_settings();
    }
    
    private function load_settings() {
        $general_settings = $this->settings_manager->get_settings('general');
        $this->default_currency = isset($general_settings['default_currency']) ? 
            $general_settings['default_currency'] : 'USD';
        
        $this->logger->log('Currency settings loaded', 'info', [
            'default_currency' => $this->default_currency
        ]);
    }
    
    public function format($amount, $currency = null) {
        // Always reload settings to ensure we have the latest
        $this->load_settings();
        
        // Use provided currency or fall back to default
        $currency = $currency ?: $this->default_currency;
        
        // Get currency data
        $currency_data = $this->get_currency($currency);
        
        // Format the number
        $formatted = number_format(
            $amount,
            $currency_data['decimals'],
            $currency_data['decimal_sep'],
            $currency_data['thousands_sep']
        );
        
        // Add currency symbol
        if ($currency_data['position'] === 'left') {
            $formatted = $currency_data['symbol'] . $formatted;
        } else {
            $formatted = $formatted . $currency_data['symbol'];
        }
        
        return $formatted;
    }
    
    public function get_default_currency() {
        // Always reload settings to ensure we have the latest
        $this->load_settings();
        return $this->default_currency;
    }

    /**
     * Get currency data
     *
     * @param string $currency Currency code
     * @return array Currency data
     */
    public function get_currency($currency) {
        return $this->currencies[$currency] ?? $this->currencies['USD'];
    }

    /**
     * Get all available currencies
     *
     * @return array Array of currencies
     */
    public function get_currencies() {
        return $this->currencies;
    }
}