<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Invoice #{{ $order->order_number }}</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #333;
            font-size: 14px;
            line-height: 1.5;
        }

        .header {
            width: 100%;
            margin-bottom: 40px;
        }

        .header td {
            vertical-align: top;
        }

        .company-details {
            text-align: right;
        }

        h1 {
            color: #111;
            margin-bottom: 5px;
        }

        .billing-section {
            width: 100%;
            margin-bottom: 30px;
        }

        .billing-section td {
            vertical-align: top;
            width: 50%;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }

        .items-table th {
            background-color: #f8f9fa;
            border-bottom: 2px solid #ddd;
            text-align: left;
            padding: 10px;
        }

        .items-table td {
            border-bottom: 1px solid #eee;
            padding: 10px;
        }

        .totals-table {
            width: 100%;
            border-collapse: collapse;
        }

        .totals-table td {
            padding: 8px 10px;
            text-align: right;
        }

        .totals-table .label {
            width: 70%;
            color: #666;
        }

        .totals-table .amount {
            width: 30%;
        }

        .total-row td {
            border-top: 2px solid #333;
            font-weight: bold;
            font-size: 16px;
        }
    </style>
</head>

<body>

    <table class="header">
        <tr>
            <td>
                <h1>INVOICE</h1>
                <p>Order #: {{ $order->order_number }}<br>
                    Date: {{ $order->created_at->format('M d, Y') }}<br>
                    Status: {{ ucfirst($order->payment_status) }}
                    Payment Method: {{ ucfirst($order->payment_method) }}<br>
                </p>
            </td>
            <td class="company-details">
                <img src="{{ public_path('images/logo.png') }}" alt="Phanna Computer Shop"
                    style="max-width: 180px; margin-bottom: 10px;">
                <p>123 Tech Street<br>
                    Phnom Penh, Cambodia<br>
                    contact@phannacomputer.com
                </p>
            </td>
        </tr>
    </table>

    <table class="billing-section">
        <tr>
            <td>
                <strong>Billed To:</strong><br>
                {{ $order->customer->name }}<br>
                {{ $order->customer->email }}
            </td>
            <td>
                <strong>Shipped To:</strong><br>
                {{ $order->shipping_full_name }}<br>
                {{ $order->shipping_address_line_1 }}<br>
                @if ($order->shipping_address_line_2)
                    {{ $order->shipping_address_line_2 }}<br>
                @endif
                {{ $order->shipping_city }}, {{ $order->shipping_country }}
            </td>
        </tr>
    </table>

    <table class="items-table">
        <thead>
            <tr>
                <th>Item Description</th>
                <th>Qty</th>
                <th>Unit Price</th>
                <th style="text-align: right;">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($order->items as $item)
                <tr>
                    <td>
                        <strong>{{ $item->product_name }}</strong><br>
                        <span style="font-size: 12px; color: #666;">SKU: {{ $item->product_sku }}</span>
                    </td>
                    <td>{{ $item->quantity }}</td>
                    <td>${{ number_format($item->unit_amount ?? $item->price, 2) }}</td>
                    <td style="text-align: right;">${{ number_format($item->total_amount ?? $item->subtotal, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals-table">
        <tr>
            <td class="label">Subtotal:</td>
            <td class="amount">${{ number_format($order->subtotal ?? $order->items->sum('total_amount'), 2) }}</td>
        </tr>
        @if ($order->discount_amount > 0)
            <tr>
                <td class="label">Discount:</td>
                <td class="amount">-${{ number_format($order->discount_amount, 2) }}</td>
            </tr>
        @endif
        <tr>
            <td class="label">Shipping:</td>
            <td class="amount">{{ $order->shipping_cost > 0 ? '$' . number_format($order->shipping_cost, 2) : 'Free' }}
            </td>
        </tr>
        @if ($order->tax_amount > 0)
            <tr>
                <td class="label">Tax:</td>
                <td class="amount">${{ number_format($order->tax_amount, 2) }}</td>
            </tr>
        @endif
        <tr class="total-row">
            <td class="label">Total:</td>
            <td class="amount">${{ number_format($order->total, 2) }}</td>
        </tr>
    </table>

</body>

</html>
