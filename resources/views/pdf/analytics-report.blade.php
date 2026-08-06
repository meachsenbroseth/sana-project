<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111827; margin: 0; padding: 20px; }
        h1 { font-size: 22px; margin: 0 0 2px 0; color: #1e293b; }
        h2 { font-size: 14px; margin: 24px 0 8px 0; color: #334155; border-bottom: 2px solid #e2e8f0; padding-bottom: 4px; }
        .meta { color: #6b7280; font-size: 10px; margin-bottom: 20px; }
        .meta span { margin-right: 16px; }

        .kpi-grid { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        .kpi-grid td { padding: 10px; border: 1px solid #e5e7eb; width: 25%; vertical-align: top; }
        .kpi-grid tr:nth-child(even) td { background: #f9fafb; }
        .label { font-size: 9px; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px; }
        .value { font-size: 16px; font-weight: bold; margin-top: 4px; color: #0f172a; }
        .value.positive { color: #16a34a; }
        .value.negative { color: #dc2626; }
        .value.neutral { color: #6b7280; }

        table.data { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        table.data th, table.data td { border: 1px solid #e5e7eb; padding: 6px 8px; text-align: left; font-size: 11px; }
        table.data th { background: #f1f5f9; color: #475569; font-size: 10px; text-transform: uppercase; letter-spacing: 0.3px; }
        table.data tr:nth-child(even) { background: #f9fafb; }
        table.data td.numeric { text-align: right; font-variant-numeric: tabular-nums; }

        .badge { display: inline-block; padding: 1px 6px; border-radius: 3px; font-size: 9px; font-weight: bold; text-transform: uppercase; }
        .badge-pending { background: #fef3c7; color: #92400e; }
        .badge-processing { background: #dbeafe; color: #1e40af; }
        .badge-shipped { background: #e0e7ff; color: #3730a3; }
        .badge-delivered { background: #d1fae5; color: #065f46; }
        .badge-cancelled { background: #fee2e2; color: #991b1b; }
        .badge-completed { background: #d1fae5; color: #065f46; }
        .badge-paid { background: #d1fae5; color: #065f46; }
        .badge-unpaid { background: #fee2e2; color: #991b1b; }
        .badge-low { background: #fef3c7; color: #92400e; }
        .badge-out { background: #fee2e2; color: #991b1b; }
        .badge-in_stock { background: #d1fae5; color: #065f46; }

        .dist-row { margin-bottom: 6px; }
        .dist-label { display: inline-block; width: 110px; font-size: 10px; color: #475569; }
        .dist-bar-bg { display: inline-block; width: 260px; height: 14px; background: #f1f5f9; border: 1px solid #e2e8f0; position: relative; vertical-align: middle; }
        .dist-bar-fill { height: 100%; }
        .dist-count { display: inline-block; margin-left: 6px; font-size: 10px; color: #6b7280; font-weight: bold; }

        .two-col { width: 100%; }
        .two-col td { width: 50%; vertical-align: top; padding-right: 12px; }

        .page-break { page-break-before: always; }

        .alert { background: #fffbeb; border: 1px solid #fbbf24; border-radius: 4px; padding: 8px 12px; margin-bottom: 12px; font-size: 10px; color: #92400e; }
    </style>
</head>
<body>
    {{-- Header --}}
    <h1>{{ $title }}</h1>
    <div class="meta">
        <span>{{ __('analytics.export.generated_at') }}: {{ $generatedAt }}</span>
        @if(isset($filtersSummary))<span>Period: {{ $filtersSummary }}</span>@endif
    </div>

    {{-- KPI Overview --}}
    <h2>{{ __('analytics.widgets.kpi_heading') }}</h2>
    <table class="kpi-grid">
        <tr>
            <td>
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
            <td>
                <div class="label">{{ __('analytics.kpis.total_products') }}</div>
                <div class="value">{{ number_format($kpis['total_products']) }}</div>
            </td>
        </tr>
        <tr>
            <td>
                <div class="label">{{ __('analytics.kpis.average_order_value') }}</div>
                <div class="value">${{ number_format($kpis['average_order_value'], 2) }}</div>
            </td>
            <td>
                <div class="label">{{ __('analytics.kpis.orders_today') }}</div>
                <div class="value">{{ number_format($kpis['orders_today']) }}</div>
            </td>
            <td>
                <div class="label">{{ __('analytics.kpis.revenue_today') }}</div>
                <div class="value">${{ number_format($kpis['revenue_today'], 2) }}</div>
            </td>
            <td>
                <div class="label">{{ __('analytics.kpis.pending_orders') }}</div>
                <div class="value">{{ number_format($kpis['pending_orders']) }}</div>
            </td>
        </tr>
    </table>

    {{-- Insights --}}
    <h2>{{ __('analytics.widgets.insights_heading') }}</h2>
    <table class="kpi-grid">
        <tr>
            <td>
                <div class="label">{{ __('analytics.insights.best_selling_product') }}</div>
                <div class="value" style="font-size:13px">{{ $insights['best_selling_product'] ?? '—' }}</div>
            </td>
            <td>
                <div class="label">{{ __('analytics.insights.most_active_customer') }}</div>
                <div class="value" style="font-size:13px">{{ $insights['most_active_customer'] ?? '—' }}</div>
            </td>
            <td>
                <div class="label">{{ __('analytics.insights.most_popular_category') }}</div>
                <div class="value" style="font-size:13px">{{ $insights['most_popular_category'] ?? '—' }}</div>
            </td>
            <td>
                <div class="label">{{ __('analytics.insights.average_clv') }}</div>
                <div class="value">${{ number_format($insights['average_customer_lifetime_value'], 2) }}</div>
            </td>
        </tr>
    </table>

    {{-- Revenue Breakdown --}}
    @if(isset($revenueBreakdown))
    <h2>Revenue Breakdown</h2>
    <table class="kpi-grid">
        <tr>
            <td>
                <div class="label">Gross Revenue (Subtotal)</div>
                <div class="value">${{ number_format($revenueBreakdown['subtotal'], 2) }}</div>
            </td>
            <td>
                <div class="label">Total Discounts</div>
                <div class="value negative">${{ number_format($revenueBreakdown['discount_amount'], 2) }}</div>
            </td>
            <td>
                <div class="label">Shipping Revenue</div>
                <div class="value">${{ number_format($revenueBreakdown['shipping_cost'], 2) }}</div>
            </td>
            <td>
                <div class="label">Net Revenue</div>
                <div class="value positive">${{ number_format($revenueBreakdown['net_revenue'], 2) }}</div>
            </td>
        </tr>
    </table>
    @endif

    {{-- Order Status & Payment Distribution (side by side) --}}
    <table class="two-col"><tr>
    <td>
    @if(isset($orderStatusDistribution) && $orderStatusDistribution)
    <h2>Order Status Distribution</h2>
    @php
        $statusTotal = array_sum($orderStatusDistribution);
        $statusColors = [
            'pending' => '#f59e0b', 'processing' => '#3b82f6',
            'shipped' => '#6366f1', 'delivered' => '#10b981',
            'completed' => '#10b981', 'cancelled' => '#ef4444',
        ];
    @endphp
    @foreach($orderStatusDistribution as $status => $count)
        <div class="dist-row">
            <span class="dist-label">{{ ucfirst($status) }}</span>
            <span class="dist-bar-bg">
                <span class="dist-bar-fill" style="width:{{ $statusTotal > 0 ? ($count / $statusTotal * 100) : 0 }}%;background:{{ $statusColors[$status] ?? '#3b82f6' }}"></span>
            </span>
            <span class="dist-count">{{ number_format($count) }} ({{ $statusTotal > 0 ? round($count / $statusTotal * 100, 1) : 0 }}%)</span>
        </div>
    @endforeach
    @endif
    </td>
    <td>
    @if(isset($paymentMethodDistribution) && $paymentMethodDistribution)
    <h2>Payment Methods</h2>
    @php
        $payTotal = array_sum($paymentMethodDistribution);
        $payColors = ['cash_on_delivery' => '#8b5cf6', 'KHQR' => '#06b6d4'];
    @endphp
    @foreach($paymentMethodDistribution as $method => $count)
        <div class="dist-row">
            <span class="dist-label">{{ ucfirst(str_replace('_', ' ', $method)) }}</span>
            <span class="dist-bar-bg">
                <span class="dist-bar-fill" style="width:{{ $payTotal > 0 ? ($count / $payTotal * 100) : 0 }}%;background:{{ $payColors[$method] ?? '#8b5cf6' }}"></span>
            </span>
            <span class="dist-count">{{ number_format($count) }} ({{ $payTotal > 0 ? round($count / $payTotal * 100, 1) : 0 }}%)</span>
        </div>
    @endforeach
    @endif
    </td>
    </tr></table>

    {{-- Payment Status Overview --}}
    @if(isset($paymentStatusDistribution) && $paymentStatusDistribution)
    <h2>Payment Status Overview</h2>
    @php
        $payStatusChunks = array_chunk($paymentStatusDistribution, 4, true);
    @endphp
    <table class="kpi-grid">
        @foreach($payStatusChunks as $chunk)
        <tr>
            @foreach($chunk as $pStatus => $pCount)
            <td>
                <div class="label">{{ ucfirst($pStatus) }}</div>
                <div class="value {{ $pStatus === 'paid' ? 'positive' : ($pStatus === 'pending' ? 'neutral' : 'negative') }}">{{ number_format($pCount) }}</div>
            </td>
            @endforeach
            @for($i = count($chunk); $i < 4; $i++) <td></td> @endfor
        </tr>
        @endforeach
    </table>
    @endif

    {{-- Category Performance --}}
    @if(isset($categoryPerformance) && $categoryPerformance->count())
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

    <div class="page-break"></div>

    {{-- Top Selling Products --}}
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
                        @elseif(($product->stock_remaining ?? 0) <= 10)
                            <span class="badge badge-low">Low Stock</span>
                        @else
                            <span class="badge badge-in_stock">In Stock</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="6">{{ __('analytics.empty_state') }}</td></tr>
            @endforelse
        </tbody>
    </table>

    {{-- Top Customers --}}
    @if(isset($topCustomers) && $topCustomers->count())
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

    {{-- Low Stock Alerts --}}
    @if(isset($lowStockProducts) && $lowStockProducts->count())
    <div class="alert">⚠ {{ $lowStockProducts->count() }} product(s) are low on stock or out of stock.</div>
    <h2>Low Stock Alerts</h2>
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

    <div class="page-break"></div>

    {{-- Recent Orders --}}
    @if(isset($recentOrders) && $recentOrders->count())
    <h2>Recent Orders</h2>
    <table class="data">
        <thead>
            <tr>
                <th>Order #</th>
                <th>Customer</th>
                <th class="numeric">Subtotal</th>
                <th class="numeric">Discount</th>
                <th class="numeric">Shipping</th>
                <th class="numeric">Total</th>
                <th>Payment</th>
                <th>Status</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
            @foreach($recentOrders as $order)
            <tr>
                <td>{{ $order->order_number }}</td>
                <td>{{ $order->customer_name }}</td>
                <td class="numeric">${{ number_format($order->subtotal, 2) }}</td>
                <td class="numeric">${{ number_format($order->discount_amount, 2) }}</td>
                <td class="numeric">${{ number_format($order->shipping_cost, 2) }}</td>
                <td class="numeric">${{ number_format($order->total, 2) }}</td>
                <td>
                    <span class="badge {{ $order->payment_status === 'paid' ? 'badge-paid' : 'badge-unpaid' }}">
                        {{ ucfirst($order->payment_status) }}
                    </span>
                </td>
                <td>
                    <span class="badge badge-{{ $order->status }}">{{ ucfirst($order->status) }}</span>
                </td>
                <td>{{ \Carbon\Carbon::parse($order->created_at)->format('M d, Y') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    {{-- Footer --}}
    <div style="margin-top: 32px; padding-top: 8px; border-top: 1px solid #e5e7eb; font-size: 9px; color: #9ca3af; text-align: center;">
        {{ $title }} — Generated on {{ $generatedAt }}
    </div>
</body>
</html>