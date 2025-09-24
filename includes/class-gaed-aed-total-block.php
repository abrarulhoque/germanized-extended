<?php
/**
 * AED Total Block enhancements for StoreaBill.
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class GAED_AED_Total_Block extends \Vendidero\StoreaBill\Editor\Blocks\ItemTotalRow {

    /**
     * Bootstrap filters for AED rendering.
     */
    public static function init() {
        $instance = new self();

        add_filter( 'render_block_storeabill/item-total-row', array( $instance, 'maybe_render_aed' ), 20, 2 );
    }

    /**
     * Replace default output when the AED variation is used.
     *
     * @param string $block_content Original block output.
     * @param array  $block         Parsed block data.
     *
     * @return string
     */
    public function maybe_render_aed( $block_content, $block ) {
        if ( empty( $block['attrs']['gaedIsAed'] ) ) {
            return $block_content;
        }

        self::maybe_setup_document();

        if ( ! isset( $GLOBALS['document'] ) ) {
            return $block_content;
        }

        $attributes = $this->parse_attributes( $block['attrs'] );
        $attributes['gaedIsAed'] = true;

        return $this->render_aed_totals( $attributes );
    }

    /**
     * Render the AED totals using StoreaBill templates.
     *
     * @param array $attributes Block attributes.
     *
     * @return string
     */
    protected function render_aed_totals( $attributes ) {
        /** @var \Vendidero\StoreaBill\Document\Document $document */
        $document                = $GLOBALS['document'];
        $attributes['totalType'] = sab_map_invoice_total_type( $attributes['totalType'], $document );

        $classes = array_merge( sab_generate_block_classes( $attributes ), array( 'item-total' ) );
        $styles  = sab_generate_block_styles( $attributes );

        $document_totals = $document->get_totals( $attributes['totalType'] );

        if ( empty( $document_totals ) ) {
            return '';
        }

        $classes[] = 'sab-item-total-row';
        $classes[] = 'sab-item-total-row-' . str_replace( '_', '-', $attributes['totalType'] );

        foreach ( (array) $attributes['borders'] as $border ) {
            $classes[] = 'sab-item-total-row-border-' . $border;
        }

        $totals_to_render = array();

        foreach ( $document_totals as $total ) {
            if ( false !== $attributes['heading'] ) {
                $total->set_label( $attributes['heading'] );
            }

            if ( 'nets' === $attributes['totalType'] && 1 === count( $document_totals ) ) {
                $label = sab_remove_placeholder_tax_rate( $total->get_label() );
                $total->set_label( $label );
            }

            if ( 'fee' === $attributes['totalType'] && $total->get_total() < 0 ) {
                $total->set_label( _x( 'Discount', 'storeabill-core', 'woocommerce-germanized-pro' ) );
            } elseif ( 'fees' === $attributes['totalType'] && $total->get_total() < 0 ) {
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

        $content     = '';
        $count       = 0;
        $total_count = count( $totals_to_render );
        $template    = ! empty( $attributes['content'] ) ? $attributes['content'] : '{total_aed}';

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

            $row_classes = array_merge(
                $classes,
                sab_get_html_loop_classes( 'sab-item-total-row', isset( $attributes['renderTotal'] ) ? $attributes['renderTotal'] : 1, $count )
            );

            $formatted_total = $this->format_aed_total( $template, $total );

            $content .= sab_do_shortcode(
                sab_get_template_html(
                    'blocks/item-totals/total.php',
                    array(
                        'total'           => $total,
                        'formatted_label' => $total->get_formatted_label(),
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
     * Replace placeholders with AED and EUR amounts.
     *
     * @param string                                       $template Template string.
     * @param \Vendidero\StoreaBill\Interfaces\Summable $total    Total object instance.
     *
     * @return string
     */
    protected function format_aed_total( $template, $total ) {
        $eur_amount    = floatval( $total->get_total() );
        $formatted_eur = $total->get_formatted_total();

        $aed_amount    = GAED_Currency_Converter::convert_eur_to_aed( $eur_amount );
        $formatted_aed = GAED_Currency_Converter::format_aed_amount( $aed_amount );

        return strtr(
            $template,
            array(
                '{total_aed}' => $formatted_aed,
                '{total_eur}' => $formatted_eur,
                '{total}'     => $formatted_aed,
            )
        );
    }
}
