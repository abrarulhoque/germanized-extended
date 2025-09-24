<?php
/**
 * StoreaBill item total row template override with AED conversion.
 */

defined( 'ABSPATH' ) || exit;

/** @var \Vendidero\StoreaBill\Document\Document $document */
global $document;

$aed_enabled = GAED_StoreaBill_Integration::show_totals_enabled();
$aed_enabled = apply_filters( 'gaed_show_aed_total_for_row', $aed_enabled, $total, $document );

if ( $aed_enabled && class_exists( 'GAED_Currency_Converter' ) ) {
    $aed_amount        = GAED_Currency_Converter::convert_eur_to_aed( $total->get_total() );
    $formatted_aed     = GAED_Currency_Converter::format_aed_amount( $aed_amount );
    $formatted_aed     = apply_filters( 'gaed_formatted_aed_total', $formatted_aed, $total, $document );
    $parenthetical     = GAED_StoreaBill_Integration::get_parenthetical_aed( $formatted_aed, $total, $document );
    $parenthetical     = apply_filters( 'gaed_aed_total_parenthetical', $parenthetical, $formatted_aed, $total, $document );
} else {
    $formatted_aed = '';
    $parenthetical = '';
}
?>

<tr class="<?php sab_print_html_classes( $classes ); ?>" style="<?php sab_print_styles( $styles ); ?>">
    <td class="sab-item-total-heading" style="<?php sab_print_styles( $styles ); ?>">
        <?php echo wp_kses_post( $formatted_label ); ?>
    </td>
    <td class="sab-item-total-data" style="<?php sab_print_styles( $styles ); ?>">
        <span class="sab-price sab-price--primary"><?php echo wp_kses_post( $formatted_total ); ?></span>
        <?php if ( ! empty( $parenthetical ) ) : ?>
            <span class="sab-price sab-price--aed"><?php echo wp_kses_post( $parenthetical ); ?></span>
        <?php endif; ?>
    </td>
</tr>
