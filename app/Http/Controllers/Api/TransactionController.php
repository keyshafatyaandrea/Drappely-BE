<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 15);
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $staffId = $request->input('staff_id');

        // pake with biar relasi langsung ke-load, jadi response udah include
        // data staff sama product detail tanpa harus ngequery lagi
        // `->` itu artinya kita manggil method di object/hasil query sebelumnya
        $query = Transaction::with('staff', 'details.product');

        if ($startDate && $endDate) {
            $query->whereBetween('transaction_date', [$startDate, $endDate]);
        }

        if ($staffId && auth()->user()->is_admin) {
            $query->where('staff_id', $staffId);
        } elseif (!auth()->user()->is_admin) {
            $query->where('staff_id', auth()->user()->id);
        }

        $transactions = $query->latest()->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'data' => $transactions->items(),
            'pagination' => [
                'total' => $transactions->total(),
                'per_page' => $transactions->perPage(),
                'current_page' => $transactions->currentPage(),
                'last_page' => $transactions->lastPage(),
            ]
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'discount' => 'nullable|numeric|min:0',
            'payment_method' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            //validasi stok semua produk
            foreach ($validated['items'] as $item) {
                $product = Product::find($item['product_id']);

                if (!$product) {
                    DB::rollBack();
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Product not found: ' . $item['product_id']
                    ], 404);
                }

                //proteksi penanganan method hasEnoughStock
                if (method_exists($product, 'hasEnoughStock')) {
                    if (!$product->hasEnoughStock($item['quantity'])) {
                        DB::rollBack();
                        return response()->json([
                            'status' => 'error',
                            'message' => "Stok {$product->name} tidak cukup."
                        ], 400);
                    }
                } else {
                    if ($product->stock < $item['quantity']) {
                        DB::rollBack();
                        return response()->json([
                            'status' => 'error',
                            'message' => "Stok {$product->name} tidak cukup. Tersedia: {$product->stock}"
                        ], 400);
                    }
                }
            }

            //hitung total transaksi
            $subtotal = 0;
            $totalItems = 0;
            $itemsDetail = [];

            foreach ($validated['items'] as $item) {
                $product = Product::find($item['product_id']);
                $itemSubtotal = $product->selling_price * $item['quantity'];
                
                $subtotal += $itemSubtotal;
                $totalItems += $item['quantity'];

                // `[]` di sini berarti masukin item baru ke array `$itemsDetail`.
                // Ini array normal PHP, bukan pemanggilan method.
                $itemsDetail[] = [
                    'product' => $product,
                    'quantity' => $item['quantity'],
                    'price' => $product->selling_price,
                    'subtotal' => $itemSubtotal,
                ];
            }

            //hitung discount dan tax
            $discount = $validated['discount'] ?? 0;
            $taxPercentage = 0; 
            $afterDiscount = $subtotal - $discount;
            $tax = $afterDiscount * ($taxPercentage / 100);
            $totalAmount = $afterDiscount + $tax;

            // INSERT Transaction record
            // `Transaction::create([...])` pake array buat field yang mau disimpen ke DB.
            // `auth()->id()` itu pemanggilan method auth, jadi di sini pakai `->`.
            $transaction = Transaction::create([
                'staff_id' => auth()->id() ?? 1, // Fallback ke id 1 kalo testing tanpa login token
                'total_items' => $totalItems,
                'subtotal' => $subtotal,
                'discount' => $discount,
                'tax' => $tax,
                'total_amount' => $totalAmount,
                'payment_method' => $validated['payment_method'] ?? 'cash',
                'notes' => $validated['notes'] ?? null,
                'transaction_date' => now(),
            ]);

            // INSERT transaction details
            foreach ($itemsDetail as $detail) {
                // lagi pake array untuk input data detail transaksi.
                TransactionDetail::create([
                    'transaction_id' => $transaction->id,
                    'product_id' => $detail['product']->id,
                    'quantity' => $detail['quantity'],
                    'price' => $detail['price'],
                    'subtotal' => $detail['subtotal'],
                ]);
            }

            //KURANGI STOK PRODUK
            foreach ($validated['items'] as $item) {
                $product = Product::find($item['product_id']);
                if (method_exists($product, 'decreaseStock')) {
                    $product->decreaseStock($item['quantity']);
                } else {
                    $product->decrement('stock', $item['quantity']);
                }
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Transaction created successfully',
                'data' => $transaction
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Transaction failed: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getTodaySummary()
    {
        //hitung akumulasi omset data staff riil harian dan bulanan
        $totalRevenueToday = Transaction::whereDate('transaction_date', Carbon::today())->sum('total_amount');
        $totalTransactionsToday = Transaction::whereDate('transaction_date', Carbon::today())->count();
        
        $totalRevenueMonth = Transaction::whereMonth('transaction_date', Carbon::now()->month)
                                        ->whereYear('transaction_date', Carbon::now()->year)
                                        ->sum('total_amount');

        //mengirim flat property object biar langsung nembak masuk ke state dashboard React
        return response()->json([
            'status' => 'success',
            'data' => [
                'total_revenue' => floatval($totalRevenueToday),
                'total_transactions' => $totalTransactionsToday,
                'revenue_growth' => 0, 
                'today_revenue' => floatval($totalRevenueToday),
                'today_transactions' => $totalTransactionsToday,
                'today_profit' => floatval($totalRevenueToday * 0.2),
                'monthly_revenue' => floatval($totalRevenueMonth),
                'monthly_profit' => floatval($totalRevenueMonth * 0.2),
            ]
        ]);
    }

    public function getDailyAnalytics(Request $request)
    {
        $days = $request->input('days', 7);
        $analytics = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $dateString = $date->format('Y-m-d');

            $revenue = Transaction::whereDate('transaction_date', $dateString)->sum('total_amount');

            $analytics[] = [
                'date' => $date->format('d M'),
                'day_name' => $date->format('D'),
                'sales' => floatval($revenue), // 👈 DIGANTI ke 'sales' agar singkron dengan dataKey="sales" di React Admin
            ];
        }


        return response()->json([
            'status' => 'success',
            'data' => $analytics
        ]);
    }

    public function getMonthlyAnalytics(Request $request)
    {
        $year = $request->input('year', now()->year);
        $analytics = [];

        for ($month = 1; $month <= 12; $month++) {
            $revenue = Transaction::whereYear('transaction_date', $year)
                                  ->whereMonth('transaction_date', $month)
                                  ->sum('total_amount');

            $analytics[] = [
                'month' => Carbon::create()->month($month)->format('F'),
                'sales' => floatval($revenue), 
            ];
        }

        return response()->json([
            'status' => 'success',
            'data' => $analytics
        ]);
    }

    public function getProfitAnalytics(Request $request)
    {
        $analytics = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $revenue = Transaction::whereDate('transaction_date', $date->format('Y-m-d'))->sum('total_amount');
            
            $analytics[] = [
                'date' => $date->format('d M'),
                'profit' => floatval($revenue * 0.2), 
            ];
        }

        return response()->json([
            'status' => 'success',
            'data' => $analytics
        ]);
    }

    /**
     * GET /api/dashboard/recent-transactions
     * Return recent transaction history (per transaction, not aggregated by day)
     */
    public function recentTransactions(Request $request)
    {
        $limit = (int) $request->input('limit', 10);

        $transactions = Transaction::with('staff')
            ->latest('transaction_date')
            ->take($limit)
            ->get()
            ->map(function ($t) {
                return [
                    'id' => $t->id,
                    'date' => $t->transaction_date?->format('d M Y') ?? null,
                    'time' => $t->transaction_date?->format('H:i') ?? null,
                    'day' => $t->transaction_date?->format('D') ?? null,
                    'total_items' => (int) $t->total_items,
                    'subtotal' => floatval($t->subtotal),
                    'tax' => floatval($t->tax),
                    'total' => floatval($t->total_amount),
                    'cashier' => $t->staff?->name ?? null,
                ];
            });

        return response()->json([
            'status' => 'success',
            'data' => $transactions
        ]);
    }
}