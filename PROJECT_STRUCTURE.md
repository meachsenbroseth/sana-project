# Project Structure

```text
.
.
├── app
│   ├── Actions
│   │   └── Fortify
│   │       ├── CreateNewCustomer.php
│   │       ├── CreateNewUser.php
│   │       └── ResetUserPassword.php
│   ├── Ai
│   │   ├── Agents
│   │   │   ├── AiBusinessAssistantAgent.php
│   │   │   ├── OrderCoach.php
│   │   │   └── ProductCoach.php
│   │   └── Tools
│   │       └── BusinessIntelligenceContextTool.php
│   ├── Concerns
│   │   ├── PasswordValidationRules.php
│   │   └── ProfileValidationRules.php
│   ├── Console
│   │   └── Commands
│   │       └── GenerateProductEmbeddings.php
│   ├── Events
│   ├── Filament
│   │   ├── Exports
│   │   │   ├── AnalyticsOrderReportExporter.php
│   │   │   └── OrderExporter.php
│   │   ├── Pages
│   │   │   ├── Auth
│   │   │   │   └── EditProfile.php
│   │   │   ├── AiBusinessAssistant.php
│   │   │   ├── Dashboard.php
│   │   │   ├── Reports.php
│   │   │   └── SiteSettings.php
│   │   ├── Resources
│   │   │   ├── Brands
│   │   │   │   ├── Pages
│   │   │   │   │   ├── CreateBrand.php
│   │   │   │   │   ├── EditBrand.php
│   │   │   │   │   └── ListBrands.php
│   │   │   │   ├── Schemas
│   │   │   │   │   └── BrandForm.php
│   │   │   │   ├── Tables
│   │   │   │   │   └── BrandsTable.php
│   │   │   │   └── BrandResource.php
│   │   │   ├── Categories
│   │   │   │   ├── Pages
│   │   │   │   │   ├── CreateCategory.php
│   │   │   │   │   ├── EditCategory.php
│   │   │   │   │   └── ListCategories.php
│   │   │   │   ├── RelationManagers
│   │   │   │   ├── Schemas
│   │   │   │   │   └── CategoryForm.php
│   │   │   │   ├── Tables
│   │   │   │   │   └── CategoriesTable.php
│   │   │   │   └── CategoryResource.php
│   │   │   ├── Customers
│   │   │   │   ├── Pages
│   │   │   │   │   ├── CreateCustomer.php
│   │   │   │   │   ├── EditCustomer.php
│   │   │   │   │   └── ListCustomers.php
│   │   │   │   ├── Schemas
│   │   │   │   │   └── CustomerForm.php
│   │   │   │   ├── Tables
│   │   │   │   │   └── CustomersTable.php
│   │   │   │   └── CustomerResource.php
│   │   │   ├── Employees
│   │   │   │   ├── Pages
│   │   │   │   │   ├── CreateEmployee.php
│   │   │   │   │   ├── EditEmployee.php
│   │   │   │   │   └── ListEmployees.php
│   │   │   │   ├── Schemas
│   │   │   │   │   └── EmployeeForm.php
│   │   │   │   ├── Tables
│   │   │   │   │   └── EmployeesTable.php
│   │   │   │   └── EmployeeResource.php
│   │   │   ├── Orders
│   │   │   │   ├── Pages
│   │   │   │   │   ├── CreateOrder.php
│   │   │   │   │   ├── EditOrder.php
│   │   │   │   │   └── ListOrders.php
│   │   │   │   ├── Schemas
│   │   │   │   │   └── OrderForm.php
│   │   │   │   ├── Tables
│   │   │   │   │   └── OrdersTable.php
│   │   │   │   └── OrderResource.php
│   │   │   ├── Permissions
│   │   │   │   ├── Pages
│   │   │   │   │   ├── CreatePermission.php
│   │   │   │   │   ├── EditPermission.php
│   │   │   │   │   └── ListPermissions.php
│   │   │   │   ├── Schemas
│   │   │   │   │   └── PermissionForm.php
│   │   │   │   ├── Tables
│   │   │   │   │   └── PermissionsTable.php
│   │   │   │   └── PermissionResource.php
│   │   │   ├── Products
│   │   │   │   ├── Pages
│   │   │   │   │   ├── CreateProduct.php
│   │   │   │   │   ├── EditProduct.php
│   │   │   │   │   └── ListProducts.php
│   │   │   │   ├── Schemas
│   │   │   │   │   └── ProductForm.php
│   │   │   │   ├── Tables
│   │   │   │   │   └── ProductsTable.php
│   │   │   │   └── ProductResource.php
│   │   │   ├── RestockOrders
│   │   │   │   ├── Pages
│   │   │   │   ├── RelationManagers
│   │   │   │   ├── Schemas
│   │   │   │   └── Tables
│   │   │   ├── Reviews
│   │   │   │   ├── Pages
│   │   │   │   │   ├── CreateReview.php
│   │   │   │   │   ├── EditReview.php
│   │   │   │   │   └── ListReviews.php
│   │   │   │   ├── Schemas
│   │   │   │   │   └── ReviewForm.php
│   │   │   │   ├── Tables
│   │   │   │   │   └── ReviewsTable.php
│   │   │   │   └── ReviewResource.php
│   │   │   ├── ShippingMethods
│   │   │   │   ├── Pages
│   │   │   │   │   ├── CreateShippingMethod.php
│   │   │   │   │   ├── EditShippingMethod.php
│   │   │   │   │   └── ListShippingMethods.php
│   │   │   │   ├── Schemas
│   │   │   │   │   └── ShippingMethodForm.php
│   │   │   │   ├── Tables
│   │   │   │   │   └── ShippingMethodsTable.php
│   │   │   │   └── ShippingMethodResource.php
│   │   │   └── Suppliers
│   │   │       ├── Pages
│   │   │       ├── RelationManagers
│   │   │       ├── Schemas
│   │   │       └── Tables
│   │   └── Widgets
│   │       ├── Reports
│   │       │   ├── Concerns
│   │       │   │   └── InteractsWithAnalytics.php
│   │       │   ├── AnalyticsInsightsWidget.php
│   │       │   ├── AnalyticsStatsOverview.php
│   │       │   ├── CustomerGrowthChart.php
│   │       │   ├── CustomerReportTable.php
│   │       │   ├── OrderReportTable.php
│   │       │   ├── OrdersChart.php
│   │       │   ├── ProductPerformanceChart.php
│   │       │   ├── SalesRevenueChart.php
│   │       │   └── TopSellingProductsTable.php
│   │       ├── LatestOrders.php
│   │       ├── LowStockWidget.php
│   │       ├── OrderStatuChart.php
│   │       ├── RevenueChart.php
│   │       └── StatsOverview.php
│   ├── Http
│   │   ├── Controllers
│   │   │   ├── Auth
│   │   │   │   ├── FacebookAuthController.php
│   │   │   │   └── GoogleAuthController.php
│   │   │   ├── CheckoutController.php
│   │   │   ├── Controller.php
│   │   │   ├── CustomerOrderDeliveryConfirmationController.php
│   │   │   └── LocaleController.php
│   │   ├── Middleware
│   │   │   └── SetLocale.php
│   │   └── Requests
│   │       └── ConfirmOrderDeliveryRequest.php
│   ├── Livewire
│   │   ├── Actions
│   │   │   └── Logout.php
│   │   └── ProductReviewForm.php
│   ├── Mail
│   │   └── OrderConfirmation.php
│   ├── Models
│   │   ├── Reports
│   │   │   └── TopSellingProductReport.php
│   │   ├── Address.php
│   │   ├── Brand.php
│   │   ├── Category.php
│   │   ├── Customer.php
│   │   ├── OrderItem.php
│   │   ├── Order.php
│   │   ├── OrderStatusHistory.php
│   │   ├── ProductImage.php
│   │   ├── Product.php
│   │   ├── Review.php
│   │   ├── Setting.php
│   │   ├── ShippingMethod.php
│   │   ├── SiteSetting.php
│   │   └── User.php
│   ├── Observers
│   │   ├── OrderObserver.php
│   │   ├── ProductObserver.php
│   │   └── ReviewObserver.php
│   ├── Policies
│   │   ├── BrandPolicy.php
│   │   ├── CategoryPolicy.php
│   │   ├── CustomerPolicy.php
│   │   ├── OrderPolicy.php
│   │   ├── PermissionPolicy.php
│   │   ├── ProductPolicy.php
│   │   ├── ReviewPolicy.php
│   │   ├── RolePolicy.php
│   │   ├── ShippingMethodPolicy.php
│   │   └── UserPolicy.php
│   ├── Providers
│   │   ├── Filament
│   │   │   └── AdminPanelProvider.php
│   │   ├── AppServiceProvider.php
│   │   └── FortifyServiceProvider.php
│   └── Services
│       ├── Ai
│       │   └── BusinessIntelligenceContextService.php
│       ├── Analytics
│       │   ├── AnalyticsFilters.php
│       │   ├── AnalyticsService.php
│       │   └── AnalyticsTableResolver.php
│       └── OrderStockService.php
├── bootstrap
│   ├── app.php
│   └── providers.php
├── config
│   ├── ai.php
│   ├── app.php
│   ├── auth.php
│   ├── cache.php
│   ├── database.php
│   ├── filament-shield.php
│   ├── filesystems.php
│   ├── fortify.php
│   ├── logging.php
│   ├── mail.php
│   ├── permission.php
│   ├── queue.php
│   ├── scout.php
│   ├── services.php
│   └── session.php
├── database
│   ├── factories
│   │   ├── ShippingMethodFactory.php
│   │   ├── SiteSettingFactory.php
│   │   └── UserFactory.php
│   ├── migrations
│   │   ├── 0001_01_01_000000_create_users_table.php
│   │   ├── 0001_01_01_000001_create_cache_table.php
│   │   ├── 0001_01_01_000002_create_jobs_table.php
│   │   ├── 2025_08_14_170933_add_two_factor_columns_to_users_table.php
│   │   ├── 2026_01_29_135632_add_phone_status_columns_to_users_table.php
│   │   ├── 2026_01_29_140059_create_customers_table.php
│   │   ├── 2026_01_29_141647_create_addresses_table.php
│   │   ├── 2026_01_30_025040_create_categories_table.php
│   │   ├── 2026_01_30_025406_create_brands_table.php
│   │   ├── 2026_01_30_025547_create_products_table.php
│   │   ├── 2026_01_30_031012_create_product_images_table.php
│   │   ├── 2026_01_30_031315_create_orders_table.php
│   │   ├── 2026_01_30_033139_create_order_items_table.php
│   │   ├── 2026_01_30_033729_create_order_status_histories_table.php
│   │   ├── 2026_01_30_034030_create_reviews_table.php
│   │   ├── 2026_01_30_045905_create_settings_table.php
│   │   ├── 2026_01_31_113339_create_permission_tables.php
│   │   ├── 2026_02_20_130410_add_status_to_products_table.php
│   │   ├── 2026_02_22_033219_create_notifications_table.php
│   │   ├── 2026_02_23_034208_create_shipping_methods_table.php
│   │   ├── 2026_02_23_034211_create_site_settings_table.php
│   │   ├── 2026_02_23_123930_add_banner_images_to_site_settings_table.php
│   │   ├── 2026_02_24_021612_create_imports_table.php
│   │   ├── 2026_02_24_021613_create_exports_table.php
│   │   ├── 2026_02_24_021614_create_failed_import_rows_table.php
│   │   ├── 2026_02_24_024945_add_completed_to_orders_status_enum.php
│   │   ├── 2026_03_03_133629_add_facebook_id_to_customers_table.php
│   │   ├── 2026_03_04_045759_create_agent_conversations_table.php
│   │   ├── 2026_04_08_054628_add_avatar_to_users_table.php
│   │   ├── 2026_05_12_032933_add_stock_deducted_at_to_orders_table.php
│   │   └── 2026_08_04_094414_add_done_by_done_at_to_orders_table.php
│   ├── seeders
│   │   ├── ComputerStoreSeeder.php
│   │   ├── DatabaseSeeder.php
│   │   └── SuperAdminUserSeeder.php
│   └── database.sqlite
├── public
│   ├── build
│   │   ├── assets
│   │   │   ├── app-CEervjoj.css
│   │   │   ├── app-l0sNRNKZ.js
│   │   │   └── theme-CwiUZg5U.css
│   │   └── manifest.json
│   ├── css
│   │   └── filament
│   │       └── filament
│   │           └── app.css
│   ├── fonts
│   │   └── filament
│   │       └── filament
│   │           └── inter
│   │               ├── index.css
│   │               ├── inter-cyrillic-ext-wght-normal-IYF56FF6.woff2
│   │               ├── inter-cyrillic-wght-normal-JEOLYBOO.woff2
│   │               ├── inter-greek-ext-wght-normal-EOVOK2B5.woff2
│   │               ├── inter-greek-wght-normal-IRE366VL.woff2
│   │               ├── inter-latin-ext-wght-normal-HA22NDSG.woff2
│   │               ├── inter-latin-wght-normal-NRMW37G5.woff2
│   │               └── inter-vietnamese-wght-normal-CE5GGD3W.woff2
│   ├── images
│   │   ├── logo.png
│   │   └── phannacomputer.png
│   ├── js
│   │   └── filament
│   │       ├── actions
│   │       │   └── actions.js
│   │       ├── filament
│   │       │   ├── app.js
│   │       │   └── echo.js
│   │       ├── forms
│   │       │   └── components
│   │       │       ├── checkbox-list.js
│   │       │       ├── code-editor.js
│   │       │       ├── color-picker.js
│   │       │       ├── date-time-picker.js
│   │       │       ├── file-upload.js
│   │       │       ├── key-value.js
│   │       │       ├── markdown-editor.js
│   │       │       ├── rich-editor.js
│   │       │       ├── select.js
│   │       │       ├── slider.js
│   │       │       ├── tags-input.js
│   │       │       └── textarea.js
│   │       ├── notifications
│   │       │   └── notifications.js
│   │       ├── schemas
│   │       │   ├── components
│   │       │   │   ├── actions.js
│   │       │   │   ├── tabs.js
│   │       │   │   └── wizard.js
│   │       │   └── schemas.js
│   │       ├── support
│   │       │   └── support.js
│   │       ├── tables
│   │       │   ├── components
│   │       │   │   └── columns
│   │       │   │       ├── checkbox.js
│   │       │   │       ├── select.js
│   │       │   │       ├── text-input.js
│   │       │   │       └── toggle.js
│   │       │   └── tables.js
│   │       └── widgets
│   │           └── components
│   │               ├── stats-overview
│   │               │   └── stat
│   │               │       └── chart.js
│   │               └── chart.js
│   ├── apple-touch-icon.png
│   ├── favicon.ico
│   ├── favicon.svg
│   ├── index.php
│   └── robots.txt
├── resources
│   ├── css
│   │   ├── filament
│   │   │   └── admin
│   │   │       └── theme.css
│   │   └── app.css
│   ├── js
│   │   └── app.js
│   ├── lang
│   │   ├── en
│   │   │   ├── analytics.php
│   │   │   ├── app.php
│   │   │   ├── auth.php
│   │   │   ├── brand.php
│   │   │   ├── category.php
│   │   │   ├── customer.php
│   │   │   ├── employee.php
│   │   │   ├── messages.php
│   │   │   ├── nav.php
│   │   │   ├── order.php
│   │   │   ├── pagination.php
│   │   │   ├── passwords.php
│   │   │   ├── permission.php
│   │   │   ├── product.php
│   │   │   ├── review.php
│   │   │   ├── shipping_method.php
│   │   │   ├── site_settings.php
│   │   │   ├── table.php
│   │   │   └── validation.php
│   │   └── km
│   │       ├── analytics.php
│   │       ├── app.php
│   │       ├── auth.php
│   │       ├── brand.php
│   │       ├── category.php
│   │       ├── customer.php
│   │       ├── employee.php
│   │       ├── messages.php
│   │       ├── nav.php
│   │       ├── order.php
│   │       ├── pagination.php
│   │       ├── passwords.php
│   │       ├── permission.php
│   │       ├── product.php
│   │       ├── review.php
│   │       ├── shipping_method.php
│   │       ├── site_settings.php
│   │       ├── table.php
│   │       └── validation.php
│   └── views
│       ├── auth
│       │   └── customer
│       │       ├── forgot-password.blade.php
│       │       ├── login.blade.php
│       │       ├── register.blade.php
│       │       └── reset-password.blade.php
│       ├── checkout
│       │   ├── cancel.blade.php
│       │   └── success.blade.php
│       ├── components
│       │   ├── ⚡cart-icon.blade.php
│       │   ├── ⚡footer.blade.php
│       │   ├── ⚡navbar.blade.php
│       │   ├── ⚡product-card.blade.php
│       │   ├── ⚡search-bar.blade.php
│       │   └── ⚡user-auth.blade.php
│       ├── filament
│       │   ├── components
│       │   │   └── language-switcher.blade.php
│       │   └── pages
│       │       ├── auth
│       │       │   └── edit-profile.blade.php
│       │       ├── ai-business-assistant.blade.php
│       │       └── site-settings.blade.php
│       ├── flux
│       │   ├── icon
│       │   │   ├── book-open-text.blade.php
│       │   │   ├── chevrons-up-down.blade.php
│       │   │   ├── folder-git-2.blade.php
│       │   │   └── layout-grid.blade.php
│       │   └── navlist
│       │       └── group.blade.php
│       ├── layouts
│       │   └── app.blade.php
│       ├── livewire
│       │   └── product-review-form.blade.php
│       ├── mail
│       │   └── order-confirmation.blade.php
│       ├── pages
│       │   ├── auth
│       │   │   ├── confirm-password.blade.php
│       │   │   ├── forgot-password.blade.php
│       │   │   ├── login.blade.php
│       │   │   ├── register.blade.php
│       │   │   ├── reset-password.blade.php
│       │   │   ├── two-factor-challenge.blade.php
│       │   │   └── verify-email.blade.php
│       │   ├── customer
│       │   │   ├── ⚡dashboard.blade.php
│       │   │   ├── ⚡order-details.blade.php
│       │   │   └── ⚡profile.blade.php
│       │   ├── settings
│       │   │   ├── two-factor
│       │   │   │   └── ⚡recovery-codes.blade.php
│       │   │   ├── ⚡appearance.blade.php
│       │   │   ├── ⚡delete-user-form.blade.php
│       │   │   ├── layout.blade.php
│       │   │   ├── ⚡password.blade.php
│       │   │   ├── ⚡profile.blade.php
│       │   │   └── ⚡two-factor.blade.php
│       │   ├── ⚡about-page.blade.php
│       │   ├── ⚡cart.blade.php
│       │   ├── ⚡chatbot.blade.php
│       │   ├── ⚡checkout.blade.php
│       │   ├── ⚡homepage.blade.php
│       │   ├── ⚡orders.blade.php
│       │   ├── ⚡product-details.blade.php
│       │   └── ⚡product-listing.blade.php
│       └── pdf
│           ├── analytics-report.blade.php
│           └── invoice.blade.php
├── routes
│   ├── console.php
│   ├── settings.php
│   └── web.php
├── stubs
│   ├── agent.stub
│   ├── middleware.stub
│   ├── structured-agent.stub
│   └── tool.stub
├── tests
│   ├── Feature
│   │   ├── Analytics
│   │   │   └── AnalyticsServiceTest.php
│   │   ├── Auth
│   │   │   ├── AuthenticationTest.php
│   │   │   ├── EmailVerificationTest.php
│   │   │   ├── PasswordConfirmationTest.php
│   │   │   ├── PasswordResetTest.php
│   │   │   ├── RegistrationTest.php
│   │   │   └── TwoFactorChallengeTest.php
│   │   ├── Filament
│   │   │   ├── AiBusinessAssistantPageTest.php
│   │   │   ├── EmployeeManagementSecurityTest.php
│   │   │   └── ReportsPageTest.php
│   │   ├── Settings
│   │   │   ├── PasswordUpdateTest.php
│   │   │   ├── ProfileUpdateTest.php
│   │   │   └── TwoFactorAuthenticationTest.php
│   │   ├── CheckoutKhqrPaymentTest.php
│   │   ├── ComputerStoreSeederTest.php
│   │   ├── CustomerOrderDeliveryConfirmationTest.php
│   │   ├── CustomerOrderDetailsReviewTest.php
│   │   ├── DashboardTest.php
│   │   ├── ExampleTest.php
│   │   ├── GenerateProductEmbeddingsCommandTest.php
│   │   ├── OrderStockManagementTest.php
│   │   ├── ProductAvailabilityManagementTest.php
│   │   ├── ProductReviewWorkflowTest.php
│   │   ├── ProductSearchWithScoutTest.php
│   │   └── SiteSettingsFeatureTest.php
│   ├── Unit
│   │   ├── Filament
│   │   │   └── NavigationLocalizationTest.php
│   │   ├── ExampleTest.php
│   │   ├── LocaleSwitchTest.php
│   │   ├── ProductCoachTest.php
│   │   └── ProductScoutSearchTest.php
│   ├── Pest.php
│   └── TestCase.php
├── AGENTS.md
├── artisan
├── boost.json
├── CLAUDE.md
├── composer.json
├── composer.lock
├── DOCKER_DEPLOYMENT.md
├── Dockerfile
├── entrypoint.sh
├── GEMINI.md
├── package.json
├── package-lock.json
├── phpunit.xml
├── pint.json
├── PROJECT_STRUCTURE.md
└── vite.config.js

156 directories, 383 files
```
