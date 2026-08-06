<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 10.5px;
            line-height: 1.5;
            color: #1f2937;
            margin: 0;
            padding: 28px 32px;
        }
        .header {
            border-bottom: 3px solid #0f172a;
            padding-bottom: 12px;
            margin-bottom: 20px;
        }
        h1 {
            font-size: 21px;
            font-weight: bold;
            margin: 0 0 4px 0;
            color: #0f172a;
            letter-spacing: -0.3px;
        }
        .meta { color: #64748b; font-size: 9.5px; }
        .meta span + span { margin-left: 18px; }
        h2 {
            font-size: 13px;
            font-weight: bold;
            margin: 0 0 12px 0;
            color: #0f172a;
            padding-left: 8px;
            border-left: 4px solid #0f172a;
        }
        h2.sub { font-size: 11.5px; margin: 22px 0 10px 0; }
        .page-break { page-break-before: always; padding-top: 6px; }

        /* KPI cards — 3 columns */
        .kpi-grid { width: 100%; border-collapse: separate; border-spacing: 6px 8px; margin: 0 -6px; }
        .kpi-grid td {
            width: 33.33%;
            vertical-align: top;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-left: 3px solid #94a3b8;
            border-radius: 4px;
            padding: 12px 14px;
        }
        .kpi-grid td.accent-red { border-left-color: #dc2626; }
        .kpi-grid td.accent-amber { border-left-color: #f59e0b; }
        .kpi-grid td.accent-green { border-left-color: #16a34a; }
        .kpi-grid td.blank { background: none; border: none; }
        .label {
            font-size: 8.5px;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            font-weight: bold;
        }
        .value {
            font-size: 18px;
            font-weight: bold;
            margin-top: 6px;
            color: #0f172a;
            font-variant-numeric: tabular-nums;
        }
        .value.small { font-size: 12.5px; }
        .value.positive { color: #16a34a; }
        .value.negative { color: #dc2626; }
        .value.neutral { color: #6b7280; }

        /* Data tables */
        table.data { width: 100%; border-collapse: collapse; margin-bottom: 4px; }
        table.data th {
            background: #0f172a;
            color: #f8fafc;
            font-size: 8.5px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            text-align: left;
            padding: 7px 9px;
            border: 1px solid #0f172a;
        }
        table.data td { border: 1px solid #e2e8f0; padding: 6px 9px; font-size: 10px; }
        table.data tr:nth-child(even) td { background: #f8fafc; }
        table.data td.numeric, table.data th.numeric { text-align: right; font-variant-numeric: tabular-nums; }
        table.data tfoot td {
            font-weight: bold;
            background: #f1f5f9;
            border-top: 2px solid #cbd5e1;
        }

        /* Badges */
        .badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 3px;
            font-size: 8.5px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.2px;
        }
        .badge-pending { background: #fef3c7; color: #92400e; }
        .badge-processing { background: #dbeafe; color: #1e40af; }
        .badge-shipped { background: #e0e7ff; color: #3730a3; }
        .badge-delivered { background: #d1fae5; color: #065f46; }
        .badge-completed { background: #d1fae5; color: #065f46; }
        .badge-cancelled { background: #fee2e2; color: #991b1b; }
        .badge-paid { background: #d1fae5; color: #065f46; }
        .badge-unpaid { background: #fee2e2; color: #991b1b; }
        .badge-low { background: #fef3c7; color: #92400e; }
        .badge-out { background: #fee2e2; color: #991b1b; }
        .badge-in_stock { background: #d1fae5; color: #065f46; }

        .alert {
            background: #fffbeb;
            border: 1px solid #fbbf24;
            border-left: 4px solid #f59e0b;
            border-radius: 4px;
            padding: 9px 14px;
            margin-bottom: 12px;
            font-size: 10px;
            font-weight: bold;
            color: #92400e;
        }
        .footer {
            margin-top: 36px;
            padding-top: 10px;
            border-top: 1px solid #e2e8f0;
            font-size: 8.5px;
            color: #94a3b8;
            text-align: center;
        }
        .empty-row td { text-align: center; color: #94a3b8; font-style: italic; padding: 16px; }

        /* Distribution bars */
        .dist-block { border: 1px solid #e2e8f0; border-radius: 4px; padding: 14px 16px; margin-top: 4px; }
        .dist-row { margin-bottom: 10px; }
        .dist-row:last-child { margin-bottom: 0; }
        .dist-top { margin-bottom: 4px; }
        .dist-label { font-size: 10px; color: #334155; font-weight: bold; }
        .dist-count { float: right; font-size: 10px; color: #64748b; font-variant-numeric: tabular-nums; }
        .dist-bar-bg {
            display: block;
            width: 100%;
            height: 11px;
            background: #f1f5f9;
            border: 1px solid #e2e8f0;
            border-radius: 3px;
            overflow: hidden;
        }
        .dist-bar-fill { display: block; height: 100%; }
    </style>
</head>
<body>
    {{-- Header --}}
    <div class="header">
        <h1>{{ $title }}</h1>
        <div class="meta">
            <span>{{ __('analytics.export.generated_at') }}: {{ $generatedAt }}</span>
            @if(isset($filtersSummary))<span>Period: {{ $filtersSummary }}</span>@endif
        </div>
    </div>

    {{-- ===================== OVERVIEW (6 KPIs) ===================== --}}
    @php
        $outOfStockCount = isset($lowStockProducts) ? $lowStockProducts->where('stock_quantity', '<=', 0)->count() : 0;
        $lowStockCount = isset($lowStockProducts) ? $lowStockProducts->where('stock_quantity', '>', 0)->count() : 0;
    @endphp

    <h2>Overview</h2>
    <table class="kpi-grid">
        <tr>
            <td class="accent-green">
                <div class="label">{{ __('analytics.kpis.total_revenue') }}</div>
                <div class="value">${{ number_format($kpis['total_revenue'], 2) }}</div>
            </td>
            <td>
                <div class="label">{{ __('analytics.kpis.total_orders') }}</div>
                <div class="value">{{ number_format($kpis['total_orders']) }}</div>
            </td>
            <td>
                <div class="label">{{ __('analytics.kpis.total_customers') }}</div>
                <div class="value">{{ number_format($kpis['total_customers']) }}</div>
            </td>
        </tr>
        <tr>
            <td>
                <div class="label">{{ __('analytics.kpis.total_products') }}</div>
                <div class="value">{{ number_format($kpis['total_products']) }}</div>
            </td>
            <td class="accent-amber">
                <div class="label">Low Stock Products</div>
                <div class="value {{ $lowStockCount > 0 ? 'negative' : '' }}">{{ number_format($lowStockCount) }}</div>
            </td>
            <td class="accent-red">
                <div class="label">Out of Stock</div>
                <div class="value {{ $outOfStockCount > 0 ? 'negative' : '' }}">{{ number_format($outOfStockCount) }}</div>
            </td>
        </tr>
    </table>

    {{-- ===================== PAYMENT METHODS ===================== --}}
    @if(isset($paymentMethodDistribution) && $paymentMethodDistribution)
    <h2>Payment Methods</h2>
    @php
        $payTotal = array_sum($paymentMethodDistribution);
        $payColors = ['cash_on_delivery' => '#8b5cf6', 'KHQR' => '#06b6d4'];
    @endphp
    <div class="dist-block">
        @foreach($paymentMethodDistribution as $method => $count)
            <div class="dist-row">
                <div class="dist-top">
                    <span class="dist-label">{{ ucfirst(str_replace('_', ' ', $method)) }}</span>
                    <span class="dist-count">{{ number_format($count) }} ({{ $payTotal > 0 ? round($count / $payTotal * 100, 1) : 0 }}%)</span>
                </div>
                <span class="dist-bar-bg">
                    <span class="dist-bar-fill" style="width:{{ $payTotal > 0 ? ($count / $payTotal * 100) : 0 }}%;background:{{ $payColors[$method] ?? '#8b5cf6' }}"></span>
                </span>
            </div>
        @endforeach
    </div>
    @endif

    {{-- ===================== ORDER REPORT ===================== --}}
    @if(isset($orderReport) && $orderReport->count())
    <div class="page-break"></div>
    <h2>Order Report</h2>
    <table class="data">
        <thead>
            <tr>
                <th>Order #</th>
                <th>Customer</th>
                <th>Status</th>
                <th>Payment</th>
                <th>Payment Status</th>
                <th class="numeric">Discount</th>
                <th class="numeric">Shipping</th>
                <th class="numeric">Total</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
            @foreach($orderReport as $order)
            <tr>
                <td>{{ $order->order_number }}</td>
                <td>{{ $order->customer?->name ?? '—' }}</td>
                <td><span class="badge badge-{{ $order->status }}">{{ ucfirst($order->status) }}</span></td>
                <td>{{ ucfirst(str_replace('_', ' ', $order->payment_method)) }}</td>
                <td>
                    <span class="badge {{ $order->payment_status === 'paid' ? 'badge-paid' : 'badge-unpaid' }}">
                        {{ ucfirst($order->payment_status) }}
                    </span>
                </td>
                <td class="numeric">${{ number_format($order->discount_amount, 2) }}</td>
                <td class="numeric">${{ number_format($order->shipping_cost, 2) }}</td>
                <td class="numeric">${{ number_format($order->total, 2) }}</td>
                <td>{{ \Carbon\Carbon::parse($order->created_at)->format('M d, Y') }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="5" style="text-align:right;">{{ $orderReport->count() }} orders</td>
                <td class="numeric">${{ number_format($orderReport->sum('discount_amount'), 2) }}</td>
                <td class="numeric">${{ number_format($orderReport->sum('shipping_cost'), 2) }}</td>
                <td class="numeric">${{ number_format($orderReport->sum('total'), 2) }}</td>
                <td></td>
            </tr>
        </tfoot>
    </table>
    @endif

    {{-- ===================== CATEGORY PERFORMANCE ===================== --}}
    @if(isset($categoryPerformance) && $categoryPerformance->count())
    <div class="page-break"></div>
    <h2>Category Performance</h2>
    <table class="data">
        <thead>
            <tr>
                <th>Category</th>
                <th class="numeric">Products</th>
                <th class="numeric">Units Sold</th>
                <th class="numeric">Revenue</th>
                <th class="numeric">Avg. Price</th>
            </tr>
        </thead>
        <tbody>
            @foreach($categoryPerformance as $cat)
            <tr>
                <td>{{ $cat->category_name }}</td>
                <td class="numeric">{{ number_format($cat->product_count) }}</td>
                <td class="numeric">{{ number_format($cat->units_sold) }}</td>
                <td class="numeric">${{ number_format($cat->revenue, 2) }}</td>
                <td class="numeric">${{ number_format($cat->avg_price, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    {{-- ===================== TOP SELLING PRODUCTS ===================== --}}
    <div class="page-break"></div>
    <h2>{{ __('analytics.tables.top_selling_products') }}</h2>
    <table class="data">
        <thead>
            <tr>
                <th>{{ __('analytics.columns.product_name') }}</th>
                <th>{{ __('analytics.columns.sku') }}</th>
                <th class="numeric">{{ __('analytics.columns.quantity_sold') }}</th>
                <th class="numeric">{{ __('analytics.columns.revenue') }}</th>
                <th class="numeric">{{ __('analytics.columns.stock_remaining') }}</th>
                <th>Stock Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($topProducts as $product)
                <tr>
                    <td>{{ $product->product_name }}</td>
                    <td>{{ $product->product_sku }}</td>
                    <td class="numeric">{{ number_format($product->quantity_sold) }}</td>
                    <td class="numeric">${{ number_format($product->revenue, 2) }}</td>
                    <td class="numeric">{{ number_format($product->stock_remaining ?? 0) }}</td>
                    <td>
                        @if(($product->stock_remaining ?? 0) <= 0)
                            <span class="badge badge-out">Out of Stock</span>
                        @elseif(($product->stock_remaining ?? 0) <= ($product->low_stock_threshold ?? 10))
                            <span class="badge badge-low">Low Stock</span>
                        @else
                            <span class="badge badge-in_stock">In Stock</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr class="empty-row"><td colspan="6">{{ __('analytics.empty_state') }}</td></tr>
            @endforelse
        </tbody>
    </table>

    {{-- ===================== TOP CUSTOMERS ===================== --}}
    @if(isset($topCustomers) && $topCustomers->count())
    <div class="page-break"></div>
    <h2>Top Customers</h2>
    <table class="data">
        <thead>
            <tr>
                <th>Customer</th>
                <th>Email</th>
                <th class="numeric">Orders</th>
                <th class="numeric">Total Spent</th>
                <th class="numeric">Avg. Order</th>
                <th>Last Order</th>
            </tr>
        </thead>
        <tbody>
            @foreach($topCustomers as $customer)
            <tr>
                <td>{{ $customer->customer_name }}</td>
                <td>{{ $customer->customer_email }}</td>
                <td class="numeric">{{ number_format($customer->order_count) }}</td>
                <td class="numeric">${{ number_format($customer->total_spent, 2) }}</td>
                <td class="numeric">${{ number_format($customer->avg_order_value, 2) }}</td>
                <td>{{ $customer->last_order_date ? \Carbon\Carbon::parse($customer->last_order_date)->format('M d, Y') : '—' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    {{-- ===================== LOW STOCK & OUT OF STOCK ===================== --}}
    @if(isset($lowStockProducts) && $lowStockProducts->count())
    <div class="page-break"></div>
    <h2>Low Stock &amp; Out of Stock</h2>
    <div class="alert">⚠ {{ number_format($outOfStockCount) }} out of stock, {{ number_format($lowStockCount) }} low stock ({{ number_format($lowStockProducts->count()) }} total).</div>
    <table class="data">
        <thead>
            <tr>
                <th>Product</th>
                <th>SKU</th>
                <th class="numeric">Stock Qty</th>
                <th class="numeric">Threshold</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($lowStockProducts as $product)
            <tr>
                <td>{{ $product->name }}</td>
                <td>{{ $product->sku }}</td>
                <td class="numeric">{{ number_format($product->stock_quantity) }}</td>
                <td class="numeric">{{ number_format($product->low_stock_threshold) }}</td>
                <td>
                    @if($product->stock_quantity <= 0)
                        <span class="badge badge-out">Out of Stock</span>
                    @else
                        <span class="badge badge-low">Low Stock</span>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    {{-- ===================== CUSTOMER REPORT ===================== --}}
    @if(isset($customerReport) && $customerReport->count())
    <div class="page-break"></div>
    <h2>Customer Report</h2>
    <table class="data">
        <thead>
            <tr>
                <th>Customer</th>
                <th>Email</th>
                <th>Phone</th>
                <th class="numeric">Orders</th>
                <th class="numeric">Total Spent</th>
                <th class="numeric">Avg. Order</th>
                <th>Last Order</th>
            </tr>
        </thead>
        <tbody>
            @foreach($customerReport as $customer)
            <tr>
                <td>{{ $customer->name }}</td>
                <td>{{ $customer->email }}</td>
                <td>{{ $customer->phone ?? '—' }}</td>
                <td class="numeric">{{ number_format($customer->total_orders) }}</td>
                <td class="numeric">${{ number_format($customer->total_spent, 2) }}</td>
                <td class="numeric">
                    @php
                        $avgOrder = $customer->total_orders > 0
                            ? $customer->total_spent / $customer->total_orders
                            : 0;
                    @endphp
                    ${{ number_format($avgOrder, 2) }}
                </td>
                <td>{{ $customer->last_order_date ? \Carbon\Carbon::parse($customer->last_order_date)->format('M d, Y') : '—' }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3" style="text-align:right;">{{ $customerReport->count() }} customers</td>
                <td class="numeric">{{ number_format($customerReport->sum('total_orders')) }}</td>
                <td class="numeric">${{ number_format($customerReport->sum('total_spent'), 2) }}</td>
                <td colspan="2"></td>
            </tr>
        </tfoot>
    </table>
    @endif

    {{-- Footer --}}
    <div class="footer">
        {{ $title }} — Generated on {{ $generatedAt }}
    </div>
</body>
</html>