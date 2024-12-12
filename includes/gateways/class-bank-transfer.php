<?php
class WP_Donation_System_Gateway_Bank_Transfer extends WP_Donation_System_Gateway {
    public function __construct() {
        $this->id = 'bank_transfer';
        $this->title = __('Bank Transfer', 'wp-donation-system');
        $this->description = __('Make a donation via direct bank transfer.', 'wp-donation-system');
        
        parent::__construct();
    }

    public function get_settings_fields() {
        return [
            'enabled' => [
                'title' => __('Enable/Disable', 'wp-donation-system'),
                'type' => 'checkbox',
                'label' => __('Enable Bank Transfer payments', 'wp-donation-system'),
                'default' => 'no'
            ],
            'bank_name' => [
                'title' => __('Bank Name', 'wp-donation-system'),
                'type' => 'text',
                'description' => __('Your bank name', 'wp-donation-system'),
                'required' => true
            ],
            'account_name' => [
                'title' => __('Account Name', 'wp-donation-system'),
                'type' => 'text',
                'description' => __('Name on the bank account', 'wp-donation-system'),
                'required' => true
            ],
            'account_number' => [
                'title' => __('Account Number', 'wp-donation-system'),
                'type' => 'text',
                'description' => __('Your bank account number', 'wp-donation-system'),
                'required' => true
            ],
            'branch_code' => [
                'title' => __('Branch Code', 'wp-donation-system'),
                'type' => 'text',
                'description' => __('Bank branch code or routing number', 'wp-donation-system'),
                'required' => true
            ],
            'instructions' => [
                'title' => __('Transfer Instructions', 'wp-donation-system'),
                'type' => 'textarea',
                'description' => __('Additional instructions for donors', 'wp-donation-system'),
                'default' => __('Please include your donation reference number in the transfer description.', 'wp-donation-system')
            ]
        ];
    }

    public function process_payment($donation_data) {
        try {
            // Create donation with pending status
            $donation_id = $this->create_donation($donation_data);
            
            // Generate reference number
            $reference = 'DON-' . $donation_id;
            
            // Get bank details
            $bank_details = $this->get_bank_details();
            
            // Return success with bank details
            return [
                'result' => 'success',
                'redirect' => false,
                'donation_id' => $donation_id,
                'reference' => $reference,
                'bank_details' => $bank_details,
                'message' => $this->get_thank_you_message($reference)
            ];
        } catch (Exception $e) {
            $this->logger->log('Bank transfer processing failed: ' . $e->getMessage(), 'error', $donation_data);
            throw $e;
        }
    }

    private function get_bank_details() {
        return [
            'bank_name' => $this->get_setting('bank_name'),
            'account_name' => $this->get_setting('account_name'),
            'account_number' => $this->get_setting('account_number'),
            'branch_code' => $this->get_setting('branch_code'),
            'instructions' => $this->get_setting('instructions')
        ];
    }

    private function get_thank_you_message($reference) {
        return sprintf(
            __('Thank you for your donation! Please make your bank transfer using these details:

Bank: %s
Account Name: %s
Account Number: %s
Branch Code: %s
Reference: %s

%s

We will notify you once we confirm your payment.', 'wp-donation-system'),
            $this->get_setting('bank_name'),
            $this->get_setting('account_name'),
            $this->get_setting('account_number'),
            $this->get_setting('branch_code'),
            $reference,
            $this->get_setting('instructions')
        );
    }

    public function get_payment_fields() {
        // Bank transfer doesn't need additional fields
        return [];
    }

    public function validate_fields($data) {
        // Bank transfer doesn't need field validation
        return true;
    }
} 