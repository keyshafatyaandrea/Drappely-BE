<?php 

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute; 

class Product extends Model
{

    protected $fillable = [
        'code',
        'name',
        'description',
        'category',
        'size',
        'pattern',
        'color',
        'purchase_price',
        'selling_price',
        'stock',
        'image_path',
        'created_by',
    ];

    protected $casts = [
        'purchase_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'stock' => 'integer',
    ];

    protected $appends = [
        'image_url',
    ];

    protected function imageUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->image_path 
                ? asset('storage/products/' . $this->image_path) 
                : asset('images/placeholder.png'),
        );
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function transactionDetails()
    {
        return $this->hasMany(TransactionDetail::class);
    }

    public function hasEnoughStock(int $quantity): bool
    {
        return $this->stock >= $quantity;
    }

    public function decreaseStock(int $quantity): bool
    {
        if (!$this->hasEnoughStock($quantity)) {
            return false;
        }
        $this->stock -= $quantity;
        return $this->save();
    }

    public function increaseStock(int $quantity): bool
    {
        $this->stock += $quantity;
        return $this->save();
    }

    public function isAvailable(): bool
    {
        return $this->stock > 0;
    }

    public function getStockStatus(): string
    {
        if ($this->stock == 0) {
            return 'Habis';
        } elseif ($this->stock < 10) {
            return 'Hampir Habis';
        } else {
            return 'Tersedia';
        }
    }

    public function getFormattedPrice(): string
    {
        return 'Rp ' . number_format((float)$this->selling_price, 0, ',', '.');
    }

    public function getImageUrl(): string
    {
        if ($this->image_path) {
            return asset('storage/products/' . $this->image_path);
        }
        return asset('images/placeholder.png');
    }
}
