<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111827; }
        h1 { font-size: 20px; margin-bottom: 4px; }
        h2 { font-size: 14px; margin-top: 24px; margin-bottom: 8px; }
        .meta { color: #6b7280; margin-bottom: 20px; }
        .grid { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        .grid td { padding: 8px; border: 1px solid #e5e7eb; width: 25%; vertical-align: top; }
        .label { font-size: 10px; color: #6b7280; text-transform: uppercase; }
        .value { font-size: 14px; font-weight: bold; margin-top: 4px; }
        table.data { width: 100%; border-collapse: collapse; }
        table.data th, table.data td { border: 1px solid #e5e7eb; padding: 6px; text-align: left; }
        table.data th { background: #f3f4f6; }
    </style>
</head>
<body>
    <h1>{{ $title }}</h1>
    <div class="meta">{{ __('analytics.export.generated_at') }}: {{ $generatedAt }}</div>

    <h2>{{ __('analytics.widgets.kpi_heading') }}</h2>
    <table class="grid">
        <tr>
            <td><div class="label">{{ __('analytics.kpis.total_revenue') }}</div><div class="value">${{ number_format($kpis['total_revenue'], 2) }}</div></td>
            <td><div class="label">{{ __('analytics.kpis.total_orders') }}</div><div class="value">{{ number_format($kpis['total_orders']) }}</div></td>
            <td><div class="label">{{ __('analytics.kpis.total_customers') }}</div><div class="value">{{ number_format($kpis['total_customers']) }}</div></td>
            <td><div class="label">{{ __('analytics.kpis.total_products') }}</div><div class="value">{{ number_format($kpis['total_products']) }}</div></td>
        </tr>
        <tr>
            <td><div class="label">{{ __('analytics.kpis.average_order_value') }}</div><div class="value">${{ number_format($kpis['average_order_value'], 2) }}</div></td>
            <td><div class="label">{{ __('analytics.kpis.orders_today') }}</div><div class="value">{{ number_format($kpis['orders_today']) }}</div></td>
            <td><div class="label">{{ __('analytics.kpis.revenue_today') }}</div><div class="value">${{ number_format($kpis['revenue_today'], 2) }}</div></td>
            <td><div class="label">{{ __('analytics.kpis.pending_orders') }}</div><div class="value">{{ number_format($kpis['pending_orders']) }}</div></td>
        </tr>
    </table>

    <h2>{{ __('analytics.widgets.insights_heading') }}</h2>
    <table class="grid">
        <tr>
            <td><div class="label">{{ __('analytics.insights.best_selling_product') }}</div><div class="value">{{ $insights['best_selling_product'] ?? '—' }}</div></td>
            <td><div class="label">{{ __('analytics.insights.most_active_customer') }}</div><div class="value">{{ $insights['most_active_customer'] ?? '—' }}</div></td>
            <td><div class="label">{{ __('analytics.insights.most_popular_category') }}</div><div class="value">{{ $insights['most_popular_category'] ?? '—' }}</div></td>
            <td><div class="label">{{ __('analytics.insights.average_clv') }}</div><div class="value">${{ number_format($insights['average_customer_lifetime_value'], 2) }}</div></td>
        </tr>
    </table>

    <h2>{{ __('analytics.tables.top_selling_products') }}</h2>
    <table class="data">
        <thead>
            <tr>
                <th>{{ __('analytics.columns.product_name') }}</th>
                <th>{{ __('analytics.columns.sku') }}</th>
                <th>{{ __('analytics.columns.quantity_sold') }}</th>
                <th>{{ __('analytics.columns.revenue') }}</th>
                <th>{{ __('analytics.columns.stock_remaining') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($topProducts as $product)
                <tr>
                    <td>{{ $product->product_name }}</td>
                    <td>{{ $product->product_sku }}</td>
                    <td>{{ number_format($product->quantity_sold) }}</td>
                    <td>${{ number_format($product->revenue, 2) }}</td>
                    <td>{{ number_format($product->stock_remaining ?? 0) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5">{{ __('analytics.empty_state') }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
