<?php

return [
    'reports' => 'Reports',
    'page_title' => 'Analytics & Reports',

    'ai_assistant' => [
        'navigation_label' => 'AI Assistant',
        'title' => 'AI Assistant',
        'chat_heading' => 'Business AI Assistant',
        'chat_subheading' => 'Ask about sales, inventory, customers, trends, and recommendations.',
        'suggested_heading' => 'Suggested questions',
        'insights_heading' => 'Automatic insights',
        'clear' => 'Clear chat',
        'copy' => 'Copy response',
        'send' => 'Send',
        'thinking' => 'Analyzing business data...',
        'input_label' => 'Ask the AI assistant',
        'placeholder' => 'Ask in Khmer or English about sales, stock, customers, or opportunities...',
        'welcome_message' => 'សួស្តី! ខ្ញុំជាជំនួយការ AI សម្រាប់វិភាគអាជីវកម្ម។ សួរខ្ញុំអំពីចំណូល ការលក់ ស្តុក អតិថិជន ឬយុទ្ធសាស្ត្របង្កើនការលក់បាន។',
        'error_message' => 'សូមអភ័យទោស។ ខ្ញុំមិនអាចបង្កើតចម្លើយបាននៅពេលនេះទេ។ សូមពិនិត្យការកំណត់ AI provider/model ឬសាកល្បងម្តងទៀត។',
        'metrics' => [
            'revenue_month' => 'Revenue This Month',
            'revenue_growth' => 'Revenue Growth',
            'low_stock' => 'Low Stock Products',
            'next_month' => 'Next Month Forecast',
            'returning_customers' => 'Returning Customers',
        ],
        'forecast_description' => 'Estimate from recent paid orders',
        'low_stock_description' => 'Needs inventory review',
        'retention_description' => 'Customers with repeat purchases',
        'demand_chart_heading' => 'Fast-Moving Product Demand',
        'demand_chart_label' => 'Quantity sold in last 30 days',
        'suggested_questions' => [
            'តើផលិតផលណាលក់ដាច់ជាងគេ?',
            'តើចំណូលខែនេះកើនឡើងប៉ុន្មានភាគរយ?',
            'តើមានផលិតផលណាអស់ស្តុក?',
            'តើខ្ញុំគួរបញ្ជាទិញស្តុកបន្ថែមអ្វីខ្លះ?',
            'Give recommendations to increase revenue.',
        ],
    ],

    'empty_state' => 'No data available',
    'empty_state_description' => 'Try adjusting your filters or date range.',

    'unavailable' => [
        'heading' => 'Product data unavailable',
        'products_description' => 'The product catalog table is missing or not migrated. Order-based metrics are still available where possible.',
        'analytics_description' => 'Required analytics tables are missing. Please run your database migrations.',
    ],

    'payment_methods' => [
        'cash_on_delivery' => 'Cash on Delivery',
        'KHQR' => 'KHQR',
    ],

    'filters' => [
        'heading' => 'Advanced Filters',
        'date_preset' => 'Date Range',
        'start_date' => 'Start Date',
        'end_date' => 'End Date',
        'product' => 'Product',
        'category' => 'Category',
        'customer' => 'Customer',
        'order_status' => 'Order Status',
        'payment_method' => 'Payment Method',
        'presets' => [
            'today' => 'Today',
            'yesterday' => 'Yesterday',
            'this_week' => 'This Week',
            'this_month' => 'This Month',
            'this_year' => 'This Year',
            'custom' => 'Custom Date Range',
        ],
    ],

    'export' => [
        'pdf' => 'Export PDF',
        'excel' => 'Export Excel',
        'csv' => 'Export CSV',
        'generated_at' => 'Generated at',
        'completed' => 'Your analytics export has completed and :count rows were exported.',
        'failed' => ':count rows failed to export.',
    ],

    'widgets' => [
        'kpi_heading' => 'Dashboard Overview',
        'insights_heading' => 'Key Statistics',
    ],

    'kpis' => [
        'total_revenue' => 'Total Revenue',
        'total_orders' => 'Total Orders',
        'total_customers' => 'Total Customers',
        'total_products' => 'Total Products',
        'average_order_value' => 'Average Order Value',
        'orders_today' => 'Orders Today',
        'revenue_today' => 'Revenue Today',
        'pending_orders' => 'Pending Orders',
    ],

    'insights' => [
        'best_selling_product' => 'Best Selling Product',
        'most_active_customer' => 'Most Active Customer',
        'highest_revenue_day' => 'Highest Revenue Day',
        'highest_revenue_month' => 'Highest Revenue Month',
        'most_popular_category' => 'Most Popular Category',
        'average_clv' => 'Average Customer Lifetime Value',
    ],

    'charts' => [
        'sales_revenue' => 'Sales Revenue',
        'orders' => 'Orders',
        'customer_growth' => 'Customer Growth',
        'product_performance' => 'Product Performance',
        'revenue_label' => 'Revenue ($)',
        'orders_label' => 'Orders',
        'customers_label' => 'New Customers',
        'periods' => [
            'daily' => 'Daily Revenue',
            'weekly' => 'Weekly Revenue',
            'monthly' => 'Monthly Revenue',
            'yearly' => 'Yearly Revenue',
        ],
        'groupings' => [
            'by_day' => 'Orders by Day',
            'by_month' => 'Orders by Month',
            'by_status' => 'Orders by Status',
            'per_day' => 'New Customers Per Day',
            'per_month' => 'New Customers Per Month',
            'registration_trend' => 'Customer Registration Trend',
        ],
        'metrics' => [
            'top_selling' => 'Top Selling Products',
            'most_viewed' => 'Most Viewed Products',
            'highest_revenue' => 'Highest Revenue Products',
            'low_stock' => 'Low Stock Products',
        ],
    ],

    'tables' => [
        'top_selling_products' => 'Top Selling Products',
        'customer_report' => 'Customer Report',
        'order_report' => 'Order Report',
    ],

    'columns' => [
        'product_name' => 'Product Name',
        'sku' => 'SKU',
        'quantity_sold' => 'Quantity Sold',
        'revenue' => 'Revenue',
        'stock_remaining' => 'Stock Remaining',
        'customer_name' => 'Customer Name',
        'email' => 'Email',
        'total_orders' => 'Total Orders',
        'total_spent' => 'Total Spent',
        'last_order_date' => 'Last Order Date',
        'order_number' => 'Order Number',
        'customer' => 'Customer',
        'status' => 'Status',
        'payment_method' => 'Payment Method',
        'total' => 'Total',
        'created_date' => 'Created Date',
    ],
];
