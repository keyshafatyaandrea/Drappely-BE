<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Barryvdh\DomPDF\Facade\Pdf;


class ReportController extends Controller
{
    //download struk pdf
    public function downloadReceiptPdf($id)
    {
        try {
            $transaction = Transaction::with(['staff', 'details.product'])->findOrFail($id);
            
            $pdf = Pdf::loadView('exports.receipt_pdf', compact('transaction'));
            return $pdf->download("struk_{$transaction->id}.pdf");
        } catch (\Exception $e) {
            //mengembalikan pesan error kalo transaksi ga ketemu / ada kendala
            return response()->json([
                'status' => 'error', 
                'message' => 'Gagal membuat file PDF: ' . $e->getMessage()
            ], 500);
        }
    }
}