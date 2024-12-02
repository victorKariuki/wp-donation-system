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
    /**
     * Supported currencies configuration
     *
     * @var array
     */
    private $currencies = array(
        'USD' => array(
            'name' => 'US Dollar',
            'symbol' => '$',
            'position' => 'left'
        ),
        'KES' => array(
            'name' => 'Kenyan Shilling',
            'symbol' => 'KSh',
            'position' => 'left'
        ),
        'EUR' => array(
            'name' => 'Euro',
            'symbol' => '€',
            'position' => 'left'
        ),
        'GBP' => array(
            'name' => 'British Pound',
            'symbol' => '£',
            'position' => 'left'
        )
    );

    /**
     * Format amount with currency symbol
     *
     * @param float $amount Amount to format
     * @param string $currency Currency code
     * @return string Formatted amount with currency symbol
     */
    public function format_amount($amount, $currency = 'USD') {
        $currency_data = $this->currencies[$currency] ?? $this->currencies['USD'];
        
        $formatted = number_format($amount, 2);
        
        return $currency_data['position'] === 'left' 
            ? $currency_data['symbol'] . $formatted 
            : $formatted . $currency_data['symbol'];
    }

    /**
     * Get all supported currencies
     *
     * @return array Array of supported currencies
     */
    public function get_currencies() {
        return apply_filters('wp_donation_supported_currencies', $this->currencies);
    }

    /**
     * Check if currency is supported
     *
     * @param string $currency Currency code to check
     * @return boolean True if supported, false otherwise
     */
    public function is_supported($currency) {
        return isset($this->currencies[$currency]);
    }

    /**
     * Get currency symbol
     *
     * @param string $currency Currency code
     * @return string Currency symbol
     */
    public function get_symbol($currency) {
        return $this->currencies[$currency]['symbol'] ?? '$';
    }
} 