<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Portal extends Model
{
    protected $fillable = [
        'portal_name', 'portal_reg_no', 'editor_name', 'editor_email', 'editor_mobile', 'password',
        'database_name', 'status', 'provisioned_at', 'provision_error', 'last_login_at',
    ];

    protected $hidden = ['password', 'api_token_hash'];

    protected $casts = [
        'password' => 'hashed',
        'provisioned_at' => 'datetime',
        'last_login_at' => 'datetime',
        'api_token_created_at' => 'datetime',
        'api_token_last_used_at' => 'datetime',
    ];

    public const MOBILE_REGEX = '/^(?:\+?88)?01[3-9]\d{8}$/';

    public static function mobileRegex(): string
    {
        return config('tenancy.mobile_regex') ?: self::MOBILE_REGEX;
    }

    /** +8801712-345678 / 8801712345678 / 01712 345678  =>  01712345678 */
    public static function normalizeMobile(string $mobile): string
    {
        $digits = preg_replace('/[\s\-()]/', '', $mobile);
        return preg_replace('/^(\+?88)/', '', $digits);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function charges(): HasMany
    {
        return $this->hasMany(PortalCharge::class);
    }

    public function graceRequests(): HasMany
    {
        return $this->hasMany(GraceRequest::class);
    }

    public function purchaseRequests(): HasMany
    {
        return $this->hasMany(PurchaseRequest::class);
    }

    public function supportSessions(): HasMany
    {
        return $this->hasMany(PortalSupportSession::class);
    }

    /**
     * Create a new public-API token for this portal (the old one stops working at once).
     * Only the SHA-256 hash is stored, so the returned plain token can never be shown again.
     */
    public function issueApiToken(): string
    {
        $token = 'npk_' . Str::random(max(16, (int) config('portal_api.token_length', 20)));

        $this->forceFill([
            'api_token_hash' => hash('sha256', $token),
            'api_token_last4' => substr($token, -4),
            'api_token_created_at' => now(),
            'api_token_last_used_at' => null,
        ])->save();

        return $token;
    }

    public function revokeApiToken(): void
    {
        $this->forceFill([
            'api_token_hash' => null,
            'api_token_last4' => null,
            'api_token_created_at' => null,
            'api_token_last_used_at' => null,
        ])->save();
    }

    public function hasApiToken(): bool
    {
        return $this->api_token_hash !== null;
    }

    public function isProvisioned(): bool
    {
        return $this->provisioned_at !== null && $this->database_name !== null;
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
