class WP_Donation_System_Currency {
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
        )
    );
    
    public function format_amount($amount, $currency = 'USD') {
        $currency_data = $this->currencies[$currency] ?? $this->currencies['USD'];
        return $currency_data['position'] === 'left' 
            ? $currency_data['symbol'] . number_format($amount, 2)
            : number_format($amount, 2) . $currency_data['symbol'];
    }
} 