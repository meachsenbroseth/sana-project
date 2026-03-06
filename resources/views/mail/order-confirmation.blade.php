<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; background-color: #f3f4f6; padding: 20px; color: #374151; line-height: 1.6; -webkit-font-smoothing: antialiased; }
        .container { max-width: 600px; margin: 0 auto; background: #ffffff; padding: 40px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .text-center { text-align: center; }
        .logo { width: 50px; height: 50px; background-color: #2563eb; border-radius: 50%; margin: 0 auto 20px; display: block; }
        .title { color: #1f2937; font-size: 24px; font-weight: bold; margin-top: 0; margin-bottom: 20px; text-align: center; }

        /* Typography */
        p { margin: 0 0 10px 0; font-size: 14px; }
        .strong { font-weight: 600; color: #111827; }

        /* Customer Info Box */
        .info-box { background-color: #f8fafc; padding: 20px; border-radius: 6px; margin: 25px 0; border: 1px solid #e2e8f0; }
        .info-box h3 { margin: 0 0 10px 0; font-size: 15px; color: #111827; }

        /* Links */
        .links-container { margin: 20px 0; }
        .links-container a { color: #2563eb; text-decoration: none; font-size: 14px; margin-right: 15px; font-weight: 500; }
        .links-container a:hover { text-decoration: underline; }

        /* Items Table */
        .items-table { width: 100%; border-collapse: collapse; margin-top: 15px; font-size: 14px; }
        .items-table th { text-align: right; padding-bottom: 8px; border-bottom: 2px solid #e2e8f0; color: #64748b; font-weight: 600; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; }
        .items-table td { padding: 15px 0; border-bottom: 1px solid #e2e8f0; vertical-align: top; }
        .item-image { width: 50px; height: 50px; background-color: #f1f5f9; border-radius: 4px; display: inline-block; }
        .item-details { padding-left: 15px; padding-right: 15px; color: #334155; }
        .item-qty { text-align: center; color: #64748b; width: 60px; font-size: 13px; }
        .item-price { text-align: right; font-weight: 500; width: 80px; color: #111827; }

        /* Totals */
        .totals-wrapper { width: 100%; display: table; margin-top: 15px; }
        .totals-spacer { display: table-cell; width: 50%; }
        .totals-table { display: table-cell; width: 50%; text-align: right; font-size: 14px; }
        .totals-table table { width: 100%; border-collapse: collapse; }
        .totals-table td { padding: 6px 0; }
        .totals-table .label { color: #64748b; padding-right: 20px; }
        .totals-table .value { color: #111827; font-weight: 500; }
        .totals-table .total-row td { font-size: 18px; font-weight: bold; color: #111827; padding-top: 15px; border-top: 1px solid #cbd5e1; }

        /* Footer Split */
        .footer-info { margin-top: 40px; border-top: 1px solid #e2e8f0; padding-top: 25px; display: table; width: 100%; font-size: 14px; }
        .footer-col { display: table-cell; width: 50%; vertical-align: top; }
        .meta-group { margin-bottom: 15px; }
        .meta-label { color: #64748b; font-size: 12px; font-weight: 600; text-transform: uppercase; margin-bottom: 4px; display: block; letter-spacing: 0.5px; }
        .meta-value { color: #111827; display: block; }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo"></div>

        <h2 class="title">Order successfully!</h2>

        <p>Dear {{ $order->shipping_full_name }},</p>
        <p>Thank you for purchasing our products, we will contact you via phone <span class="strong">{{ $order->shipping_phone }}</span> to confirm order!</p>

        <div class="info-box">
            <h3>Customer Information</h3>
            <p><span class="strong">Name:</span> {{ $order->shipping_full_name }}</p>
            <p><span class="strong">Phone:</span> {{ $order->shipping_phone }}</p>
            <p><span class="strong">Address:</span>
                {{ $order->shipping_address_line_1 }}@if($order->shipping_address_line_2), {{ $order->shipping_address_line_2 }}@endif,
                {{ $order->shipping_city }}@if($order->shipping_state), {{ $order->shipping_state }}@endif,
                {{ $order->shipping_country }}
            </p>
        </div>

        <p class="strong">Here's what you ordered:</p>

        <div class="links-container">
            <a wire:navigate href="{{ route('customer.orders.show', $order->id) }}">View order</a>
            <span style="color: #cbd5e1;">|</span>
            <a wire:navigate href="{{ route('home') }}" style="margin-left: 15px;">Go to our shop</a>
        </div>

        <table class="items-table">
            <thead>
                <tr>
                    <th style="text-align: left;">Item</th>
                    <th>Quantity</th>
                    <th>Price</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->items as $item)
                <tr>
                    <td>
                        <table style="width: 100%;">
                            <tr>
                                <td style="width: 50px; padding: 0; border: none;">
                                    <div class="item-image"></div>
                                </td>
                                <td class="item-details" style="border: none; padding-top: 0; padding-bottom: 0;">
                                    <strong>{{ $item->product_name }}</strong>
                                </td>
                            </tr>
                        </table>
                    </td>
                    <td class="item-qty">x {{ $item->quantity }}</td>
                    <td class="item-price">${{ number_format($item->unit_amount, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="totals-wrapper">
            <div class="totals-spacer"></div>
            <div class="totals-table">
                <table>
                    <tr>
                        <td class="label">Subtotal</td>
                        <td class="value">${{ number_format($order->subtotal, 2) }}</td>
                    </tr>
                    @if($order->discount_amount > 0)
                    <tr>
                        <td class="label" style="color: #059669;">Discount</td>
                        <td class="value" style="color: #059669;">-${{ number_format($order->discount_amount, 2) }}</td>
                    </tr>
                    @endif
                    <tr>
                        <td class="label">Shipping fee</td>
                        <td class="value">
                            @if($order->shipping_cost > 0)
                                ${{ number_format($order->shipping_cost, 2) }}
                            @else
                                <span style="color: #059669;">Free</span>
                            @endif
                        </td>
                    </tr>
                    @if($order->tax_amount > 0)
                    <tr>
                        <td class="label">Tax</td>
                        <td class="value">${{ number_format($order->tax_amount, 2) }}</td>
                    </tr>
                    @endif
                    <tr class="total-row">
                        <td class="label">Total</td>
                        <td class="value">${{ number_format($order->total, 2) }}</td>
                    </tr>
                </table>
            </div>
        </div>

        <div class="footer-info">
            <div class="footer-col">
                <div class="meta-group">
                    <span class="meta-label">Shipping Method</span>
                    <span class="meta-value">{{ ucfirst($order->shipping_method ?? 'Standard Delivery') }}</span>
                </div>
                <div class="meta-group">
                    <span class="meta-label">Payment Method</span>
                    <span class="meta-value">
                        @if($order->payment_method === 'KHQR')
                            Bakong KHQR
                        @elseif($order->payment_method === 'cash_on_delivery')
                            Cash on Delivery
                        @else
                            {{ ucfirst(str_replace('_', ' ', $order->payment_method)) }}
                        @endif
                    </span>
                </div>
            </div>
            <div class="footer-col">
                <div class="meta-group">
                    <span class="meta-label">Order number</span>
                    <span class="meta-value" style="color: #2563eb; font-weight: 500;">#{{ $order->order_number }}</span>
                </div>
            </div>
        </div>

    </div>
</body>
</html>
