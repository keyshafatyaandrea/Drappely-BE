<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Barryvdh\DomPDF\Facade\Pdf;


class ReportController extends Controller
{
    public function downloadReceiptPdf($id)
    {
        try {
            $transaction = Transaction::with(['staff', 'details.product'])->findOrFail($id);
            
            $pdf = Pdf::loadView('exports.receipt_pdf', compact('transaction'));
            return $pdf->download("struk_{$transaction->id}.pdf");
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error', 
                'message' => 'Gagal membuat file PDF: ' . $e->getMessage()
            ], 500);
        }
    }
}
