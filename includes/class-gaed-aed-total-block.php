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

    public function get_attributes() {
        return array(
            'totalType'         => $this->get_schema_string( 'total' ),
            'heading'           => $this->get_schema_string( false ),
            'content'           => $this->get_schema_string( '{total_aed}' ),
            'borderColor'       => $this->get_schema_string(),
            'className'         => $this->get_schema_string(),
            'customBorderColor' => $this->get_schema_string(),
            'textColor'         => $this->get_schema_string(),
            'customTextColor'   => $this->get_schema_string(),
            'fontSize'          => $this->get_schema_string( '' ),
            'hideIfEmpty'       => $this->get_schema_boolean( false ),
            'borders'           => array(
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

        $document   = $GLOBALS['document'];
        $attributes = $this->parse_attributes( $attributes );

        $total_type = isset( $attributes['totalType'] ) ? $attributes['totalType'] : 'total';
        $total_type = sab_map_invoice_total_type( $total_type, $document );

        $classes = array_merge( sab_generate_block_classes( $attributes ), array( 'item-total' ) );
        $styles  = sab_generate_block_styles( $attributes );

        $document_totals = $document->get_totals( $total_type );

        if ( empty( $document_totals ) ) {
            return '';
        }

        $classes[] = 'sab-item-total-row';
        $classes[] = 'sab-aed-total-row';
        $classes[] = 'sab-item-total-row-' . str_replace( '_', '-', $total_type );

        foreach ( $attributes['borders'] as $border ) {
            $classes[] = 'sab-item-total-row-border-' . $border;
        }

        $totals_to_render = array();

        foreach ( $document_totals as $total ) {
            if ( false !== $attributes['heading'] && '' !== $attributes['heading'] ) {
                $total->set_label( $attributes['heading'] );
            }

            if ( 'nets' === $total_type && 1 === count( $document_totals ) ) {
                $label = sab_remove_placeholder_tax_rate( $total->get_label() );
                $total->set_label( $label );
            }

            if ( 'fee' === $total_type && $total->get_total() < 0 ) {
                $total->set_label( _x( 'Discount', 'storeabill-core', 'woocommerce-germanized-pro' ) );
            } elseif ( 'fees' === $total_type && $total->get_total() < 0 ) {
                $total->set_label( _x( 'Discount: %s', 'storeabill-core', 'woocommerce-germanized-pro' ) );
            }

            if ( ( true === $attributes['hideIfEmpty'] && empty( $total->get_total() ) ) || apply_filters( "storeabill_hide_{$document->get_type()}_total_row", false, $attributes, $total, $document ) ) {
                continue;
            }

            $totals_to_render[] = $total;
        }

        if ( empty( $totals_to_render ) ) {
            return '';
        }

        $total_content = $attributes['content'];
        $content       = '';
        $count         = 0;
        $total_count   = count( $totals_to_render );

        foreach ( $totals_to_render as $total ) {
            ++$count;

            if ( $count > 1 ) {
                $classes = array_diff( $classes, array( 'sab-item-total-row-border-top', 'has-border-top' ) );

                if ( $count < $total_count ) {
                    $classes = array_diff( $classes, array( 'sab-item-total-row-border-bottom', 'has-border-bottom' ) );
                }
            }

            do_action( "storeabill_setup_{$document->get_type()}_total_row", $total, $document );

            \Vendidero\StoreaBill\Package::setup_document_total( $total );

            $heading = $attributes['heading'];

            if ( false === $heading || '' === $heading ) {
                $heading = $total->get_label() . ' (AED)';
            }

            $eur_amount    = $total->get_total();
            $aed_amount    = GAED_Currency_Converter::convert_eur_to_aed( $eur_amount );
            $formatted_aed = GAED_Currency_Converter::format_aed_amount( $aed_amount );

            $formatted_total = str_replace(
                array( '{total_aed}', '{total}' ),
                $formatted_aed,
                $total_content
            );

            $row_classes = array_merge(
                $classes,
                sab_get_html_loop_classes( 'sab-item-total-row', $total_count, $count )
            );

            $content .= sab_do_shortcode(
                $this->get_total_template_html(
                    array(
                        'total'           => $total,
                        'formatted_label' => $heading,
                        'formatted_total' => $formatted_total,
                        'classes'         => $row_classes,
                        'styles'          => $styles,
                    )
                )
            );
        }

        return $content;
    }

    /**
     * Get the template HTML for a total row
     *
     * @param array $args Template arguments
     * @return string HTML content
     */
    private function get_total_template_html( $args ) {
        $defaults = array(
            'total'           => null,
            'formatted_label' => '',
            'formatted_total' => '',
            'classes'         => array(),
            'styles'          => array(),
        );

        $args = wp_parse_args( $args, $defaults );

        ob_start();
        ?>
        <tr class="<?php echo esc_attr( implode( ' ', $args['classes'] ) ); ?>" style="<?php echo esc_attr( sab_print_styles( $args['styles'], false ) ); ?>">
            <td class="sab-item-total-heading" style="<?php echo esc_attr( sab_print_styles( $args['styles'], false ) ); ?>">
                <?php echo wp_kses_post( $args['formatted_label'] ); ?>
            </td>
            <td class="sab-item-total-data sab-aed-total-data" style="<?php echo esc_attr( sab_print_styles( $args['styles'], false ) ); ?>">
                <span class="sab-price sab-aed-price"><?php echo wp_kses_post( $args['formatted_total'] ); ?></span>
            </td>
        </tr>
        <?php
        return ob_get_clean();
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
