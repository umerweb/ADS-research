# Cloud Packaging Keyword Research

WordPress plugin that automates Google Ads Keyword Planner research for WooCommerce products.

## Requirements
- WordPress 6.0+
- WooCommerce 7.0+
- PHP 8.0+
- Google Ads account with Keyword Planner access
- OAuth credentials already used by `paid-seo.php`

## Installation
1. Zip the plugin folder or upload via Plugins → Add New → Upload
2. Activate the plugin
3. Go to **Cloud Packaging → Keyword Research**

## Configuration
Required constants in `wp-config.php` (same ones used by `paid-seo.php`):
```php
define('CP_GOOGLE_ADS_CLIENT_ID', '...');
define('CP_GOOGLE_ADS_CLIENT_SECRET', '...');
define('CP_GOOGLE_ADS_REFRESH_TOKEN', '...');
define('CP_GOOGLE_ADS_CUSTOMER_ID', '...');