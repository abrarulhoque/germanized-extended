<?php
/**
 * AED Total Block for StoreaBill
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class GAED_AED_Total_Block extends \Vendidero\StoreaBill\Editor\Blocks\DynamicBlock {

    /**
     * Block name.
     *
     * @var string
     */
    protected $block_name = 'aed-total-row';

    /**
     * Block namespace.
     *
     * @var string
     */
    protected $namespace = 'gaed';

    public function get_available_shortcodes() {
        return array(
            array(
                'title'     => _x( 'Tax Rate', 'storeabill-core', 'woocommerce-germanized-pro' ),
                'shortcode' => 'document_total?data=rate&total_type=taxes',
            ),
        );
    }

    public function get_attributes() {
        return array(
            'totalType'             => $this->get_schema_string( 'total' ),
            'heading'               => $this->get_schema_string( false ),
            'content'               => $this->get_schema_string( '{total}' ),
            'borderColor'           => $this->get_schema_string(),
            'className'             => $this->get_schema_string(),
            'customBorderColor'     => $this->get_schema_string(),
            'backgroundColor'       => $this->get_schema_string(),
            'customBackgroundColor' => $this->get_schema_string(),
            'textColor'             => $this->get_schema_string(),
            'customTextColor'       => $this->get_schema_string(),
            'fontSize'              => $this->get_schema_string( '' ),
            'customFontSize'        => $this->get_schema_string(),
            'hideIfEmpty'           => $this->get_schema_boolean( false ),
            'renderNumber'          => $this->get_schema_number( 1 ),
            'renderTotal'           => $this->get_schema_number( 1 ),
            'borders'               => array(
                'type'    => 'array',
                'default' => array(),
                'items'   => array(
                    'type' => 'string',
                ),
            ),
        );
    }

    /**
     * Render the AED total block
     *
     * @param array  $attributes Block attributes
     * @param string $content    Block content
     * @return string Rendered block output
     */
    public function render( $attributes = array(), $content = '' ) {
        self::maybe_setup_document();

        if ( ! isset( $GLOBALS['document'] ) ) {
            return $content;
        }

        /**
         * @var \Vendidero\StoreaBill\Document\Document $document
         */
        $document                = $GLOBALS['document'];
        $attributes              = $this->parse_attributes( $attributes );
        $attributes['totalType'] = sab_map_invoice_total_type( $attributes['totalType'], $document );

        $classes = array_merge( sab_generate_block_classes( $attributes ), array( 'item-total' ) );
        $styles  = sab_generate_block_styles( $attributes );

        $document_totals = $document->get_totals( $attributes['totalType'] );

        $total_content = $attributes['content'];
        $classes[]     = 'sab-item-total-row';
        $classes[]     = 'sab-item-total-row-' . str_replace( '_', '-', $attributes['totalType'] );

        foreach ( $attributes['borders'] as $border ) {
            $classes[] = 'sab-item-total-row-border-' . $border;
        }

        if ( ! empty( $document_totals ) ) {
            $count   = 0;
            $content = '';

            foreach ( $document_totals as $total ) {
                if ( false !== $attributes['heading'] ) {
                    $total->set_label( $attributes['heading'] );
                }

                /**
                 * Remove the actual net tax rate in case only one tax rate is included.
                 */
                if ( 'nets' === $attributes['totalType'] && 1 === count( $document_totals ) ) {
                    $label = sab_remove_placeholder_tax_rate( $total->get_label() );
                    $total->set_label( $label );
                }

                /**
                 * In case a fee has a negative total amount - force a discount label.
                 */
                if ( 'fee' === $attributes['totalType'] && $total->get_total() < 0 ) {
                    $total->set_label( _x( 'Discount', 'storeabill-core', 'woocommerce-germanized-pro' ) );
                } elseif ( 'fees' === $attributes['totalType'] && $total->get_total() < 0 ) {
                    $total->set_label( _x( 'Discount: %s', 'storeabill-core', 'woocommerce-germanized-pro' ) );
                }

                /**
                 * Skip for empty amounts.
                 */
                if ( ( true === $attributes['hideIfEmpty'] && empty( $total->get_total() ) ) || apply_filters( "storeabill_hide_{$document->get_type()}_total_row", false, $attributes, $total, $document ) ) {
                    continue;
                }

                ++$count;

                /**
                 * Remove border top styles
                 */
                if ( $count > 1 ) {
                    $classes = array_diff( $classes, array( 'sab-item-total-row-border-top', 'has-border-top' ) );

                    if ( $count < count( $document_totals ) ) {
                        $classes = array_diff( $classes, array( 'sab-item-total-row-border-bottom', 'has-border-bottom' ) );
                    }
                }

                do_action( "storeabill_setup_{$document->get_type()}_total_row", $total, $document );

                $total_classes = array_merge( $classes, sab_get_html_loop_classes( 'sab-item-total-row', $attributes['renderTotal'], $count ) );
                \Vendidero\StoreaBill\Package::setup_document_total( $total );

                // Convert EUR amount to AED
                $eur_amount    = $total->get_total();
                $aed_amount    = GAED_Currency_Converter::convert_eur_to_aed( $eur_amount );
                $formatted_aed = GAED_Currency_Converter::format_aed_amount( $aed_amount );

                // Replace {total} with AED amount in content
                $aed_total_content = str_replace( '{total}', $formatted_aed, $total_content );

                // Modify label to show AED
                $aed_label = $total->get_formatted_label();
                if ( false === $attributes['heading'] || '' === $attributes['heading'] ) {
                    $aed_label .= ' (AED)';
                }

                $total_html_content = sab_get_template_html(
                    'blocks/item-totals/total.php',
                    array(
                        'total'           => $total,
                        'formatted_label' => $aed_label,
                        'formatted_total' => $aed_total_content,
                        'classes'         => $total_classes,
                        'styles'          => $styles,
                    )
                );

                $content .= sab_do_shortcode( $total_html_content );
            }
        }

        return $content;
    }


    /**
     * Register the block type
     */
    public function register_type() {
        if ( function_exists( 'register_block_type' ) ) {
            register_block_type(
                $this->namespace . '/' . $this->block_name,
                array(
                    'render_callback' => array( $this, 'render' ),
                    'attributes'      => $this->get_attributes(),
                    'category'        => 'storeabill',
                    'supports'        => array(),
                )
            );
        }
    }
}
