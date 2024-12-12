<?php
class WP_Donation_System_MPesa_Transaction extends WP_Donation_System_Model {
    protected static $table_name = 'donation_system_mpesa_transactions';
    protected static $fillable = [
        'donation_id',
        'checkout_request_id',
        'merchant_request_id',
        'transaction_id',
        'phone_number',
        'amount',
        'request_type',
        'request_status',
        'raw_request',
        'raw_response',
        'result_code',
        'result_desc'
    ];

    // Relationships
    public function donation() {
        return WP_Donation_System_Donation::find($this->donation_id);
    }

    // Scopes
    public static function pending() {
        return static::query()->where('request_status', 'pending');
    }

    public static function completed() {
        return static::query()->where('request_status', 'completed');
    }

    public static function failed() {
        return static::query()->where('request_status', 'failed');
    }

    // Mutators
    protected function setRawRequestAttribute($value) {
        $this->attributes['raw_request'] = is_array($value) ? json_encode($value) : $value;
    }

    protected function setRawResponseAttribute($value) {
        $this->attributes['raw_response'] = is_array($value) ? json_encode($value) : $value;
    }

    // Accessors
    protected function getRawRequestAttribute($value) {
        return $value ? json_decode($value, true) : [];
    }

    protected function getRawResponseAttribute($value) {
        return $value ? json_decode($value, true) : [];
    }

    // Business Logic
    public function markAsCompleted($response = []) {
        $this->request_status = 'completed';
        if (!empty($response)) {
            $this->raw_response = $response;
        }
        return $this->save();
    }

    public function markAsFailed($response = []) {
        $this->request_status = 'failed';
        if (!empty($response)) {
            $this->raw_response = $response;
        }
        return $this->save();
    }

    public function isCompleted() {
        return $this->request_status === 'completed';
    }

    public function isPending() {
        return $this->request_status === 'pending';
    }

    public function isFailed() {
        return $this->request_status === 'failed';
    }
} 