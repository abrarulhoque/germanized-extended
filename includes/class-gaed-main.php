<?php
/**
 * Main plugin class
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class GAED_Main {

    /**
     * Initialize the plugin
     */
    public static function init() {
        // Load required classes
        self::includes();

        // Initialize components
        GAED_Currency_Converter::init();
        GAED_StoreaBill_Integration::init();

        // Schedule exchange rate updates
        add_action( 'gaed_update_exchange_rates', array( 'GAED_Currency_Converter', 'update_exchange_rates' ) );

        // Handle manual exchange rate updates
        add_action( 'admin_post_gaed_update_rates', array( __CLASS__, 'handle_manual_rate_update' ) );

        // Add admin menu
        add_action( 'admin_menu', array( __CLASS__, 'add_admin_menu' ) );

        // Add settings
        add_action( 'admin_init', array( __CLASS__, 'init_settings' ) );
    }

    /**
     * Include required files
     */
    private static function includes() {
        require_once GAED_PLUGIN_PATH . 'includes/class-gaed-currency-converter.php';
        require_once GAED_PLUGIN_PATH . 'includes/class-gaed-storeabill-integration.php';
    }

    /**
     * Add admin menu
     */
    public static function add_admin_menu() {
        add_submenu_page(
            'woocommerce',
            __( 'AED Currency Settings', 'germanized-aed-totals' ),
            __( 'AED Currency', 'germanized-aed-totals' ),
            'manage_woocommerce',
            'gaed-settings',
            array( __CLASS__, 'settings_page' )
        );
    }

    /**
     * Initialize settings
     */
    public static function init_settings() {
        register_setting( 'gaed_settings', 'gaed_attribution_text' );
        register_setting( 'gaed_settings', 'gaed_last_update' );
        register_setting( 'gaed_settings', 'gaed_exchange_rate' );
        register_setting( 'gaed_settings', 'gaed_show_totals_aed', array(
            'type'              => 'boolean',
            'default'           => true,
            'sanitize_callback' => array( __CLASS__, 'sanitize_checkbox' ),
        ) );
        register_setting( 'gaed_settings', 'gaed_show_line_item_aed', array(
            'type'              => 'boolean',
            'default'           => true,
            'sanitize_callback' => array( __CLASS__, 'sanitize_checkbox' ),
        ) );
        register_setting( 'gaed_settings', 'gaed_show_payment_method', array(
            'type'              => 'boolean',
            'default'           => true,
            'sanitize_callback' => array( __CLASS__, 'sanitize_checkbox' ),
        ) );

        add_settings_section(
            'gaed_general',
            __( 'General Settings', 'germanized-aed-totals' ),
            null,
            'gaed_settings'
        );

        add_settings_field(
            'gaed_attribution_text',
            __( 'Attribution Text', 'germanized-aed-totals' ),
            array( __CLASS__, 'attribution_text_field' ),
            'gaed_settings',
            'gaed_general'
        );

        add_settings_field(
            'gaed_current_rate',
            __( 'Current EUR to AED Rate', 'germanized-aed-totals' ),
            array( __CLASS__, 'current_rate_field' ),
            'gaed_settings',
            'gaed_general'
        );

        add_settings_field(
            'gaed_show_totals_aed',
            __( 'Totals Display', 'germanized-aed-totals' ),
            array( __CLASS__, 'checkbox_field' ),
            'gaed_settings',
            'gaed_general',
            array(
                'option' => 'gaed_show_totals_aed',
                'label'  => __( 'Show AED amounts alongside totals', 'germanized-aed-totals' ),
                'default'=> true,
            )
        );

        add_settings_field(
            'gaed_show_line_item_aed',
            __( 'Line Items', 'germanized-aed-totals' ),
            array( __CLASS__, 'checkbox_field' ),
            'gaed_settings',
            'gaed_general',
            array(
                'option' => 'gaed_show_line_item_aed',
                'label'  => __( 'Show AED amounts for each line item', 'germanized-aed-totals' ),
                'default'=> true,
            )
        );

        add_settings_field(
            'gaed_show_payment_method',
            __( 'Payment Method Notice', 'germanized-aed-totals' ),
            array( __CLASS__, 'checkbox_field' ),
            'gaed_settings',
            'gaed_general',
            array(
                'option' => 'gaed_show_payment_method',
                'label'  => __( 'Show payment method notice below totals', 'germanized-aed-totals' ),
                'default'=> true,
            )
        );
    }

    /**
     * Sanitize checkbox values to 1/0.
     */
    public static function sanitize_checkbox( $value ) {
        return ! empty( $value ) ? 1 : 0;
    }

    /**
     * Settings page content
     */
    public static function settings_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
            <?php settings_errors( 'gaed_settings' ); ?>
            <form action="options.php" method="post">
                <?php
                settings_fields( 'gaed_settings' );
                do_settings_sections( 'gaed_settings' );
                ?>
                <p class="submit">
                    <input type="submit" name="submit" id="submit" class="button button-primary" value="<?php esc_attr_e( 'Save Changes', 'germanized-aed-totals' ); ?>">
                </p>
            </form>

            <form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post" style="margin-top: 1em;">
                <?php wp_nonce_field( 'gaed_update_rates' ); ?>
                <input type="hidden" name="action" value="gaed_update_rates">
                <?php submit_button( __( 'Update Exchange Rates Now', 'germanized-aed-totals' ), 'secondary', 'gaed-update-rates', false ); ?>
            </form>
        </div>
        <?php
    }

    /**
     * Handle manual exchange rate updates from the settings page.
     */
    public static function handle_manual_rate_update() {
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_die( esc_html__( 'You are not allowed to perform this action.', 'germanized-aed-totals' ) );
        }

        check_admin_referer( 'gaed_update_rates' );

        $success = GAED_Currency_Converter::update_exchange_rates();

        if ( $success ) {
            add_settings_error( 'gaed_settings', 'gaed_update_rates_success', __( 'Exchange rates updated successfully.', 'germanized-aed-totals' ), 'updated' );
        } else {
            add_settings_error( 'gaed_settings', 'gaed_update_rates_failed', __( 'Unable to update exchange rates. Please check the error log for details.', 'germanized-aed-totals' ), 'error' );
        }

        set_transient( 'settings_errors', get_settings_errors(), 30 );

        wp_safe_redirect( add_query_arg( array( 'page' => 'gaed-settings' ), admin_url( 'admin.php' ) ) );
        exit;
    }

    /**
     * Attribution text field
     */
    public static function attribution_text_field() {
        $value = get_option( 'gaed_attribution_text', 'Rates by Exchange Rate API' );
        echo '<input type="text" name="gaed_attribution_text" value="' . esc_attr( $value ) . '" class="regular-text" />';
        echo '<p class="description">' . __( 'Attribution text for the exchange rate provider (required by API terms).', 'germanized-aed-totals' ) . '</p>';
    }

    /**
     * Current rate field
     */
    public static function current_rate_field() {
        $rate = get_option( 'gaed_exchange_rate', 0 );
        $last_update = get_option( 'gaed_last_update', 0 );

        echo '<strong>' . esc_html( $rate ) . '</strong>';
        echo '<p class="description">';
        if ( $last_update ) {
            echo sprintf( __( 'Last updated: %s', 'germanized-aed-totals' ), date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $last_update ) );
        } else {
            echo __( 'Never updated', 'germanized-aed-totals' );
        }
        echo '</p>';
    }

    /**
     * Generic checkbox renderer.
     *
     * @param array $args Field arguments.
     */
    public static function checkbox_field( $args ) {
        $option  = isset( $args['option'] ) ? $args['option'] : '';
        $label   = isset( $args['label'] ) ? $args['label'] : '';
        $default = isset( $args['default'] ) ? (bool) $args['default'] : false;

        if ( empty( $option ) ) {
            return;
        }

        $value = get_option( $option, $default ? 1 : 0 );

        echo '<label>';
        echo '<input type="checkbox" name="' . esc_attr( $option ) . '" value="1" ' . checked( $value, 1, false ) . ' /> ';
        echo esc_html( $label );
        echo '</label>';
    }
}
