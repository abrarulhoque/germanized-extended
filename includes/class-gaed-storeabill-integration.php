<?php
/**
 * StoreaBill Integration class
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class GAED_StoreaBill_Integration {

    /**
     * Initialize the StoreaBill integration
     */
    public static function init() {
        // Hook into StoreaBill initialization
        add_action( 'init', array( __CLASS__, 'register_assets' ), 15 );

        add_filter( 'storeabill_document_template_editor_asset_whitelist_paths', array( __CLASS__, 'whitelist_asset_paths' ) );
        add_filter( 'storeabill_get_template', array( __CLASS__, 'override_storeabill_templates' ), 10, 5 );

        // Ensure our styles are available inside the StoreaBill editor
        add_action( 'storeabill_load_block_editor', array( __CLASS__, 'enqueue_editor_styles' ) );
        add_action( 'enqueue_block_editor_assets', array( __CLASS__, 'enqueue_editor_styles' ) );

        // Add custom CSS for AED blocks
        add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_frontend_styles' ) );
        add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_frontend_styles' ) );
    }

    const OPTION_SHOW_TOTALS   = 'gaed_show_totals_aed';
    const OPTION_SHOW_LINES    = 'gaed_show_line_item_aed';
    const OPTION_SHOW_PAYMENT  = 'gaed_show_payment_method';

    /**
     * Check if a boolean option is enabled.
     */
    protected static function is_option_enabled( $option, $default = true ) {
        $value = get_option( $option, $default ? 1 : 0 );

        return (bool) apply_filters( 'gaed_option_' . $option, (bool) $value );
    }

    public static function show_totals_enabled() {
        return self::is_option_enabled( self::OPTION_SHOW_TOTALS, true );
    }

    public static function show_line_items_enabled() {
        return self::is_option_enabled( self::OPTION_SHOW_LINES, true );
    }

    public static function show_payment_method_enabled() {
        return self::is_option_enabled( self::OPTION_SHOW_PAYMENT, true );
    }

    /**
     * Ensure StoreaBill does not dequeue our assets.
     *
     * @param array $paths Existing whitelist paths.
     * @return array
     */
    public static function whitelist_asset_paths( $paths ) {
        $plugin_slug = 'plugins/germanized-aed-totals';

        if ( ! in_array( $plugin_slug, $paths, true ) ) {
            $paths[] = $plugin_slug;
        }

        return $paths;
    }

    /**
     * Override specific StoreaBill templates to inject AED amounts.
     *
     * @param string $template      Resolved template path.
     * @param string $template_name Relative template name.
     * @param array  $args          Template arguments.
     * @param string $template_path Custom template path supplied by StoreaBill.
     * @param string $default_path  Default StoreaBill template path.
     *
     * @return string
     */
    public static function override_storeabill_templates( $template, $template_name, $args, $template_path, $default_path ) {
        if ( 'blocks/item-totals/total.php' === $template_name ) {
            $custom_template = GAED_PLUGIN_PATH . 'templates/storeabill/blocks/item-totals/total.php';

            if ( file_exists( $custom_template ) ) {
                return $custom_template;
            }
        } elseif ( 'blocks/item-totals/totals.php' === $template_name ) {
            $custom_template = GAED_PLUGIN_PATH . 'templates/storeabill/blocks/item-totals/totals.php';

            if ( file_exists( $custom_template ) ) {
                return $custom_template;
            }
        } elseif ( 'blocks/item-table/row.php' === $template_name ) {
            $custom_template = GAED_PLUGIN_PATH . 'templates/storeabill/blocks/item-table/row.php';

            if ( file_exists( $custom_template ) ) {
                return $custom_template;
            }
        }

        return $template;
    }

    /**
     * Register block assets so WordPress can enqueue them when needed.
     */
    public static function register_assets() {
        wp_register_style(
            'gaed-storeabill-editor',
            GAED_PLUGIN_URL . 'assets/css/blocks-editor.css',
            array( 'wp-edit-blocks' ),
            GAED_VERSION
        );

        wp_register_style(
            'gaed-storeabill-frontend',
            GAED_PLUGIN_URL . 'assets/css/blocks-frontend.css',
            array(),
            GAED_VERSION
        );
    }

    /**
     * Enqueue styles when StoreaBill loads its block editor.
     */
    public static function enqueue_editor_styles() {
        if ( wp_style_is( 'gaed-storeabill-editor', 'registered' ) ) {
            wp_enqueue_style( 'gaed-storeabill-editor' );
            return;
        }

        wp_enqueue_style(
            'gaed-storeabill-editor',
            GAED_PLUGIN_URL . 'assets/css/blocks-editor.css',
            array( 'wp-edit-blocks' ),
            GAED_VERSION
        );
    }

    /**
     * Enqueue frontend styles
     */
    public static function enqueue_frontend_styles() {
        if ( wp_style_is( 'gaed-storeabill-frontend', 'registered' ) ) {
            wp_enqueue_style( 'gaed-storeabill-frontend' );
            return;
        }

        wp_enqueue_style(
            'gaed-storeabill-frontend',
            GAED_PLUGIN_URL . 'assets/css/blocks-frontend.css',
            array(),
            GAED_VERSION
        );
    }

    /**
     * Retrieve formatted AED display (wrapped in parentheses) with filter support.
     */
    public static function get_parenthetical_aed( $formatted_aed, $total = null, $document = null ) {
        $display = sprintf(
            /* translators: %s: formatted AED amount */
            __( '( %s )', 'germanized-aed-totals' ),
            $formatted_aed
        );

        return apply_filters( 'gaed_parenthetical_aed_display', $display, $formatted_aed, $total, $document );
    }

    /**
     * Create AED markup for line items.
     *
     * @param array                                        $column    Column data.
     * @param \Vendidero\StoreaBill\Document\Item         $item      Document item.
     * @param \Vendidero\StoreaBill\Document\Document     $document Document.
     *
     * @return string
     */
    public static function get_line_item_aed_markup( $column, $item, $document ) {
        if ( ! self::show_line_items_enabled() ) {
            return '';
        }

        if ( empty( $column['innerBlocks'] ) || ! class_exists( 'GAED_Currency_Converter' ) ) {
            return '';
        }

        $line_block = self::find_inner_block_by_name( $column['innerBlocks'], 'storeabill/item-line-total' );

        if ( ! $line_block ) {
            return '';
        }

        $attrs               = isset( $line_block['attrs'] ) ? $line_block['attrs'] : array();
        $show_including_tax  = isset( $attrs['showPricesIncludingTax'] ) ? (bool) $attrs['showPricesIncludingTax'] : true;
        $discount_total_type = isset( $attrs['discountTotalType'] ) ? $attrs['discountTotalType'] : 'before_discounts';

        $getter = self::build_item_total_getter( 'total', $show_including_tax, $discount_total_type, $document, $item );

        if ( ! $getter || ! is_callable( array( $item, $getter ) ) ) {
            return '';
        }

        $eur_total = $item->$getter();
        $aed_total = GAED_Currency_Converter::convert_eur_to_aed( $eur_total );
        $formatted = GAED_Currency_Converter::format_aed_amount( $aed_total );

        if ( empty( $formatted ) ) {
            return '';
        }

        $display = self::get_parenthetical_aed( $formatted, null, $document );
        $display = apply_filters( 'gaed_line_item_aed_display', $display, $formatted, $item, $document, $column );

        if ( empty( $display ) ) {
            return '';
        }

        return '<span class="gaed-line-item-aed sab-price--aed">' . wp_kses_post( $display ) . '</span>';
    }

    protected static function find_inner_block_by_name( $inner_blocks, $block_name ) {
        foreach ( (array) $inner_blocks as $block ) {
            if ( isset( $block['blockName'] ) && $block['blockName'] === $block_name ) {
                return $block;
            }
        }

        return null;
    }

    protected static function build_item_total_getter( $prefix, $inc_tax, $discount_total_type, $document, $item ) {
        $getter = 'get_' . $prefix;

        if ( 'before_discounts' === $discount_total_type ) {
            $getter .= '_subtotal';

            if ( strpos( $prefix, 'total' ) !== false ) {
                $getter = 'get_' . str_replace( 'total', 'subtotal', $prefix );
            }
        }

        if ( 'total_tax' === $prefix ) {
            return $getter;
        }

        $inc_tax = apply_filters( 'storeabill_document_item_table_prices_include_tax', $inc_tax, $prefix, $discount_total_type, $document, $item );

        if ( ! $inc_tax ) {
            if ( strpos( $prefix, '_total' ) !== false ) {
                $getter = str_replace( '_total', '', $getter );
            }

            $getter .= '_net';
        }

        return $getter;
    }

    /**
     * Get payment method display string for document.
     */
    public static function get_payment_method_display( $document ) {
        if ( ! self::show_payment_method_enabled() ) {
            return '';
        }

        $payment_method = '';

        if ( is_object( $document ) ) {
            if ( is_callable( array( $document, 'get_payment_method_title' ) ) ) {
                $payment_method = $document->get_payment_method_title();
            }

            if ( empty( $payment_method ) && is_callable( array( $document, 'get_order' ) ) ) {
                $order = $document->get_order();

                if ( $order && is_callable( array( $order, 'get_payment_method_title' ) ) ) {
                    $payment_method = $order->get_payment_method_title();
                }
            }
        }

        $payment_method = apply_filters( 'gaed_payment_method_title', $payment_method, $document );

        if ( empty( $payment_method ) ) {
            return '';
        }

        $text = sprintf(
            /* translators: %s payment method title */
            __( 'Paid via %s', 'germanized-aed-totals' ),
            $payment_method
        );

        return apply_filters( 'gaed_payment_method_notice', $text, $payment_method, $document );
    }
}
