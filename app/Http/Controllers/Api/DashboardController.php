<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function getSummary()
    {
        return response()->json([
            'total_revenue' => 0,
            'total_transactions' => 0,
            'total_products' => Product::count(),
            'revenue_growth' => 0,
            'transaction_growth' => 0
        ]);
    }

    public function getDailySalesChart()
    {
        return response()->json([
            ['date' => 'Senin', 'sales' => 0],
            ['date' => 'Selasa', 'sales' => 0],
            ['date' => 'Rabu', 'sales' => 0],
            ['date' => 'Kamis', 'sales' => 0],
            ['date' => 'Jumat', 'sales' => 0],
            ['date' => 'Sabtu', 'sales' => 0],
            ['date' => 'Minggu', 'sales' => 0],
        ]);
    }

    public function getWeeklySalesChart()
    {
        return response()->json([]);
    }

    public function getMonthlySalesChart()
    {
        return response()->json([
            ['month' => 'Jan', 'sales' => 0],
            ['month' => 'Feb', 'sales' => 0],
            ['month' => 'Mar', 'sales' => 0],
            ['month' => 'Apr', 'sales' => 0],
            ['month' => 'Mei', 'sales' => 0],
            ['month' => 'Jun', 'sales' => 0],
        ]);
    }

    public function getProfitChart()
    {
        return response()->json([
            ['date' => '01 Juni', 'profit' => 0],
        ]);
    }

    public function getTopProducts()
    {
        return response()->json([]);
    }
}
