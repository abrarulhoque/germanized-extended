# Germanized AED Totals

A WordPress plugin that extends WooCommerce Germanized Pro's StoreaBill functionality to display invoice totals in AED (United Arab Emirates Dirham) currency alongside the original EUR amounts.

## Features

- **Automatic Currency Conversion**: Converts EUR amounts to AED using real-time exchange rates
- **StoreaBill Integration**: Seamlessly integrates with Germanized Pro's StoreaBill block editor
- **Custom AED Total Block**: New block type specifically for displaying AED totals
- **Daily Rate Updates**: Automatically updates exchange rates daily using the Exchange Rate API
- **Attribution Compliance**: Includes required attribution for the free exchange rate service
- **Responsive Design**: Works perfectly in both print and digital formats
- **Admin Interface**: Easy-to-use settings page for configuration

## Requirements

- WordPress 5.4 or higher
- WooCommerce 3.9 or higher
- WooCommerce Germanized Pro with StoreaBill package
- PHP 7.4 or higher

## Installation

1. Upload the plugin files to `/wp-content/plugins/germanized-aed-totals/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to WooCommerce > AED Currency to configure settings
4. Edit your invoice templates in StoreaBill and add AED Total Row blocks

## Usage

### Adding AED Totals to Invoice Templates

1. Go to StoreaBill templates in your WordPress admin
2. Edit an invoice template
3. In the block editor, find the "AED Total Row" block
4. Add it after any regular total row block
5. Configure the total type (Total, Subtotal, Tax, Shipping, etc.)
6. Customize the heading if needed
7. Save the template

### Block Settings

The AED Total Row block supports the following settings:

- **Total Type**: Choose which total to convert (Total, Subtotal, Tax, etc.)
- **Custom Heading**: Override the auto-generated heading
- **Content Template**: Customize how the AED amount is displayed
- **Hide if Empty**: Optionally hide the row when the amount is zero
- **Borders**: Add visual borders to the row

### API Configuration

The plugin uses the free Exchange Rate API (https://open.er-api.com/) which:

- Updates rates once per day
- Requires attribution (automatically included)
- Has rate limiting (handled automatically)
- Provides reliable EUR to AED conversion rates

### Settings Page

Access the settings at **WooCommerce > AED Currency**:

- View current exchange rate
- See last update timestamp
- Customize attribution text
- Manually update exchange rates

## Technical Details

### Exchange Rate Updates

- Automatic daily updates via WordPress cron
- Manual update option in admin settings
- Fallback rate (4.0) if API is unavailable
- Error logging for troubleshooting

### Block Integration

The plugin extends StoreaBill's block system by:

- Registering custom block type `gaed/aed-total-row`
- Inheriting from StoreaBill's `DynamicBlock` class
- Following StoreaBill's templating conventions
- Supporting all StoreaBill styling options

### Currency Conversion

- Fetches EUR to AED rates from Exchange Rate API
- Converts amounts in real-time during invoice generation
- Formats amounts with proper AED currency display
- Handles decimal precision and number formatting

## Hooks and Filters

### Actions

- `gaed_update_exchange_rates` - Triggered daily to update rates
- `gaed_after_rate_update` - Fired after successful rate update

### Filters

- `gaed_exchange_rate` - Modify the exchange rate before conversion
- `gaed_formatted_amount` - Customize AED amount formatting
- `gaed_attribution_text` - Modify attribution text

## File Structure

```
germanized-aed-totals/
├── germanized-aed-totals.php          # Main plugin file
├── includes/
│   ├── class-gaed-main.php            # Main plugin class
│   ├── class-gaed-currency-converter.php  # Currency conversion logic
│   ├── class-gaed-storeabill-integration.php  # StoreaBill integration
│   └── class-gaed-aed-total-block.php     # Custom block implementation
├── assets/
│   ├── js/
│   │   └── blocks.js                  # Block editor JavaScript
│   └── css/
│       ├── blocks-editor.css          # Editor styles
│       └── blocks-frontend.css        # Frontend styles
└── README.md                          # This file
```

## Development

### Adding New Currency Conversions

To add support for additional currencies:

1. Extend `GAED_Currency_Converter` class
2. Add new API endpoints for additional currencies
3. Create new block types following the same pattern
4. Register blocks in the integration class

### Customizing Display

CSS classes for styling:

- `.sab-aed-total-row` - Main AED total row
- `.sab-aed-total-data` - AED amount cell
- `.sab-aed-price` - AED price span
- `.sab-aed-attribution` - Attribution row

## Support

For support and bug reports, please check:

1. WordPress error logs for API issues
2. Browser console for JavaScript errors
3. StoreaBill template compatibility
4. Exchange Rate API status

## License

This plugin is released under the GPL v2 or later license.

## Attribution

This plugin uses the Exchange Rate API (https://www.exchangerate-api.com) for currency conversion data. Attribution is automatically included in invoice outputs as required by their terms of service.