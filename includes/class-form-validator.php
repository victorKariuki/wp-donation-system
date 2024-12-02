class WP_Donation_System_Form_Validator {
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
        
        return empty($errors) ? true : $errors;
    }
} 