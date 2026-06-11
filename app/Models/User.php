<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'is_admin',
        'is_active',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'is_admin' => 'boolean',
        'is_active' => 'boolean',
        'email_verified_at' => 'datetime',
    ];

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [
            'email' => $this->email,
            'is_admin' => $this->is_admin,
        ];
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'staff_id');
    }

    public function products()
    {
        return $this->hasMany(Product::class, 'created_by');
    }

    public function isAdmin(): bool
    {
        return $this->is_admin === 1;
    }

    public function isStaff(): bool
    {
        return $this->is_admin === 0;
    }

    public function isActive(): bool
    {
        return $this->is_active === 1;
    }

    public function getTotalTransactions()
    {
        return $this->transactions()->count();
    }

    public function getTotalSales()
    {
        return $this->transactions()->sum('total_amount');
    }

    public function getTodayTransactions()
    {
        return $this->transactions()
            ->whereDate('created_at', today())
            ->get();
    }
}
