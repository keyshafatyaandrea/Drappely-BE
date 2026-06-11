<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $fillable = [
        'staff_id',
        'total_items',
        'subtotal',
        'discount',
        'tax',
        'total_amount',
        'payment_method',
        'notes',
        'transaction_date',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'discount' => 'decimal:2',
        'tax' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'transaction_date' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function staff()
    {
        return $this->belongsTo(User::class, 'staff_id');
    }

    public function details()
    {
        return $this->hasMany(TransactionDetail::class);
    }

    public function calculateTotal(): float
    {
        $subtotal = $this->details()->sum('subtotal');
        $discount = floatval($this->discount ?? 0);
        $tax = floatval($this->tax ?? 0);
        
        return ($subtotal - $discount) + $tax;
    }

    public function getTotalItems(): int
    {
        return (int) $this->details()->sum('quantity');
    }

    public function getSubtotal(): float
    {
        return floatval($this->details()->sum('subtotal'));
    }

    public function applyDiscount(float $amount): bool
    {
        if ($amount < 0 || $amount > $this->getSubtotal()) {
            return false;
        }

        $this->discount = $amount;
        return $this->save();
    }

    public function applyTax(float $percentage): bool
    {
        if ($percentage < 0 || $percentage > 100) {
            return false;
        }

        $taxAmount = ($this->getSubtotal() - floatval($this->discount ?? 0)) * ($percentage / 100);
        $this->tax = $taxAmount;
        return $this->save();
    }

    public function isCompleted(): bool
    {
        return !is_null($this->transaction_date) && $this->details()->count() > 0;
    }

    public function getDetailedData()
    {
        return [
            'id' => $this->id,
            'staff_name' => $this->staff?->name,
            'transaction_date' => $this->transaction_date->format('Y-m-d H:i:s'),
            'details' => $this->details()->with('product')->get()->map(function ($detail) {
                return [
                    'product_name' => $detail->product->name,
                    'product_code' => $detail->product->code,
                    'quantity' => $detail->quantity,
                    'price' => $detail->price,
                    'subtotal' => $detail->subtotal,
                ];
            }),
            'subtotal' => $this->getSubtotal(),
            'discount' => floatval($this->discount),
            'tax' => floatval($this->tax),
            'total_amount' => floatval($this->total_amount),
            'payment_method' => $this->payment_method,
            'notes' => $this->notes,
        ];
    }

    public function getFormattedTotal(): string
    {
        return 'Rp ' . number_format($this->total_amount, 0, ',', '.');
    }

    public static function getTodayTransactions()
    {
        return self::whereDate('transaction_date', today())
            ->with('staff')
            ->latest()
            ->get();
    }

    public static function getTransactionsByPeriod($startDate, $endDate)
    {
        return self::whereBetween('transaction_date', [$startDate, $endDate])
            ->with('staff', 'details.product')
            ->latest()
            ->get();
    }

    public static function getTodayRevenue(): float
    {
        return floatval(self::whereDate('transaction_date', today())
            ->sum('total_amount'));
    }

    public static function getTodayProfit(): float
    {
        $transactions = self::getTodayTransactions();
        $profit = 0;

        foreach ($transactions as $transaction) {
            foreach ($transaction->details as $detail) {
                $product = $detail->product;
                $profitPerUnit = $product->selling_price - $product->purchase_price;
                $profit += $profitPerUnit * $detail->quantity;
            }
        }

        return $profit;
    }
}
