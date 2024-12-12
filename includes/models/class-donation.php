<?php
class WP_Donation_System_Donation extends WP_Donation_System_Model
{
    protected static $table_name = 'donation_system_donations';
    protected static $fillable = [
        'donor_name',
        'donor_email',
        'donor_phone',
        'amount',
        'currency',
        'payment_method',
        'status',
        'notes',
        'metadata',
        'is_anonymous',
        'is_recurring'
    ];

    // Relationships
    public function mpesaTransaction() {
        return WP_Donation_System_MPesa_Transaction::query()
            ->where('donation_id', $this->id)
            ->first();
    }

    // Scopes
    public static function pending() {
        return static::query()->where('status', 'pending');
    }

    public static function completed() {
        return static::query()->where('status', 'completed');
    }

    public static function failed() {
        return static::query()->where('status', 'failed');
    }

    // Mutators
    protected function setMetadataAttribute($value) {
        $this->attributes['metadata'] = is_array($value) ? json_encode($value) : $value;
    }

    // Accessors
    protected function getMetadataAttribute($value) {
        return $value ? json_decode($value, true) : [];
    }

    // Business Logic
    public function markAsCompleted() {
        $this->status = 'completed';
        return $this->save();
    }

    public function markAsFailed() {
        $this->status = 'failed';
        return $this->save();
    }

    public function isCompleted() {
        return $this->status === 'completed';
    }

    public function isPending() {
        return $this->status === 'pending';
    }

    public function isFailed() {
        return $this->status === 'failed';
    }
}