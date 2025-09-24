<?php
/**
 * Currency Converter class
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class GAED_Currency_Converter {

    /**
     * Exchange Rate API endpoint
     */
    const API_URL = 'https://open.er-api.com/v6/latest/EUR';

    /**
     * Initialize the currency converter
     */
    public static function init() {
        // Initialize exchange rate if not set
        if ( ! get_option( 'gaed_exchange_rate' ) ) {
            self::update_exchange_rates();
        }
    }

    /**
     * Update exchange rates from the API
     */
    public static function update_exchange_rates() {
        // Make API request
        $response = wp_remote_get( self::API_URL, array(
            'timeout' => 30,
            'headers' => array(
                'User-Agent' => 'WordPress/' . get_bloginfo( 'version' ) . '; ' . home_url()
            )
        ) );

        if ( is_wp_error( $response ) ) {
            error_log( 'GAED: Failed to fetch exchange rates - ' . $response->get_error_message() );
            return false;
        }

        $response_code = wp_remote_retrieve_response_code( $response );
        if ( $response_code !== 200 ) {
            error_log( 'GAED: API returned error code ' . $response_code );
            return false;
        }

        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );

        if ( ! $data || ! isset( $data['result'] ) || $data['result'] !== 'success' ) {
            error_log( 'GAED: Invalid API response structure' );
            return false;
        }

        if ( ! isset( $data['rates']['AED'] ) ) {
            error_log( 'GAED: AED rate not found in API response' );
            return false;
        }

        // Update the exchange rate
        $aed_rate = floatval( $data['rates']['AED'] );
        update_option( 'gaed_exchange_rate', $aed_rate );
        update_option( 'gaed_last_update', time() );

        // Log successful update
        error_log( 'GAED: Exchange rate updated successfully - EUR to AED: ' . $aed_rate );

        return true;
    }

    /**
     * Convert EUR amount to AED
     *
     * @param float $eur_amount Amount in EUR
     * @return float Amount in AED
     */
    public static function convert_eur_to_aed( $eur_amount ) {
        $exchange_rate = get_option( 'gaed_exchange_rate', 0 );

        if ( $exchange_rate <= 0 ) {
            // Try to update rates if not available
            self::update_exchange_rates();
            $exchange_rate = get_option( 'gaed_exchange_rate', 0 );
        }

        if ( $exchange_rate <= 0 ) {
            // Fallback rate if API is unavailable
            $exchange_rate = 4.0; // Approximate fallback rate
            error_log( 'GAED: Using fallback exchange rate' );
        }

        return $eur_amount * $exchange_rate;
    }

    /**
     * Format AED amount for display
     *
     * @param float $aed_amount Amount in AED
     * @return string Formatted amount
     */
    public static function format_aed_amount( $aed_amount ) {
        if ( function_exists( 'wc_price' ) ) {
            return wc_price( $aed_amount, array( 'currency' => 'AED' ) );
        }

        return 'AED ' . number_format( $aed_amount, 2, '.', ',' );
    }

    /**
     * Get the current exchange rate
     *
     * @return float Current EUR to AED exchange rate
     */
    public static function get_current_rate() {
        return get_option( 'gaed_exchange_rate', 0 );
    }

    /**
     * Get last update timestamp
     *
     * @return int Last update timestamp
     */
    public static function get_last_update() {
        return get_option( 'gaed_last_update', 0 );
    }
}
