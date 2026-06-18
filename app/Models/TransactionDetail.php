<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransactionDetail extends Model
{
    protected $table = 'transaction_details';

    protected $fillable = [
        'transaction_id',
        'product_id',
        'quantity',
        'price',
        'subtotal',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'price' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    
    //hitung subtotal otomatis dari jumlah dan harga
    public function calculateSubtotal(): float
    {
        return floatval($this->quantity) * floatval($this->price);
    }

    //mengambil provit / keuntungan dari detail
    public function getProfit(): float
    {
        if ($this->product) {
            $profitPerUnit = $this->product->selling_price - $this->product->purchase_price;
            return floatval($profitPerUnit) * floatval($this->quantity);
        }
        return 0;
    }

    //format display
    public function getFormattedPrice(): string
    {
        return 'Rp ' . number_format($this->price, 0, ',', '.');
    }

    public function getFormattedSubtotal(): string
    {
        return 'Rp ' . number_format($this->subtotal, 0, ',', '.');
    }
}
