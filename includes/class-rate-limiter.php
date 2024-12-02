<?php
class WP_Donation_System_Rate_Limiter {
    private $transient_prefix = 'wp_donation_rate_limit_';
    
    public function check_limit($key, $max_attempts = 5, $time_window = 300) {
        $transient_key = $this->transient_prefix . md5($key);
        $attempts = get_transient($transient_key);
        
        if ($attempts === false) {
            set_transient($transient_key, 1, $time_window);
            return true;
        }
        
        if ($attempts >= $max_attempts) {
            return false;
        }
        
        set_transient($transient_key, $attempts + 1, $time_window);
        return true;
    }
} 