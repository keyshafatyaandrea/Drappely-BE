<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Struk Pembayaran</title>
    <style>
        body { 
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; 
            font-size: 12px; 
            color: #000000; 
            line-height: 1.4;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .invoice-title { font-size: 20px; font-weight: bold; letter-spacing: 2px; }
        .table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .border-top { border-top: 1px solid #000000; }
        .border-bottom { border-bottom: 1px solid #000000; }
        .dashed-top { border-top: 1px dashed #cccccc; }
        .padding-v { padding: 8px 0; }
    </style>
</head>
<body>

    <div class="text-center">
        <span class="invoice-title">DRAPPELY</span>
        <div style="font-size: 10px; font-weight: bold; margin-top: 4px;">Your all-in-one fashion store management system</div>
        <div style="font-size: 10px; color: #555555;">New York, USA</div>
    </div>

    <br><br>

    <table style="width: 100%; font-size: 11px;">
        <tr>
            <td>DATE: {{ $transaction->created_at->format('d M Y H:i') }}</td>
            <td class="text-right">METHOD: <b>{{ strtoupper($transaction->payment_method) }}</b></td>
        </tr>
        <tr>
            <td colspan="2" class="text-right">CASHIER: {{ $transaction->staff?->name ?? 'ALDI' }}</td>
        </tr>
    </table>

    <table class="table">
        <thead>
            <tr class="border-top border-bottom">
                <th align="left" class="padding-v">ITEM DESCRIPTION</th>
                <th align="right" class="padding-v">TOTAL</th>
            </tr>
        </thead>
        <tbody>
            @foreach($transaction->details as $detail)
            <tr>
                <td class="padding-v">
                    <span style="text-transform: uppercase; font-weight: bold;">{{ $detail->product->name }}</span><br>
                    <span style="color: #555555;">{{ $detail->quantity }} × IDR {{ number_format($detail->price, 0, ',', '.') }}</span>
                </td>
                <td align="right" class="padding-v" style="font-weight: bold;">
                    IDR {{ number_format($detail->price * $detail->quantity, 0, ',', '.') }}
                </td>
            </tr>
            @endforeach

            <tr class="border-top">
                <td class="padding-v">SUBTOTAL</td>
                <td align="right" class="padding-v">IDR {{ number_format($transaction->subtotal, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>TAX</td>
                <td align="right">IDR {{ number_format($transaction->tax, 0, ',', '.') }}</td>
            </tr>
            <tr style="font-size: 14px; font-weight: bold;">
                <td class="padding-v" style="border-top: 1px solid #000000;">GRAND TOTAL</td>
                <td align="right" class="padding-v" style="border-top: 1px solid #000000;">IDR {{ number_format($transaction->total_amount, 0, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>

    <div class="text-center dashed-top" style="margin-top: 40px; padding-top: 15px; font-size: 9px; color: #777777;">
        <p style="margin: 0 0 3px 0;">Thank you for shopping with us!</p>
        <p style="margin: 0;">We hope you love your purchase and can't wait to see you again.</p>
    </div>

</body>
</html>