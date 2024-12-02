<?php
/**
 * Form Validator Class
 *
 * @package WP_Donation_System
 */

if (!defined('ABSPATH')) {
    exit;
}

class WP_Donation_System_Form_Validator {
    /**
     * Validate donation form data
     *
     * @param array $data Form data to validate
     * @return true|array True if valid, array of errors if invalid
     */
    public function validate($data) {
        $errors = array();
        
        // Required fields
        if (empty($data['amount']) || !is_numeric($data['amount'])) {
            $errors[] = __('Please enter a valid donation amount', 'wp-donation-system');
        }
        
        if (empty($data['donor_email']) || !is_email($data['donor_email'])) {
            $errors[] = __('Please enter a valid email address', 'wp-donation-system');
        }
        
        // M-Pesa specific validation
        if ($data['payment_method'] === 'mpesa' && empty($data['phone'])) {
            $errors[] = __('Phone number is required for M-Pesa payments', 'wp-donation-system');
        }
        
        // Additional validations
        if (!empty($data['amount']) && is_numeric($data['amount'])) {
            // Check minimum amount
            if ($data['amount'] < 1) {
                $errors[] = __('Donation amount must be at least 1', 'wp-donation-system');
            }
            // Check maximum amount
            if ($data['amount'] > 999999) {
                $errors[] = __('Donation amount exceeds maximum limit', 'wp-donation-system');
            }
        }
        
        if (!empty($data['donor_name']) && strlen($data['donor_name']) > 100) {
            $errors[] = __('Donor name is too long', 'wp-donation-system');
        }
        
        // Payment method validation
        if (empty($data['payment_method'])) {
            $errors[] = __('Please select a payment method', 'wp-donation-system');
        } elseif (!in_array($data['payment_method'], array('paypal', 'mpesa'))) {
            $errors[] = __('Invalid payment method selected', 'wp-donation-system');
        }
        
        /**
         * Filter the validation errors
         *
         * @param array $errors Array of validation errors
         * @param array $data Form data being validated
         */
        $errors = apply_filters('wp_donation_validation_errors', $errors, $data);
        
        return empty($errors) ? true : $errors;
    }

    /**
     * Sanitize form data
     *
     * @param array $data Raw form data
     * @return array Sanitized form data
     */
    public function sanitize($data) {
        $clean = array();
        
        if (!empty($data['donor_name'])) {
            $clean['donor_name'] = sanitize_text_field($data['donor_name']);
        }
        
        if (!empty($data['donor_email'])) {
            $clean['donor_email'] = sanitize_email($data['donor_email']);
        }
        
        if (!empty($data['amount'])) {
            $clean['amount'] = floatval($data['amount']);
        }
        
        if (!empty($data['payment_method'])) {
            $clean['payment_method'] = sanitize_text_field($data['payment_method']);
        }
        
        if (!empty($data['phone'])) {
            $clean['phone'] = sanitize_text_field($data['phone']);
        }
        
        /**
         * Filter the sanitized form data
         *
         * @param array $clean Sanitized form data
         * @param array $data Raw form data
         */
        return apply_filters('wp_donation_sanitize_form_data', $clean, $data);
    }
} 