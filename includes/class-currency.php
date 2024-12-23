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

    private $default_currency = 'USD';

    public function __construct() {
        $this->default_currency = get_option('wp_donation_system_default_currency', 'USD');
    }

    /**
     * Format amount according to currency settings
     *
     * @param float $amount Amount to format
     * @param string $currency Currency code (optional)
     * @return string Formatted amount
     */
    public function format($amount, $currency = '') {
        if (empty($currency)) {
            $currency = $this->default_currency;
        }

        $currency_data = $this->get_currency($currency);
        if (!$currency_data) {
            return $amount;
        }

        // Format the number
        $amount = number_format(
            $amount,
            $currency_data['decimals'],
            $currency_data['decimal_sep'],
            $currency_data['thousands_sep']
        );

        // Add currency symbol
        if ($currency_data['position'] === 'left') {
            return $currency_data['symbol'] . $amount;
        } else {
            return $amount . $currency_data['symbol'];
        }
    }

    /**
     * Get currency data
     *
     * @param string $currency Currency code
     * @return array|false Currency data or false if not found
     */
    public function get_currency($currency) {
        return $this->currencies[$currency] ?? false;
    }

    /**
     * Get all available currencies
     *
     * @return array Array of currencies
     */
    public function get_currencies() {
        return $this->currencies;
    }

    /**
     * Get default currency
     *
     * @return string Default currency code
     */
    public function get_default_currency() {
        return $this->default_currency;
    }

    /**
     * Get currency symbol
     *
     * @param string $currency Currency code (optional)
     * @return string Currency symbol
     */
    public function get_symbol($currency = '') {
        if (empty($currency)) {
            $currency = $this->default_currency;
        }

        $currency_data = $this->get_currency($currency);
        return $currency_data ? $currency_data['symbol'] : '';
    }

    /**
     * Format an amount according to the currency settings
     *
     * @param float $amount The amount to format
     * @return string Formatted amount with currency symbol
     */
    public function format_amount($amount) {
        $currency_symbol = $this->get_currency_symbol();
        $decimal_places = $this->get_decimal_places();
        
        // Format the number with proper decimal places
        $formatted_amount = number_format($amount, $decimal_places, '.', ',');
        
        // Return formatted amount with currency symbol
        return $currency_symbol . $formatted_amount;
    }

    /**
     * Get currency symbol
     *
     * @return string Currency symbol
     */
    private function get_currency_symbol() {
        // Default to USD symbol if not set
        return get_option('wpds_currency_symbol', '$');
    }

    /**
     * Get number of decimal places for currency
     *
     * @return int Number of decimal places
     */
    private function get_decimal_places() {
        return (int) get_option('wpds_decimal_places', 2);
    }
} 